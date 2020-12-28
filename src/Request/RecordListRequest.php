<?php

namespace Guym4c\Airtable\Request;

use Guym4c\Airtable\Airtable;
use Guym4c\Airtable\AirtableApiException;
use Guym4c\Airtable\ListFilter;
use Guym4c\Airtable\Record;

class RecordListRequest extends AbstractRequest {

    private const CACHE_LIFETIME = 60 * 60 * 24; // 24 hours

    private ?string $offsetOfNextPage = '';

    private ?ListFilter $filter;

    /** @var ?Record[] */
    private ?array $records = [];

    private string $searchField;

    private $searchValue;

    public function __construct(
        Airtable $airtable,
        string $table,
        string $searchField = '',
        $searchValue = '',
        ?ListFilter $filter = null,
        string $offset = ''
    ) {
        parent::__construct(
            $airtable,
            $table,
            'GET',
            '',
            self::getQuery($searchField, $searchValue, $filter)
        );

        $this->searchField = $searchField;
        $this->searchValue = $searchValue;
        $this->filter = $filter;

        $this->options['query']['offset'] = $offset;
    }

    private static function getQuery(string $searchField, $searchValue, ?ListFilter $filter): array {
        if (empty($searchField)) {
            return empty($filter)
                ? []
                : $filter->jsonSerialize();
        }
        return ListFilter::constructSearch($searchField, $searchValue)
            ->jsonSerialize();
    }

    /**
     * @return self
     * @throws AirtableApiException
     */
    public function getResponse(): self {
        return $this->getCachedResponse();
    }

    /**
     * @param bool $useCacheFirst
     * @return RecordListRequest
     * @throws AirtableApiException
     */
    private function getCachedResponse(bool $useCacheFirst = true): self {

        $cache = $this->airtable->getCache();
        $jsonIsFromCache = false;

        // retrieve data
        if (
            !empty($cache)
            && $useCacheFirst
            && $cache->contains($this->table)
        ) {
            $json = $cache->fetch($this->table);
            $jsonIsFromCache = true;
        } else {
            $json = $this->execute();
        }

        $this->offsetOfNextPage = $json['offset'] ?? null;

        // if can be cached
        if (
            !empty($cache)
            && $this->airtable->isCachableTable($this->table)
            && $this->isCachableRequest()
            && !$jsonIsFromCache
        ) {
            $cache->save($this->table, $json, self::CACHE_LIFETIME);
        }

        $this->records = $this->parseJsonRecords($json['records']);

        if (
            $jsonIsFromCache
            && !empty($this->searchField)
        ) {
            $this->records = $this->findRecords($this->searchField, $this->searchValue);

            if (empty($this->records)) {
                return $this->getCachedResponse(false);
            }
        }

        return $this;
    }

    /**
     * Returns the next page of the response. If there are no further pages, returns null.
     *
     * @return self|null
     * @throws AirtableApiException
     */
    public function nextPage(): ?self {

        if (empty($this->offsetOfNextPage)) {
            return null;
        }

        $nextPage = new self($this->airtable,
            $this->table,
            $this->searchField,
            $this->searchValue,
            $this->filter,
            $this->offsetOfNextPage);

        return $nextPage->getResponse();
    }

    /**
     * @return Record[]
     */
    public function getRecords(): array {
        return $this->records;
    }

    /**
     * @param string $field
     * @param mixed  $value
     * @param bool   $exactMatch
     * @return Record[]
     */
    public function findRecords(string $field, $value, bool $exactMatch = false): array {
        $results = [];
        foreach ($this->records as $record) {
            if (
                (
                    $exactMatch
                    && $record->{$field} === $value
                )
                || (
                    !$exactMatch
                    && strpos($record->{$field}, $value) !== false
                )
            ) {
                $results[] = $record;
            }
        }
        return $results;
    }

    /**
     * @param array $jsonRecords
     * @return Record[]
     */
    private function parseJsonRecords(array $jsonRecords): array {
        $records = [];
        foreach ($jsonRecords as $record) {
            $records[] = new Record($this->airtable, $this->table, $record);
        }
        return $records;
    }

    private function isCachableRequest(): bool {
        return empty($this->offsetOfNextPage)
            && empty($this->searchField)
            && empty($this->filter);
    }
}
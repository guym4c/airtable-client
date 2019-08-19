<?php

namespace Guym4c\Airtable\Request;

use Guym4c\Airtable\Airtable;
use Guym4c\Airtable\AirtableApiException;
use Guym4c\Airtable\ListFilter;
use Guym4c\Airtable\Record;

class RecordListRequest extends AbstractRequest {

    private const CACHE_LIFETIME = 60 * 60 * 24; // 24 hours

    /** @var ?string */
    private $offset;

    /** @var ?Record[] */
    private $records = [];

    /** @var string */
    private $searchField;

    /** @var mixed */
    private $searchValue;

    public function __construct(Airtable $airtable, string $table, string $searchField = '', $searchValue = '', ?ListFilter $filter = null) {
        parent::__construct($airtable, $table, 'GET', '', empty($searchField)
            ? (empty($filter)
                ? []
                : $filter->jsonSerialize())
            : ListFilter::constructSearch($searchField, $searchValue)
                ->jsonSerialize(),
            []);

        $this->searchField = $searchField;
        $this->searchValue = $searchValue;
    }

    /**
     * @return self
     * @throws AirtableApiException
     */
    public function getResponse(): self {
        return $this->getCachedResponse();
    }

    /**
     * @param bool $cached
     * @return RecordListRequest
     * @throws AirtableApiException
     */
    private function getCachedResponse(bool $cached = true): self {

        $cache = $this->airtable->getCache();
        $jsonIsFromCache = false;

        if (!empty($cache) &&
            $cached &&
            $cache->contains($this->table)) {

            $json = $cache->fetch($this->table);
            $jsonIsFromCache = true;
        } else {
            $json = $this->execute();
        }

        $this->offset = $json['offset'] ?? null;

        if (!empty($cache) &&
            in_array($this->table, $this->airtable->getCachedTables())) {

            if (empty($this->offset) &&
                empty($this->searchField)) {

                $cache->save($this->table, $json, self::CACHE_LIFETIME);
            } else {
                $cache->delete($this->table);
            }
        }

        $this->records = $this->parseJsonRecords($json['records']);

        if ($jsonIsFromCache &&
            !empty($this->searchField)) {

            $this->records = $this->findRecords($this->searchField, $this->searchValue);

            if (empty($this->records)) {
                return $this->getCachedResponse(false);
            }
        }

        return $this;
    }

    /**
     * @return self
     * @throws AirtableApiException
     */
    public function nextPage(): self {

        if (empty($offset)) {
            return null;
        }

        $this->options['query']['offset'] = $this->offset;
        return $this->getResponse();
    }

    /**
     * @return Record[]
     */
    public function getRecords(): array {
        return $this->records;
    }

    /**
     * @param string $field
     * @param string $value
     * @return Record[]
     */
    public function findRecords(string $field, string $value): array {
        $results = [];
        foreach ($this->records as $record) {
            if ($record->{$field} === $value) {
                $results[] = $record;
            }
        }
        return $results;
    }

    /**
     * @param array $jsonRecords
     * @return Record[]
     */
    public function parseJsonRecords(array $jsonRecords): array {
        $records = [];
        foreach ($jsonRecords as $record) {
            $records[] = new Record($this->airtable, $this->table, $record);
        }
        return $records;
    }
}
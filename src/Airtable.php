<?php

namespace Guym4c\Airtable;

use Doctrine\Common\Cache\CacheProvider;
use Guym4c\Airtable\Request\BatchUpdateRequest;
use Guym4c\Airtable\Request\DeleteRequest;
use Guym4c\Airtable\Request\BatchCreateRequest;
use Guym4c\Airtable\Request\RecordListRequest;
use Guym4c\Airtable\Request\SingleRecordRequest;

class Airtable {

    private const RECORD_BATCH_SIZE = 10;
    private const DEFAULT_API_ENDPOINT = 'https://api.airtable.com/v0';

    private string $key;

    private string $baseId;

    private ?CacheProvider $cache;

    /** @var string[] */
    private array $cachableTables;

    private ?string $apiEndpoint;

    /** @var string[] */
    private array $headers;

    private bool $isRateLimited;

    public function __construct(
        string $key,
        string $baseId,
        ?CacheProvider $cache = null,
        array $cachableTables = [],
        ?string $apiEndpoint = null,
        array $headers = [],
        bool $isRateLimited = true
    ) {
        $this->key = $key;
        $this->baseId = $baseId;
        $this->cache = $cache;
        $this->apiEndpoint = $apiEndpoint ?? self::DEFAULT_API_ENDPOINT;
        $this->headers = $headers;
        $this->isRateLimited = $isRateLimited;

        if (empty($cache)) {
            $this->cachableTables = [];
        } else {
            $this->cachableTables = $cachableTables;
        }
    }

    /**
     * Get a single record.
     *
     * @param string $table
     * @param string $id
     * @return Record
     * @throws AirtableApiException
     */
    public function get(string $table, string $id): Record {
        return (new SingleRecordRequest($this, $table, 'GET', $id))
            ->getResponse();
    }

    /**
     * @param string          $table
     * @param ListFilter|null $filter
     * @return RecordListRequest
     * @throws AirtableApiException
     */
    public function list(string $table, ?ListFilter $filter = null): RecordListRequest {

        return (new RecordListRequest($this, $table, '', '', $filter))
            ->getResponse();
    }

    /**
     * Searches Airtable for $value in $field.
     * @see find() for an exact match
     *
     * @param string $table
     * @param string $field
     * @param        $value
     * @return RecordListRequest
     * @throws AirtableApiException
     */
    public function search(string $table, string $field, $value): RecordListRequest {

        return (new RecordListRequest($this, $table, $field, $value))
            ->getResponse();
    }

    /**
     * Finds an exact match for $value in $field.
     * @see search() for a non-exact match.
     *
     * @param string $table
     * @param string $field
     * @param        $value
     * @return Record[]
     * @throws AirtableApiException
     */
    public function find(string $table, string $field, $value): array {

        return $this->search($table, $field, $value)
            ->findRecords($field, $value, true);
    }

    /**
     * @param Record $record
     * @return Record
     * @throws AirtableApiException
     */
    public function update(Record $record): Record {
        $this->deleteCacheForTable($record->getTable());
        return (new SingleRecordRequest($this, $record->getTable(), 'PATCH', $record->getId(),
            ['fields' => $record->getUpdatedFields()]))
            ->getResponse();
    }

    /**
     * @param string $table
     * @param array $records
     * @return Record[]
     * @throws AirtableApiException
     */
    public function updateAll(string $table, array $records): array {
        $this->deleteCacheForTable($table);
        $results = [];
        foreach (array_chunk($records, self::RECORD_BATCH_SIZE) as $chunk) {
            $results = array_merge(
                $results,
                (new BatchUpdateRequest($this, $table, $chunk))
                    ->getResponse(),
            );
        }
        return $results;
    }

    /**
     * @param string $table
     * @param array  $data
     * @return Record
     * @throws AirtableApiException
     */
    public function create(string $table, array $data): Record {
        $this->deleteCacheForTable($table);
        return (new SingleRecordRequest($this, $table, 'POST', '',
            ['fields' => $data]))
            ->getResponse();
    }

    /**
     * @param string $table
     * @param array $data
     * @return Record[]
     * @throws AirtableApiException
     */
    public function createAll(string $table, array $data): array {
        $this->deleteCacheForTable($table);
        $results = [];
        foreach (array_chunk($data, self::RECORD_BATCH_SIZE) as $chunk) {
            $results = array_merge(
                $results,
                (new BatchCreateRequest($this, $table, $chunk))
                    ->getResponse(),
            );
        }
        return $results;
    }

    /**
     * @param string $table
     * @param string $id
     * @return bool
     * @throws AirtableApiException
     */
    public function delete(string $table, string $id): bool {
        $this->deleteCacheForTable($table);
        return (new DeleteRequest($this, $table, $id))
            ->getResponse();
    }

    private function deleteCacheForTable(string $table): void {
        if (
            !empty($this->getCache())
            && $this->isCachableTable($table)
        ) {
            $this->getCache()->delete($table);
        }
    }

    /**
     * @return string
     */
    public function getKey(): string {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getBaseId(): string {
        return $this->baseId;
    }

    /**
     * @return CacheProvider|null
     */
    public function getCache(): ?CacheProvider {
        return $this->cache;
    }

    /**
     * @return array
     */
    public function getCachableTables(): array {
        return $this->cachableTables;
    }

    /**
     * @param string $table
     * @return bool
     */
    public function isCachableTable(string $table): bool {
        return in_array($table, $this->getCachableTables());
    }

    /**
     * @return bool
     */
    public function flushCache(): bool {
        if (!empty($this->cache)) {
            return $this->cache->flushAll();
        }
        return false;
    }

    /**
     * @return string|null
     */
    public function getApiEndpoint(): ?string {
        return $this->apiEndpoint;
    }

    /**
     * @return string[]
     */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * @return bool
     */
    public function isRateLimited(): bool {
        return $this->isRateLimited;
    }
}
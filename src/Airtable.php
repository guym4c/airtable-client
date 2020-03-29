<?php

namespace Guym4c\Airtable;

use Doctrine\Common\Cache\CacheProvider;
use Guym4c\Airtable\Request\DeleteRequest;
use Guym4c\Airtable\Request\RecordListRequest;
use Guym4c\Airtable\Request\SingleRecordRequest;

class Airtable {

    /** @var string */
    private $key;

    /** @var string */
    private $baseId;

    /** @var ?CacheProvider */
    private $cache;

    /** @var string[] */
    private $cachableTables;

    /** @var string|null */
    private $apiEndpoint;

    /** @var string[] */
    private $headers;

    /** @var bool */
    private $isRateLimited;

    const DEFAULT_API_ENDPOINT = 'https://api.airtable.com/v0';

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
     * @param Record $record
     * @return Record
     * @throws AirtableApiException
     */
    public function update(Record $record): Record {
        return (new SingleRecordRequest($this, $record->getTable(), 'PATCH', $record->getId(),
            ['fields' => $record->getUpdatedFields()]))
            ->getResponse();
    }

    /**
     * @param string $table
     * @param array  $data
     * @return Record
     * @throws AirtableApiException
     */
    public function create(string $table, array $data): Record {
        return (new SingleRecordRequest($this, $table, 'POST', '',
            ['fields' => $data]))
            ->getResponse();
    }

    /**
     * @param string $table
     * @param string $id
     * @return bool
     * @throws AirtableApiException
     */
    public function delete(string $table, string $id): bool {
        return (new DeleteRequest($this, $table, $id))
            ->getResponse();
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
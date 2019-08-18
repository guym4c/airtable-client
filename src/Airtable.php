<?php

namespace Guym4c\Airtable;

use Doctrine\Common\Cache\Cache;
use Guym4c\Airtable\Request\DeleteRequest;
use Guym4c\Airtable\Request\RecordListRequest;
use Guym4c\Airtable\Request\SingleRecordRequest;

class Airtable {

    /** @var string */
    private $key;

    /** @var string */
    private $baseId;

    /** @var ?Cache */
    private $cache;

    /** @var array caching */
    private $cachedTables;

    const API_ENDPOINT = 'https://api.airtable.com/v0';

    public function __construct(string $key, string $baseId, ?Cache $cache = null, array $cachedTables = []) {
        $this->key = $key;
        $this->baseId = $baseId;
        $this->cache = $cache;

        if (empty($cache)) {
            $this->cachedTables = [];
        } else {
            $this->cachedTables = $cachedTables;
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
        return (new SingleRecordRequest($this, $record->getTable(), 'PATCH', $record->getId(), [],
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
        return (new SingleRecordRequest($this, $table, 'POST', '', [],
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
     * @return Cache|null
     */
    public function getCache(): ?Cache {
        return $this->cache;
    }

    /**
     * @return array
     */
    public function getCachedTables(): array {
        return $this->cachedTables;
    }
}
<?php

namespace Guym4c\Airtable\Request;

use Guym4c\Airtable\Airtable;
use Guym4c\Airtable\AirtableApiException;
use Guym4c\Airtable\Record;

class SingleRecordRequest extends AbstractRequest {

    /** @var string */
    private $id;

    /** @var bool */
    private $useCache;

    public function __construct(Airtable $airtable, string $table, string $method, string $id, array $body = []) {
        parent::__construct($airtable, $table, $method, $id, [], $body);
        $this->id = $id;
        $this->useCache = empty($body);
    }

    /**
     * @return Record
     * @throws AirtableApiException
     */
    public function getResponse(): Record {

        if ($this->useCache &&
            !empty($this->airtable->getCache())) {

            $records = $this->airtable->list($this->table)
                ->getRecords();

            $record = $this->findRecord($records);
            if (!empty($record)) {
                return $record;
            }
        }

        return new Record($this->airtable, $this->table, $this->execute());
    }

    private function findRecord(array $records): ?Record {
        foreach ($records as $record) {
            if ($record->getId() == $this->id) {
                return $record;
            }
        }
        return null;
    }
}
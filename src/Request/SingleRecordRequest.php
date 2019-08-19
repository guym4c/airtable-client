<?php

namespace Guym4c\Airtable\Request;

use Guym4c\Airtable\Airtable;
use Guym4c\Airtable\AirtableApiException;
use Guym4c\Airtable\Record;

class SingleRecordRequest extends AbstractRequest {

    /** @var string */
    private $id;

    public function __construct(Airtable $airtable, string $table, string $method, string $id, array $body = []) {
        parent::__construct($airtable, $table, $method, $id, [], $body);
        $this->id = $id;
    }

    /**
     * @return Record
     * @throws AirtableApiException
     */
    public function getResponse(): Record {

        $cache = $this->airtable->getCache();

        if (!empty($cache) &&
            $cache->contains($this->table)) {

            $records = (new RecordListRequest($this->airtable, $this->table))
                ->getResponse()
                ->getRecords();

            foreach ($records as $record) {
                if ($record->getId() == $this->id) {
                    return $record;
                }
            }
        }

        return new Record($this->airtable, $this->table, $this->execute());
    }
}
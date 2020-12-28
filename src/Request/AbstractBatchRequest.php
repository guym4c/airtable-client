<?php

namespace Guym4c\Airtable\Request;

use Guym4c\Airtable\AirtableApiException;
use Guym4c\Airtable\Record;

abstract class AbstractBatchRequest extends AbstractRequest {

    /**
     * @throws AirtableApiException
     */
    public function getResponse(): array {
        $records = [];
        foreach ($this->execute()['records'] as $record) {
            $records[] = new Record($this->airtable, $this->table, $record);
        }
        return $records;
    }
}
<?php

namespace Guym4c\Airtable\Request;

use Guym4c\Airtable\AirtableApiException;
use Guym4c\Airtable\Record;

class SingleRecordRequest extends AbstractRequest {

    /**
     * @return Record
     * @throws AirtableApiException
     */
    public function getResponse(): Record {
        return new Record($this->airtable, $this->table, $this->execute());
    }
}
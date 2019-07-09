<?php

namespace Guym4c\Airtable\Request;

use Guym4c\Airtable\Airtable;
use Guym4c\Airtable\AirtableApiException;

class DeleteRequest extends AbstractRequest {

    public function __construct(Airtable $airtable, string $table, string $uri = '') {
        parent::__construct($airtable, $table, 'DELETE', $uri);
    }

    /**
     * @return bool
     * @throws AirtableApiException
     */
    public function getResponse(): bool {
        return $this->execute()['deleted'];
    }
}
<?php

namespace Guym4c\Airtable\Request;

use Guym4c\Airtable\Airtable;
use Guym4c\Airtable\AirtableApiException;
use Guym4c\Airtable\Record;

class RecordListRequest extends AbstractRequest {

    /** @var ?string */
    private $offset;

    /** @var ?Record[] */
    private $records = [];

    public function __construct(Airtable $airtable, string $table, array $query = []) {
        parent::__construct($airtable, $table, 'GET', '', $query, []);
    }

    /**
     * @return self
     * @throws AirtableApiException
     */
    public function getResponse(): self {
        $json = $this->execute();

        $this->offset = $json['offset'] ?? null;

        $this->records = [];
        foreach ($json['records'] as $record) {
            $this->records[] = new Record($this->airtable, $this->table, $record);
        }

        return $this;
    }

    /**
     * @return self
     * @throws AirtableApiException
     */
    public function nextPage(): self {

        if (empty($offset))
            return null;

        $this->options['query']['offset'] = $this->offset;
        return $this->getResponse();
    }

    /**
     * @return Record[]
     */
    public function getRecords(): array {
        return $this->records;
    }
}
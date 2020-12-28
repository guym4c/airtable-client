<?php

namespace Guym4c\Airtable;

class Loader {

    private Airtable $airtable;

    private string $id;

    public function __construct(Airtable $airtable, string $id) {
        $this->airtable = $airtable;
        $this->id = $id;
    }

    /**
     * @param string $targetTable
     * @return Record|null
     * @throws AirtableApiException
     */
    public function load(string $targetTable): ?Record {
        return $this->airtable->get($targetTable, $this->id);
    }

    /**
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }
}
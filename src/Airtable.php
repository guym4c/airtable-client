<?php

namespace Guym4c\Airtable;

use GuzzleHttp;
use GuzzleHttp\Exception\GuzzleException;

class Airtable {

    /** @var string */
    private $key;

    /** @var string */
    private $baseId;

    const API_ENDPOINT = 'https://api.airtable.com/v0';

    public function __construct(string $key, string $baseId) {

        $this->key = $key;
        $this->baseId = $baseId;
    }

    /**
     * Get a single record.
     *
     * @param string $table
     * @param string $id
     * @return Response|null
     */
    public function get(string $table, string $id): ?Response {

        try {
            return (new Request($table, 'GET', $id))->getResponse();
        } catch (GuzzleException $e) {
            return null;
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
}
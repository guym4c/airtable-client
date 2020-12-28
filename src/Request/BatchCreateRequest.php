<?php

namespace Guym4c\Airtable\Request;

use Guym4c\Airtable\Airtable;

class BatchCreateRequest extends AbstractBatchRequest {

    /**
     * BatchCreateRequest constructor.
     * @param Airtable $airtable
     * @param string $table
     * @param array $data
     */
    public function __construct(Airtable $airtable, string $table, array $data = []) {
        parent::__construct($airtable, $table, 'POST', '', [], self::createBody($data));
    }

    private static function createBody(array $records): array {
        $data = [];
        foreach ($records as $record) {
            $data[] = ['fields' => $record];
        }
        return ['records' => $data];
    }
}
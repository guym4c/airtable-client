<?php

namespace Guym4c\Airtable\Request;

use Guym4c\Airtable\Airtable;
use Guym4c\Airtable\Record;

class BatchUpdateRequest extends AbstractBatchRequest {

    /**
     * BatchUpdateRequest constructor.
     * @param Airtable $airtable
     * @param string $table
     * @param Record[] $records
     */
    public function __construct(Airtable $airtable, string $table, array $records = []) {
        parent::__construct($airtable, $table, 'PATCH', '', [], self::createBody($records));
    }

    /**
     * @param Record[] $records
     * @return array
     */
    private static function createBody(array $records): array {
        $data = [];
        foreach ($records as $record) {
            $data[] = [
                'id' => $record->getId(),
                'fields' => $record->getUpdatedFields(),
            ];
        }
        return ['records' => $data];
    }
}
<?php

namespace Guym4c\Airtable;

use JsonSerializable;

class Sort implements JsonSerializable {

    private string $field;

    private string $direction;

    /**
     * Sort constructor.
     * @param string $field
     * @param string $direction asc or desc
     */
    public function __construct(string $field, string $direction = 'asc') {
        $this->field = $field;
        $this->direction = $direction;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array {
        return [
            'field' => $this->field,
            'direction' => $this->direction,
        ];
    }
}
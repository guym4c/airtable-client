<?php

namespace Guym4c\Airtable;

use JsonSerializable;

class Sort implements JsonSerializable {

    /** @var string */
    private $field;

    /** @var string */
    private $direction;

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
<?php

namespace Guym4c\Airtable;

use DateTime;

class Record {

    /** @var Airtable */
    private $airtable;

    /** @var string */
    private $table;

    /** @var string */
    private $id;

    /** @var array */
    private $data;

    /** @var DateTime */
    private $timestamp;

    public function __construct(Airtable $airtable, string $table, array $json) {
        $this->airtable = $airtable;
        $this->table = $table;
        $this->id = $json['id'];
        $this->data = $json['fields'];
        $this->timestamp = strtotime($json['createdTime']) ?? new DateTime();
    }

    public function __get(string $property) {
        return $this->get($property);
    }

    public function load(string $property, string $targetTable) {
        return $this->get($property, $targetTable);
    }

    private function get(string $property, ?string $targetTable = null) {

        // find if exists
        if (empty($this->data[$property]) &&
            !array_key_exists($property, $this->data))
            return null;

        // retrieve from data
        $field = $this->data[$property];

        // check if is relation
        if (is_array($field) &&
            $this->isRelation($field[0])) {

            if (count($field) == 1) {
                return $this->attemptLoad($field[0], $targetTable);
            } else {
                $records = [];
                foreach ($field as $recordId) {
                    $records[] = $this->attemptLoad($recordId, $targetTable);
                }
                return $records;
            }
        }

        return $field;
    }

    private function attemptLoad(string $recordId, ?string $targetTable = null) {

        if (empty($targetTable))
            return new Loader($this->airtable, $recordId);

        try {
            return $this->airtable->get($targetTable, $recordId);
        } catch (AirtableApiException $e) {
            return new Loader($this->airtable, $recordId);
        }
    }

    public function __set(string $property, $value): void {

        // check for a Record object
        if ($value instanceof self)
            $this->{$property} = [$value->getId()];

        if (is_array($value) &&
            !empty($value) &&
            $value[0] instanceof self) {

            $this->{$property} = [];
            foreach ($value as $record) {

                /** @var $record self */
                $this->{$property}[] = $record->getId();
            }
        } else {
            $this->{$property} = $value;
        }
    }

    private function isRelation(string $s): bool {
        return preg_match('/^rec[A-Za-z0-9]{14}$/', $s) === 1;
    }

    public function __isset(string $property): bool {
        return array_key_exists($property, $this->data);
    }

    public function __unset(string $property): void {
        unset($this->data[$property]);
    }

    /**
     * @return string
     */
    public function getTable(): string {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getData(): array {
        return $this->data;
    }

    /**
     * @return DateTime
     */
    public function getTimestamp(): DateTime {
        return $this->timestamp;
    }
}
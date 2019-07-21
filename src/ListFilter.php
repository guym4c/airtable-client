<?php


namespace Guym4c\Airtable;

use JsonSerializable;

class ListFilter implements JsonSerializable {

    /** @var ?string[] */
    private $fields;

    /** @var ?string */
    private $formula;

    /** @var ?int */
    private $maxRecords;

    /** @var ?int */
    private $pageSize;

    /** @var ?Sort[] */
    private $sorting;

    /** @var ?string */
    private $view;

    /**
     * @param string[] $fields
     * @return self
     */
    public function setFields(array $fields): self {
        $this->fields = $fields;
        return $this;
    }

    /**
     * @param string $formula
     * @return self
     */
    public function setFormula(string $formula): self {
        $this->formula = $formula;
        return $this;
    }

    /**
     * @param int $maxRecords
     * @return self
     */
    public function setMaxRecords(int $maxRecords): self {
        $this->maxRecords = $maxRecords;
        return $this;
    }

    /**
     * @param int $pageSize
     * @return self
     */
    public function setPageSize(int $pageSize): self {
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * @param Sort $sort
     * @return self
     */
    public function addSort(Sort $sort): self {
        $this->sorting[] = $sort;
        return $this;
    }

    /**
     * @param string $view
     * @return self
     */
    public function setView(string $view): self {
        $this->view = $view;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array {

        $data = [];

        if (!empty($this->formula)) {
            $data['filterByFormula'] = $this->formula;
        }

        if (!empty($this->sorting)) {

            foreach ($this->sorting as $sort) {

                /** @var $sort Sort */
                $data['sort'][] = $sort->jsonSerialize();
            }
        }

        foreach (array_diff(array_keys(get_object_vars($this)), ['formula', 'sorting']) as $property) {

            if (!empty($this->{$property})) {
                $data[$property] = $this->{$property};
            }
        }

        return $data;
    }
}
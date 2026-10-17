<?php

namespace Adamski\Bundle\FetchTableBundle\Model\Filter;

readonly class Filter {
    public function __construct(
        private string $columnName,
        private mixed  $value,
    ) {}

    public function getColumnName(): string {
        return $this->columnName;
    }

    public function getValue(): mixed {
        return $this->value;
    }
}

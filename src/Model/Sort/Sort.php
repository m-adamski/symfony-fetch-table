<?php

namespace Adamski\Bundle\FetchTableBundle\Model\Sort;

readonly class Sort {
    public function __construct(
        private string    $column,
        private Direction $direction,
    ) {}

    public function getColumn(): string {
        return $this->column;
    }

    public function getDirection(): Direction {
        return $this->direction;
    }
}

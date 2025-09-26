<?php

namespace Adamski\Bundle\FetchTableBundle\Model\Pagination;

readonly class Pagination {
    public function __construct(
        private int $page,
        private int $size,
    ) {}

    public function getPage(): int {
        return $this->page;
    }

    public function getSize(): int {
        return $this->size;
    }
}

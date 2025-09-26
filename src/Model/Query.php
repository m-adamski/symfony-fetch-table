<?php

namespace Adamski\Bundle\FetchTableBundle\Model;

use Adamski\Bundle\FetchTableBundle\Model\Pagination\Pagination;
use Adamski\Bundle\FetchTableBundle\Model\Sort\Sort;

class Query {
    public function __construct(
        private ?string     $search = null,
        private ?Sort       $sort = null,
        private ?Pagination $pagination = null,
    ) {}

    public function getSearch(): ?string {
        return $this->search;
    }

    public function setSearch(?string $search): Query {
        $this->search = $search;
        return $this;
    }

    public function getSort(): ?Sort {
        return $this->sort;
    }

    public function setSort(?Sort $sort): Query {
        $this->sort = $sort;
        return $this;
    }

    public function getPagination(): ?Pagination {
        return $this->pagination;
    }

    public function setPagination(?Pagination $pagination): Query {
        $this->pagination = $pagination;
        return $this;
    }
}

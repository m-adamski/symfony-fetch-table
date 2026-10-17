<?php

namespace Adamski\Bundle\FetchTableBundle\Model;

use Adamski\Bundle\FetchTableBundle\Model\Filter\Filter;
use Adamski\Bundle\FetchTableBundle\Model\Pagination\Pagination;
use Adamski\Bundle\FetchTableBundle\Model\Sort\Sort;

class Query {
    public function __construct(
        private array       $filters = [],
        private ?string     $searchQuery = null,
        private ?Sort       $sort = null,
        private ?Pagination $pagination = null,
    ) {}

    /**
     * @return Filter[]
     */
    public function getFilters(): array {
        return $this->filters;
    }

    /**
     * @param Filter[] $filters
     * @return $this
     */
    public function setFilters(array $filters): Query {
        $this->filters = $filters;
        return $this;
    }

    public function addFilter(Filter $filter): Query {
        $this->filters[] = $filter;
        return $this;
    }

    public function getSearchQuery(): ?string {
        return $this->searchQuery;
    }

    public function setSearchQuery(?string $searchQuery): Query {
        $this->searchQuery = $searchQuery;
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

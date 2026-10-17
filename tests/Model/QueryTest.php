<?php

namespace Adamski\Bundle\FetchTableBundleTests\Model;

use Adamski\Bundle\FetchTableBundle\Model\Filter\Filter;
use Adamski\Bundle\FetchTableBundle\Model\Pagination\Pagination;
use Adamski\Bundle\FetchTableBundle\Model\Query;
use Adamski\Bundle\FetchTableBundle\Model\Sort\Direction;
use Adamski\Bundle\FetchTableBundle\Model\Sort\Sort;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase {
    public function testDefaultValues(): void {
        $query = new Query();

        $this->assertEquals([], $query->getFilters());
        $this->assertNull($query->getSearchQuery());
        $this->assertNull($query->getSort());
        $this->assertNull($query->getPagination());
    }

    public function testSetAndGetFilters(): void {
        $query = new Query();
        $filter1 = new Filter("status", "active");
        $filter2 = new Filter("role", "admin");

        $query->setFilters([$filter1, $filter2]);

        $this->assertSame([$filter1, $filter2], $query->getFilters());
    }

    public function testAddFilter(): void {
        $query = new Query();
        $filter1 = new Filter("status", "active");
        $filter2 = new Filter("category", "books");

        $query->addFilter($filter1);
        $query->addFilter($filter2);

        $this->assertSame([$filter1, $filter2], $query->getFilters());
    }

    public function testSetAndGetSearchQuery(): void {
        $query = new Query();
        $query->setSearchQuery("term");

        $this->assertEquals("term", $query->getSearchQuery());
    }

    public function testSetAndGetSort(): void {
        $query = new Query();
        $sort = new Sort("name", Direction::DESC);
        $query->setSort($sort);

        $this->assertSame($sort, $query->getSort());
    }

    public function testSetAndGetPagination(): void {
        $query = new Query();
        $pagination = new Pagination(2, 50);
        $query->setPagination($pagination);

        $this->assertSame($pagination, $query->getPagination());
    }

    public function testConstructorInitialization(): void {
        $filter = new Filter("status", "active");
        $sort = new Sort("name", Direction::ASC);
        $pagination = new Pagination(1, 10);

        $query = new Query([$filter], "search", $sort, $pagination);

        $this->assertSame([$filter], $query->getFilters());
        $this->assertEquals("search", $query->getSearchQuery());
        $this->assertSame($sort, $query->getSort());
        $this->assertSame($pagination, $query->getPagination());
    }
}

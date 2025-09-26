<?php

namespace Adamski\Bundle\FetchTableBundleTests\Adapter;

use Adamski\Bundle\FetchTableBundle\Adapter\ArrayAdapter;
use Adamski\Bundle\FetchTableBundle\Column\TextColumn;
use Adamski\Bundle\FetchTableBundle\Model\Column;
use Adamski\Bundle\FetchTableBundle\Model\Pagination\Pagination;
use Adamski\Bundle\FetchTableBundle\Model\Query;
use Adamski\Bundle\FetchTableBundle\Model\Result;
use Adamski\Bundle\FetchTableBundle\Model\Sort\Direction;
use Adamski\Bundle\FetchTableBundle\Model\Sort\Sort;
use PHPUnit\Framework\TestCase;

class ArrayAdapterTest extends TestCase {
    private ArrayAdapter $arrayAdapter;
    private array $columns = [];

    protected function setUp(): void {
        $this->arrayAdapter = new ArrayAdapter();
        $this->arrayAdapter->setConfig([
            "data" => [
                ["name" => "John Doe", "emailAddress" => "john.doe@example.com", "age" => 32, "city" => "New York"],
                ["name" => "Michael Brown", "emailAddress" => "michael.brown@example.com", "age" => 45, "city" => "Chicago"],
                ["name" => "Emily Davis", "emailAddress" => "emily.davis@example.com", "age" => 31, "city" => "Houston"],
                ["name" => "David Wilson", "emailAddress" => "david.wilson@example.com", "age" => 37, "city" => "Phoenix"],
                //                ["name" => "Jane Smith", "emailAddress" => "jane.smith@example.com", "age" => 28, "city" => "Los Angeles"],
                //                ["name" => "Sarah Johnson", "emailAddress" => "sarah.johnson@example.com", "age" => 29, "city" => "Philadelphia"],
                //                ["name" => "Chris Lee", "emailAddress" => "chris.lee@example.com", "age" => 33, "city" => "San Antonio"],
                //                ["name" => "Amanda Clark", "emailAddress" => "amanda.clark@example.com", "age" => 35, "city" => "San Diego"],
                //                ["name" => "Robert Martin", "emailAddress" => "robert.martin@example.com", "age" => 42, "city" => "Dallas"],
                //                ["name" => "Linda Thomas", "emailAddress" => "linda.thomas@example.com", "age" => 39, "city" => "San Jose"],
                //                ["name" => "James Moore", "emailAddress" => "james.moore@example.com", "age" => 44, "city" => "Austin"],
                //                ["name" => "Patricia Jackson", "emailAddress" => "patricia.jackson@example.com", "age" => 36, "city" => "Jacksonville"],
                //                ["name" => "Kevin White", "emailAddress" => "kevin.white@example.com", "age" => 30, "city" => "Fort Worth"],
                //                ["name" => "Laura Harris", "emailAddress" => "laura.harris@example.com", "age" => 34, "city" => "Columbus"],
                //                ["name" => "Brian Lewis", "emailAddress" => "brian.lewis@example.com", "age" => 41, "city" => "San Francisco"],
                //                ["name" => "Megan Young", "emailAddress" => "megan.young@example.com", "age" => 27, "city" => "Charlotte"],
                //                ["name" => "Andrew King", "emailAddress" => "andrew.king@example.com", "age" => 38, "city" => "Indianapolis"],
                //                ["name" => "Melissa Wright", "emailAddress" => "melissa.wright@example.com", "age" => 32, "city" => "Seattle"],
                //                ["name" => "Steven Hall", "emailAddress" => "steven.hall@example.com", "age" => 40, "city" => "Denver"],
                //                ["name" => "Nicole Allen", "emailAddress" => "nicole.allen@example.com", "age" => 31, "city" => "Boston"],
                //                ["name" => "Thomas Scott", "emailAddress" => "thomas.scott@example.com", "age" => 43, "city" => "Portland"],
            ]
        ]);

        // Define columns
        $nameColumn = (new TextColumn())
            ->setConfig(["label" => "Name", "sortable" => true, "searchable" => true]);
        $emailColumn = (new TextColumn())
            ->setConfig(["label" => "Email Address", "sortable" => false, "searchable" => true]);
        $ageColumn = (new TextColumn())
            ->setConfig(["label" => "Age", "sortable" => true, "searchable" => true]);
        $cityColumn = (new TextColumn())
            ->setConfig(["label" => "City", "sortable" => true, "searchable" => false]);

        $this->columns = [
            "name"         => $nameColumn,
            "emailAddress" => $emailColumn,
            "age"          => $ageColumn,
            "city"         => $cityColumn
        ];
    }

    public function testFetchDataWithEmptyQuery(): void {
        $query = new Query();
        $fetchResult = $this->arrayAdapter->fetchData($query, $this->columns, []);

        $expectedResult = (new Result())
            ->setData([
                [new Column("name", "John Doe"), new Column("emailAddress", "john.doe@example.com"), new Column("age", 32), new Column("city", "New York")],
                [new Column("name", "Michael Brown"), new Column("emailAddress", "michael.brown@example.com"), new Column("age", 45), new Column("city", "Chicago")],
                [new Column("name", "Emily Davis"), new Column("emailAddress", "emily.davis@example.com"), new Column("age", 31), new Column("city", "Houston")],
                [new Column("name", "David Wilson"), new Column("emailAddress", "david.wilson@example.com"), new Column("age", 37), new Column("city", "Phoenix")],
            ]);

        $this->assertEquals($expectedResult, $fetchResult);
    }

    public function testFetchDataWithSortByNameQuery(): void {
        $query = (new Query())->setSort(new Sort("name", Direction::ASC));
        $fetchResult = $this->arrayAdapter->fetchData($query, $this->columns, []);

        $expectedResult = (new Result())
            ->setData([
                [new Column("name", "David Wilson"), new Column("emailAddress", "david.wilson@example.com"), new Column("age", 37), new Column("city", "Phoenix")],
                [new Column("name", "Emily Davis"), new Column("emailAddress", "emily.davis@example.com"), new Column("age", 31), new Column("city", "Houston")],
                [new Column("name", "John Doe"), new Column("emailAddress", "john.doe@example.com"), new Column("age", 32), new Column("city", "New York")],
                [new Column("name", "Michael Brown"), new Column("emailAddress", "michael.brown@example.com"), new Column("age", 45), new Column("city", "Chicago")],
            ]);

        $this->assertEquals($expectedResult, $fetchResult);
    }

    public function testFetchDataWithSortByAgeQuery(): void {
        $query = (new Query())->setSort(new Sort("age", Direction::DESC));
        $fetchResult = $this->arrayAdapter->fetchData($query, $this->columns, []);

        $expectedResult = (new Result())
            ->setData([
                [new Column("name", "Michael Brown"), new Column("emailAddress", "michael.brown@example.com"), new Column("age", 45), new Column("city", "Chicago")],
                [new Column("name", "David Wilson"), new Column("emailAddress", "david.wilson@example.com"), new Column("age", 37), new Column("city", "Phoenix")],
                [new Column("name", "John Doe"), new Column("emailAddress", "john.doe@example.com"), new Column("age", 32), new Column("city", "New York")],
                [new Column("name", "Emily Davis"), new Column("emailAddress", "emily.davis@example.com"), new Column("age", 31), new Column("city", "Houston")],
            ]);

        $this->assertEquals($expectedResult, $fetchResult);
    }

    public function testFetchDataWithSortByNotSortableColumnQuery(): void {
        $this->expectException(\RuntimeException::class);

        $query = (new Query())->setSort(new Sort("emailAddress", Direction::DESC));
        $fetchResult = $this->arrayAdapter->fetchData($query, $this->columns, []);
    }

    public function testFetchDataWithSearchQuery(): void {
        $query = (new Query())
            ->setSearch("dav");
        $fetchResult = $this->arrayAdapter->fetchData($query, $this->columns, []);

        $expectedResult = (new Result())
            ->setData([
                [new Column("name", "Emily Davis"), new Column("emailAddress", "emily.davis@example.com"), new Column("age", 31), new Column("city", "Houston")],
                [new Column("name", "David Wilson"), new Column("emailAddress", "david.wilson@example.com"), new Column("age", 37), new Column("city", "Phoenix")],
            ]);

        $this->assertEquals($expectedResult, $fetchResult);
    }

    public function testFetchDataWithPaginationQuery(): void {
        $query = (new Query())->setPagination(new Pagination(1, 2));
        $fetchResult = $this->arrayAdapter->fetchData($query, $this->columns, []);

        $expectedResult = (new Result())
            ->setPage(1)
            ->setPageSize(2)
            ->setTotalPages(2)
            ->setData([
                [new Column("name", "John Doe"), new Column("emailAddress", "john.doe@example.com"), new Column("age", 32), new Column("city", "New York")],
                [new Column("name", "Michael Brown"), new Column("emailAddress", "michael.brown@example.com"), new Column("age", 45), new Column("city", "Chicago")],
            ]);

        $this->assertEquals($expectedResult, $fetchResult);
    }
}

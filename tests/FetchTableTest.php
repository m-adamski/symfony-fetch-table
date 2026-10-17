<?php

namespace Adamski\Bundle\FetchTableBundleTests;

use Adamski\Bundle\FetchTableBundle\Adapter\AbstractAdapter;
use Adamski\Bundle\FetchTableBundle\DependencyInjection\InstanceStorage;
use Adamski\Bundle\FetchTableBundle\FetchTable;
use Adamski\Bundle\FetchTableBundle\Model\Filter\Filter;
use Adamski\Bundle\FetchTableBundle\Model\Query;
use Adamski\Bundle\FetchTableBundle\Model\Result;
use Adamski\Bundle\FetchTableBundle\Transformer\TransformerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class FetchTableTest extends TestCase {
    private FetchTable $fetchTable;
    private array $defaultConfig = [
        "ajaxURL"     => "#",
        "ajaxMethod"  => "GET",
        "ajaxHeaders" => [
            "Content-Type"     => "application/json",
            "X-Requested-With" => "XMLHttpRequest",
            "X-Requested-By"   => "fetch-table"
        ],
        "debug"       => false,
        "components"  => [
            "pagination" => [
                "active"         => false,
                "pageSize"       => 25,
                "availableSizes" => [
                    0 => 10,
                    1 => 25,
                    2 => 50,
                    3 => 100
                ],
                "style"          => "standard",
                "description"    => [
                    "active" => true
                ]
            ],
            "search"     => [
                "active" => false
            ],
            "filter"     => [
                "active" => false
            ]
        ]
    ];

    protected function setUp(): void {
        $instanceStorage = $this->createStub(InstanceStorage::class);
        $transformerMock = $this->createStub(TransformerInterface::class);

        // Create an instance of FetchTable
        $this->fetchTable = new FetchTable("#example", $instanceStorage, $transformerMock);
        $this->fetchTable->setConfig($this->defaultConfig);
    }

    public function testGetConfigReturnsEntireDefaultConfig(): void {
        $config = $this->fetchTable->getConfig();

        $this->assertEquals($this->defaultConfig, $config);
    }

    public function testGetConfigWithValidPropertyPathReturnsValue(): void {
        $configPaginationStatus = $this->fetchTable->getConfig("[components][pagination][active]");

        $this->assertFalse($configPaginationStatus);
    }

    public function testGetConfigWithInvalidPropertyPathReturnsNull(): void {
        $configPaginationStatus = $this->fetchTable->getConfig("[components][pagination][invalid]");

        $this->assertNull($configPaginationStatus);
    }

    public function testOverwriteConfig(): void {
        $this->fetchTable->setConfig([
            "ajaxURL"    => "#",
            "components" => [
                "pagination" => [
                    "active" => true,
                ]
            ]
        ]);

        $configPaginationStatus = $this->fetchTable->getConfig("[components][pagination][active]");

        $this->assertTrue($configPaginationStatus);
    }

    public function testSetFilterConfigValid(): void {
        $this->fetchTable->setConfig([
            "ajaxURL"    => "#",
            "components" => [
                "filter" => [
                    "active"   => true,
                    "allLabel" => "All items",
                    "filters"  => [
                        [
                            "columnName" => "status",
                            "label"      => "Status",
                            "type"       => "button",
                            "values"     => ["active", "inactive"],
                        ],
                        [
                            "columnName" => "role",
                            "type"       => "select",
                            "values"     => ["admin", "user"],
                        ],
                    ],
                ],
            ],
        ]);

        $filterConfig = $this->fetchTable->getConfig("[components][filter]");

        $this->assertTrue($filterConfig["active"]);
        $this->assertEquals("All items", $filterConfig["allLabel"]);
        $this->assertCount(2, $filterConfig["filters"]);
        $this->assertEquals("status", $filterConfig["filters"][0]["columnName"]);
        $this->assertEquals("Status", $filterConfig["filters"][0]["label"]);
        $this->assertEquals("button", $filterConfig["filters"][0]["type"]);
        $this->assertEquals(["active", "inactive"], $filterConfig["filters"][0]["values"]);
    }

    public function testSetFilterConfigWithInvalidFiltersThrowsException(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains("The option 'filters' must be a list of filter objects.");

        $this->fetchTable->setConfig([
            "ajaxURL"    => "#",
            "components" => [
                "filter" => [
                    "filters" => [
                        "assocKey" => [
                            "columnName" => "status",
                            "type"       => "button",
                            "values"     => ["active"],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testSetFilterConfigWithInvalidFilterTypeThrowsException(): void {
        $this->expectException(InvalidOptionsException::class);

        $this->fetchTable->setConfig([
            "ajaxURL"    => "#",
            "components" => [
                "filter" => [
                    "filters" => [
                        [
                            "columnName" => "status",
                            "type"       => "checkbox", // invalid type
                            "values"     => ["active"],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testSetFilterConfigWithMissingRequiredOptionThrowsException(): void {
        $this->expectException(MissingOptionsException::class);

        $this->fetchTable->setConfig([
            "ajaxURL"    => "#",
            "components" => [
                "filter" => [
                    "filters" => [
                        [
                            "columnName" => "status",
                            // missing type and values
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testSetFilterElementsConfig(): void {
        $this->fetchTable->setConfig([
            "ajaxURL"  => "#",
            "elements" => [
                "filter" => [
                    "container" => [
                        "className"  => "filter-container",
                        "attributes" => ["data-test" => "container"],
                    ],
                    "item"      => [
                        "container" => ["className" => "filter-item"],
                        "label"     => ["className" => "filter-label"],
                        "select"    => [
                            "select" => ["className" => "filter-select"],
                            "option" => ["className" => "filter-option"],
                        ],
                        "button"    => [
                            "container" => ["className" => "filter-btn-group"],
                            "button"    => [
                                "default" => ["className" => "btn btn-secondary"],
                                "active"  => ["className" => "btn btn-primary"],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $filterElements = $this->fetchTable->getConfig("[elements][filter]");

        $this->assertEquals("filter-container", $filterElements["container"]["className"]);
        $this->assertEquals(["data-test" => "container"], $filterElements["container"]["attributes"]);
        $this->assertEquals("filter-item", $filterElements["item"]["container"]["className"]);
        $this->assertEquals("btn btn-primary", $filterElements["item"]["button"]["button"]["active"]["className"]);
    }

    public function testHandleRequestWithFilterArrayData(): void {
        $adapter = $this->createMock(AbstractAdapter::class);
        $adapter->expects($this->once())
            ->method("fetchData")
            ->with($this->callback(function (Query $query) {
                $filters = $query->getFilters();
                $this->assertCount(1, $filters);
                $this->assertInstanceOf(Filter::class, $filters[0]);
                $this->assertEquals("status", $filters[0]->getColumnName());
                $this->assertEquals("active", $filters[0]->getValue());
                return true;
            }))
            ->willReturn(new Result()->setData([])->setTotalRecords(0));

        $this->fetchTable->setAdapter($adapter);

        $request = new Request(["filter" => [["columnName" => "status", "value" => "active"]]], [], [], [], [], [
            "HTTP_X_REQUESTED_BY" => "fetch-table",
            "REQUEST_METHOD"      => "GET",
        ]);

        $response = $this->fetchTable->handleRequest($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testHandleRequestWithFilterStringData(): void {
        $adapter = $this->createMock(AbstractAdapter::class);
        $adapter->expects($this->once())
            ->method("fetchData")
            ->with($this->callback(function (Query $query) {
                $filters = $query->getFilters();
                $this->assertCount(2, $filters);
                $this->assertEquals("status", $filters[0]->getColumnName());
                $this->assertEquals("active", $filters[0]->getValue());
                $this->assertEquals("role", $filters[1]->getColumnName());
                $this->assertEquals("admin", $filters[1]->getValue());
                return true;
            }))
            ->willReturn(new Result()->setData([])->setTotalRecords(0));

        $this->fetchTable->setAdapter($adapter);

        $request = new Request(["filter" => ["status=active", "role=admin"]], [], [], [], [], [
            "HTTP_X_REQUESTED_BY" => "fetch-table",
            "REQUEST_METHOD"      => "GET",
        ]);

        $response = $this->fetchTable->handleRequest($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}

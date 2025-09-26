<?php

namespace Adamski\Bundle\FetchTableBundleTests;

use Adamski\Bundle\FetchTableBundle\DependencyInjection\InstanceStorage;
use Adamski\Bundle\FetchTableBundle\FetchTable;
use PHPUnit\Framework\TestCase;

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
            ],
            "search"     => [
                "active" => false
            ]
        ]
    ];

    protected function setUp(): void {
        $instanceStorage = $this->createMock(InstanceStorage::class);

        // Create an instance of FetchTable
        $this->fetchTable = new FetchTable("#example", $instanceStorage);
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
}

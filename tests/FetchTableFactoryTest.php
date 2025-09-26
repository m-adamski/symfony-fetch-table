<?php

namespace Adamski\Bundle\FetchTableBundleTests;

use Adamski\Bundle\FetchTableBundle\DependencyInjection\InstanceStorage;
use Adamski\Bundle\FetchTableBundle\FetchTable;
use Adamski\Bundle\FetchTableBundle\FetchTableFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class FetchTableFactoryTest extends TestCase {
    public function testCreateReturnsFetchTableInstance(): void {
        $instanceStorage = $this->createMock(InstanceStorage::class);
        $factory = new FetchTableFactory($instanceStorage);

        $fetchTable = $factory->create("#example", [
            "ajaxURL" => "#"
        ]);

        $this->assertInstanceOf(FetchTable::class, $fetchTable);
    }

    public function testCreateThrowsMissingOptionsException(): void {
        $this->expectException(MissingOptionsException::class);

        $instanceStorage = $this->createMock(InstanceStorage::class);
        $factory = new FetchTableFactory($instanceStorage);

        $fetchTable = $factory->create("#example", []);
    }
}

<?php

namespace Adamski\Bundle\FetchTableBundleTests;

use Adamski\Bundle\FetchTableBundle\DependencyInjection\InstanceStorage;
use Adamski\Bundle\FetchTableBundle\FetchTable;
use Adamski\Bundle\FetchTableBundle\FetchTableFactory;
use Adamski\Bundle\FetchTableBundle\Transformer\TransformerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class FetchTableFactoryTest extends TestCase {
    public function testCreateReturnsFetchTableInstance(): void {
        $instanceStorage = $this->createMock(InstanceStorage::class);
        $transformerMock = $this->createMock(TransformerInterface::class);
        $factory = new FetchTableFactory($instanceStorage, $transformerMock);

        $fetchTable = $factory->create("#example", [
            "ajaxURL" => "#"
        ]);

        $this->assertInstanceOf(FetchTable::class, $fetchTable);
    }

    public function testCreateThrowsMissingOptionsException(): void {
        $this->expectException(MissingOptionsException::class);

        $instanceStorage = $this->createMock(InstanceStorage::class);
        $transformerMock = $this->createMock(TransformerInterface::class);
        $factory = new FetchTableFactory($instanceStorage, $transformerMock);

        $fetchTable = $factory->create("#example", []);
    }
}

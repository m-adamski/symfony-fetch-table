<?php

namespace Adamski\Bundle\FetchTableBundleTests\Adapter;

use Adamski\Bundle\FetchTableBundle\Adapter\AbstractAdapter;
use Adamski\Bundle\FetchTableBundle\Model\Query;
use Adamski\Bundle\FetchTableBundle\Model\Result;
use Adamski\Bundle\FetchTableBundle\Transformer\TransformerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractAdapterTest extends TestCase {
    private AbstractAdapter $abstractAdapter;
    private array $defaultConfig = [
        "adapter" => "abstract",
    ];

    public function setUp(): void {
        $this->abstractAdapter = new class extends AbstractAdapter {
            protected function validateConfig(OptionsResolver $resolver): void {
                $resolver->define("adapter")->allowedTypes("string")->required();
            }


            public function fetchData(Query $query, TransformerInterface $transformer, array $columns, array $config): Result {
                return new Result();
            }
        };

        $this->abstractAdapter->setConfig($this->defaultConfig);
    }

    public function testGetConfigReturnsEntireDefaultConfig(): void {
        $config = $this->abstractAdapter->getConfig();

        $this->assertEquals($this->defaultConfig, $config);
    }

    public function testGetConfigWithValidPropertyPathReturnsValue(): void {
        $configAdapter = $this->abstractAdapter->getConfig("[adapter]");

        $this->assertEquals("abstract", $configAdapter);
    }

    public function testGetConfigWithInvalidPropertyPathReturnsNull(): void {
        $configAdapter = $this->abstractAdapter->getConfig("[invalid]");

        $this->assertNull($configAdapter);
    }
}

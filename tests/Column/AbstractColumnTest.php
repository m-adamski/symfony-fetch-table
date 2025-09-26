<?php

namespace Adamski\Bundle\FetchTableBundleTests\Column;

use Adamski\Bundle\FetchTableBundle\Column\AbstractColumn;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractColumnTest extends TestCase {
    private AbstractColumn $abstractColumn;
    private array $defaultConfig = [
        "label"      => "Test",
        "type"       => "text",
        "className"  => null,
        "sortable"   => false,
        "searchable" => false,
        "mapped"     => true,
        "test"       => "testValue",
    ];

    public function setUp(): void {
        $this->abstractColumn = new class extends AbstractColumn {
            protected function validateConfig(OptionsResolver $resolver): void {
                $resolver->define("test")->allowedTypes("string")->required();
            }

            public function render(mixed $value): string|int|float {
                return "";
            }
        };

        $this->abstractColumn->setConfig($this->defaultConfig);
    }

    public function testGetConfigReturnsEntireConfig(): void {
        $config = $this->abstractColumn->getConfig();

        $this->assertEquals($this->defaultConfig, $config);
    }

    public function testGetConfigWithValidPropertyPathReturnsValue(): void {
        $configTest = $this->abstractColumn->getConfig("[test]");

        $this->assertEquals("testValue", $configTest);
    }

    public function testGetConfigWithInvalidPropertyPathReturnsNull(): void {
        $configTest = $this->abstractColumn->getConfig("[invalid]");

        $this->assertNull($configTest);
    }
}

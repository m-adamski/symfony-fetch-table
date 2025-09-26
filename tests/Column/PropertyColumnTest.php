<?php

namespace Adamski\Bundle\FetchTableBundleTests\Column;

use Adamski\Bundle\FetchTableBundle\Column\PropertyColumn;
use PHPUnit\Framework\TestCase;

class PropertyColumnTest extends TestCase {
    public function testRenderWithValidPropertyPath(): void {
        $propertyColumn = new PropertyColumn();
        $propertyColumn->setConfig([
            "label"    => "Property",
            "property" => "[property]"
        ]);

        $this->assertEquals("example", $propertyColumn->render(["property" => "example"]));
    }

    public function testRenderWithInvalidPropertyPath(): void {
        $propertyColumn = new PropertyColumn();
        $propertyColumn->setConfig([
            "label"        => "Property",
            "property"     => "[invalid]",
            "defaultValue" => "default",
        ]);

        $this->assertEquals("default", $propertyColumn->render(["property" => "example"]));
    }
}

<?php

namespace Adamski\Bundle\FetchTableBundleTests\Column;

use Adamski\Bundle\FetchTableBundle\Column\CallableColumn;
use PHPUnit\Framework\TestCase;

class CallableColumnTest extends TestCase {
    public function testRenderWithSimpleValue(): void {
        $callableColumn = new CallableColumn();
        $callableColumn->setConfig([
            "label"    => "Callable",
            "callable" => function (string $value) {
                return "Callable: $value";
            }
        ]);

        $this->assertEquals("Callable: example", $callableColumn->render("example"));
    }

    public function testRenderWithExpandedValue(): void {
        $callableColumn = new CallableColumn();
        $callableColumn->setConfig([
            "label"    => "Callable",
            "expanded" => true,
            "callable" => function ($value) {
                return "Callable: " . $value["name"] . " " . $value["surname"];
            }
        ]);

        $this->assertEquals("Callable: example example", $callableColumn->render(["name" => "example", "surname" => "example"]));
    }
}

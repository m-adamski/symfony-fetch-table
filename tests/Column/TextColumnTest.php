<?php

namespace Adamski\Bundle\FetchTableBundleTests\Column;

use Adamski\Bundle\FetchTableBundle\Column\TextColumn;
use PHPUnit\Framework\TestCase;

class TextColumnTest extends TestCase {
    public function testRender(): void {
        $textColumn = new TextColumn();
        $textColumn->setConfig([
            "label" => "Text",
        ]);

        $this->assertEquals("example", $textColumn->render("example"));
    }
}

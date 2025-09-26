<?php

namespace Adamski\Bundle\FetchTableBundleTests\Column;

use Adamski\Bundle\FetchTableBundle\Column\DateTimeColumn;
use PHPUnit\Framework\TestCase;

class DateTimeColumnTest extends TestCase {
    public function testRender(): void {
        $dateTimeColumn = new DateTimeColumn();
        $dateTimeColumn->setConfig([
            "label"  => "DateTime",
            "format" => "Y-m-d H:i:s",
        ]);

        $this->assertEquals("2024-01-01 00:00:00", $dateTimeColumn->render(new \DateTime("2024-01-01 00:00:00")));
    }
}

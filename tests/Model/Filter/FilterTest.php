<?php

namespace Adamski\Bundle\FetchTableBundleTests\Model\Filter;

use Adamski\Bundle\FetchTableBundle\Model\Filter\Filter;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase {
    public function testGettersReturnProvidedValues(): void {
        $filter = new Filter("status", "active");

        $this->assertEquals("status", $filter->getColumnName());
        $this->assertEquals("active", $filter->getValue());
    }

    public function testFilterSupportsVariousValueTypes(): void {
        $intFilter = new Filter("age", 25);
        $this->assertSame(25, $intFilter->getValue());

        $nullFilter = new Filter("deletedAt", null);
        $this->assertNull($nullFilter->getValue());

        $boolFilter = new Filter("isActive", true);
        $this->assertTrue($boolFilter->getValue());
    }
}

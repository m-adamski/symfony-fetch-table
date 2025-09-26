<?php

namespace Adamski\Bundle\FetchTableBundleTests\Column\Twig;

use Adamski\Bundle\FetchTableBundle\Column\Twig\TwigColumn;
use PHPUnit\Framework\TestCase;

class TwigColumnTest extends TestCase {
    public function testRender(): void {
        $twigMock = $this->createMock(\Twig\Environment::class);
        $twigMock->expects($this->once())->method("render")->willReturn("Example");

        $twigColumn = new TwigColumn($twigMock);
        $twigColumn->setConfig([
            "label"    => "Twig",
            "template" => "test.html.twig"
        ]);

        $this->assertEquals("Example", $twigColumn->render("example"));
    }
}

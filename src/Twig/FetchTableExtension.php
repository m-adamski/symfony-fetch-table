<?php

namespace Adamski\Bundle\FetchTableBundle\Twig;

use Adamski\Bundle\FetchTableBundle\FetchTable;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FetchTableExtension extends AbstractExtension {
    public function getFunctions(): array {
        return [
            new TwigFunction("fetch_table_config", $this->config(...)),
        ];
    }

    /**
     * Get table configuration in JSON format.
     *
     * @param FetchTable $fetchTable
     * @return string
     * @throws \JsonException
     */
    public function config(FetchTable $fetchTable): string {
        return json_encode(["selector" => $fetchTable->getSelector(), "config" => $fetchTable->getConfig()], JSON_THROW_ON_ERROR);
    }
}

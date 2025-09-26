<?php

namespace Adamski\Bundle\FetchTableBundle;

use Adamski\Bundle\FetchTableBundle\DependencyInjection\InstanceStorage;

readonly class FetchTableFactory {
    public function __construct(
        private InstanceStorage $instanceStorage,
    ) {}

    public function create(string $selector, array $config = []): FetchTable {
        $fetchTable = new FetchTable($selector, $this->instanceStorage);
        $fetchTable->setConfig($config);

        return $fetchTable;
    }
}

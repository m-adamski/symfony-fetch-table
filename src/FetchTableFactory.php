<?php

namespace Adamski\Bundle\FetchTableBundle;

use Adamski\Bundle\FetchTableBundle\DependencyInjection\InstanceStorage;
use Adamski\Bundle\FetchTableBundle\Transformer\TransformerInterface;

readonly class FetchTableFactory {
    public function __construct(
        private InstanceStorage      $instanceStorage,
        private TransformerInterface $transformer,
    ) {}

    public function create(string $selector, array $config = []): FetchTable {
        $fetchTable = new FetchTable($selector, $this->instanceStorage, $this->transformer);
        $fetchTable->setConfig($config);

        return $fetchTable;
    }
}

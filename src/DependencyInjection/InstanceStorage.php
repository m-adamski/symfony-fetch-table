<?php

namespace Adamski\Bundle\FetchTableBundle\DependencyInjection;

use Adamski\Bundle\FetchTableBundle\Adapter\AbstractAdapter;
use Adamski\Bundle\FetchTableBundle\Column\AbstractColumn;

readonly class InstanceStorage {
    public function __construct(
        private array $instances = []
    ) {}

    public function getAdapter(string $adapterClass): AbstractAdapter {
        return $this->getInstance($adapterClass, AbstractAdapter::class);
    }

    public function getColumn(string $columnClass): AbstractColumn {
        return $this->getInstance($columnClass, AbstractColumn::class);
    }

    private function getInstance(string $type, string $class): AbstractAdapter|AbstractColumn {
        if (isset($this->instances[$class]) && $this->instances[$class]->has($type)) {
            $instance = clone $this->instances[$class]->get($type);
        } elseif (class_exists($type)) {
            $instance = new $type();
        } else {
            throw new \InvalidArgumentException(sprintf('Could not resolve type "%s" to a service or class', $type));
        }

        if (!$instance instanceof $class) {
            throw new \InvalidArgumentException(sprintf('Class "%s" must implement/extend %s', $type, $class));
        }

        return $instance;
    }
}

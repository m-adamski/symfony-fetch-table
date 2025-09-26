<?php

namespace Adamski\Bundle\FetchTableBundle\Adapter;

use Adamski\Bundle\FetchTableBundle\Column\AbstractColumn;
use Adamski\Bundle\FetchTableBundle\Model\Column;
use Adamski\Bundle\FetchTableBundle\Model\Query;
use Adamski\Bundle\FetchTableBundle\Model\Result;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

abstract class AbstractAdapter {
    protected array $config = [];

    /**
     * Get adapter configuration or a specific property based on the provided path.
     * Returns null if the property is not found.
     *
     * @param string|null $propertyPath
     * @return mixed
     */
    public function getConfig(?string $propertyPath = null): mixed {
        $propertyAccessor = $this->getPropertyAccessor();

        if (null !== $propertyPath) {
            if (true === $propertyAccessor->isReadable($this->config, $propertyPath)) {
                return $propertyAccessor->getValue($this->config, $propertyPath);
            }

            return null;
        }

        return $this->config;
    }

    /**
     * Set adapter configuration.
     * It uses the OptionsResolver component to validate and resolve configuration.
     *
     * @param array $config
     * @return AbstractAdapter
     */
    public function setConfig(array $config): self {
        $configResolver = new OptionsResolver();
        $this->validateConfig($configResolver);
        $this->config = $configResolver->resolve($config);

        return $this;
    }

    /**
     * Convert the provided array with data to the array of Column objects.
     *
     * @param array $data
     * @param array $columns
     * @return array
     */
    protected function convertData(array $data, array $columns): array {
        $resultData = [];
        foreach ($data as $item) {
            $columnRow = [];

            /**
             * @var string         $columnName
             * @var AbstractColumn $column
             */
            foreach ($columns as $columnName => $column) {
                $isMapped = $column->getConfig("[mapped]");
                $isExpanded = $column->getConfig("[expanded]") ?? false;

                // Define property path and value
                $propertyPath = is_array($item) ? "[$columnName]" : $columnName;
                $propertyValue = $isMapped ? ($isExpanded ? $item : $this->getPropertyAccessor()->getValue($item, $propertyPath)) : ($isExpanded ? $item : null);

                $columnRow[] = new Column(
                    name: $columnName,
                    value: $column->render($propertyValue)
                );
            }

            $resultData[] = $columnRow;
        }

        return $resultData;
    }

    /**
     * Create and return the PropertyAccessor.
     *
     * @return PropertyAccessorInterface
     */
    protected function getPropertyAccessor(): PropertyAccessorInterface {
        return PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();
    }

    /**
     * Validate custom configuration of the adapter.
     * This method is called after the default configuration is resolved.
     * Use the OptionsResolver component to validate the configuration.
     *
     * @param OptionsResolver $resolver
     * @return void
     */
    protected abstract function validateConfig(OptionsResolver $resolver): void;

    /**
     * Retrieve data from the designated source.
     * This method is implemented by derived classes to fetch specific data sets.
     *
     * @param Query            $query
     * @param AbstractColumn[] $columns
     * @param array            $config
     * @return Result
     */
    public abstract function fetchData(Query $query, array $columns, array $config): Result;
}

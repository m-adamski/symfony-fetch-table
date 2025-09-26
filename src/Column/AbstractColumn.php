<?php

namespace Adamski\Bundle\FetchTableBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

abstract class AbstractColumn {
    private array $config = [];

    /**
     * Get the column configuration or a specific property based on the provided path.
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
     * Set the column configuration.
     * The configuration is validated using the OptionsResolver component.
     * The custom configuration is defined in the validateConfig() method.
     *
     * @param array $config
     * @return AbstractColumn
     */
    public function setConfig(array $config): self {
        $configResolver = new OptionsResolver();

        // Define options using the Symfony OptionsResolver component
        $configResolver->define("type")->allowedValues("text", "html")->default("text");
        $configResolver->define("label")->allowedTypes("string")->required();
        $configResolver->define("className")->allowedTypes("string", "null")->default(null);
        $configResolver->define("sortable")->allowedTypes("bool")->default(false);
        $configResolver->define("searchable")->allowedTypes("bool")->default(false);
        $configResolver->define("mapped")->allowedTypes("bool")->default(true);

        // Validate custom config
        $this->validateConfig($configResolver);

        $this->config = $configResolver->resolve($config);

        return $this;
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
     * Validate custom configuration of the column.
     * This method is called after the default configuration is resolved.
     * Use the OptionsResolver component to validate the configuration.
     *
     * @param OptionsResolver $resolver
     * @return void
     */
    abstract protected function validateConfig(OptionsResolver $resolver): void;

    /**
     * Render the column value.
     *
     * @param mixed $value
     * @return string|int|float
     */
    abstract public function render(mixed $value): string|int|float;
}

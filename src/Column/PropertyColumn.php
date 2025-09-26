<?php

namespace Adamski\Bundle\FetchTableBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

class PropertyColumn extends AbstractColumn {
    protected function validateConfig(OptionsResolver $resolver): void {
        $resolver->define("property")->allowedTypes("string")->required();
        $resolver->define("defaultValue")->allowedTypes("string", "int", "float")->default("");
        $resolver->define("expanded")->allowedTypes("bool")->default(true);
    }

    public function render(mixed $value): string|int|float {
        $property = $this->getConfig("[property]");
        $defaultValue = $this->getConfig("[defaultValue]");

        if (null === $value || !$this->getPropertyAccessor()->isReadable($value, $property)) {
            return $defaultValue;
        }

        return $this->getPropertyAccessor()->getValue($value, $property);
    }
}

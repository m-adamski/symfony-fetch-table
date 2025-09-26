<?php

namespace Adamski\Bundle\FetchTableBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeColumn extends AbstractColumn {
    protected function validateConfig(OptionsResolver $resolver): void {
        $resolver->define("format")->allowedTypes("string")->default("Y-m-d H:i:s");
    }

    public function render(mixed $value): string {
        $format = $this->getConfig("[format]");

        if (!$value instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException("Value must be an instance of DateTimeInterface");
        }

        return $value->format($format);
    }
}

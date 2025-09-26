<?php

namespace Adamski\Bundle\FetchTableBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

class TextColumn extends AbstractColumn {
    protected function validateConfig(OptionsResolver $resolver): void {
        // Nothing to validate
    }

    public function render(mixed $value): string {
        return (string)$value;
    }
}

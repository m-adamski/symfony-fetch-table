<?php

namespace Adamski\Bundle\FetchTableBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

class CallableColumn extends AbstractColumn {
    protected function validateConfig(OptionsResolver $resolver): void {
        $resolver->define("callable")->allowedTypes("callable")->required();
        $resolver->define("expanded")->allowedTypes("bool")->default(false);
    }

    public function render(mixed $value): string|int|float {
        $function = $this->getConfig("[callable]");

        return call_user_func($function, $value);
    }
}

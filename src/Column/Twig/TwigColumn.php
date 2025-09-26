<?php

namespace Adamski\Bundle\FetchTableBundle\Column\Twig;

use Adamski\Bundle\FetchTableBundle\Column\AbstractColumn;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

class TwigColumn extends AbstractColumn {
    public function __construct(
        private readonly ?Environment $twig = null,
    ) {
        if (null === $this->twig) {
            throw new \InvalidArgumentException("Twig is not available. Install symfony/twig-bundle to use the TwigColumn");
        }
    }

    protected function validateConfig(OptionsResolver $resolver): void {
        $resolver->define("template")->allowedTypes("string")->required();
        $resolver->define("expanded")->allowedTypes("bool")->default(false);

        // Override default type
        $resolver->setDefault("type", "html");
    }

    public function render(mixed $value): string {
        $template = $this->getConfig("[template]");

        return $this->twig->render($template, [
            "value" => $value,
        ]);
    }
}

<?php

namespace Adamski\Bundle\FetchTableBundle;

use Adamski\Bundle\FetchTableBundle\Adapter\AbstractAdapter;
use Adamski\Bundle\FetchTableBundle\Column\AbstractColumn;
use Adamski\Bundle\FetchTableBundle\DependencyInjection\FetchTableExtension;
use Adamski\Bundle\FetchTableBundle\DependencyInjection\InstanceStorage;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class FetchTableBundle extends AbstractBundle implements CompilerPassInterface {
    public function build(ContainerBuilder $container): void {
        parent::build($container);

        // Register the compiler pass
        $container->addCompilerPass($this);
    }

    public function getContainerExtension(): ?ExtensionInterface {
        return new FetchTableExtension();
    }

    /**
     * Compiler passes give an opportunity to manipulate other service definitions
     * that have been registered with the service container.
     * https://symfony.com/doc/current/service_container/compiler_passes.html#working-with-compiler-passes-in-bundles
     *
     * @param ContainerBuilder $container
     * @return void
     */
    public function process(ContainerBuilder $container): void {
        $container->getDefinition(InstanceStorage::class)
            ->setArguments([
                [
                    AbstractAdapter::class => $this->registerLocator($container, "adapter"),
                    AbstractColumn::class  => $this->registerLocator($container, "column"),
                ]
            ]);

        // Register the Twig extension if Twig is available
        if (true === $container->hasExtension("twig")) {
            // - register(...) creates one specific service definition in the container (by id and class) right now.
            // - registerForAutoconfiguration(...) declares a rule that will be applied later to many services that
            // are autoconfigured, based on their type (interface/attribute), not creating any service by itself.
            $container->register("m_adamski_fetch_table.twig.extension", Twig\FetchTableExtension::class)
                ->addTag("twig.extension");
        }
    }

    /**
     * Registers a service locator for the given base tag.
     *
     * @param ContainerBuilder $container
     * @param string           $baseTag
     * @return Definition
     */
    private function registerLocator(ContainerBuilder $container, string $baseTag): Definition {
        $types = [];
        foreach ($container->findTaggedServiceIds("m_adamski_fetch_table.{$baseTag}") as $serviceId => $tag) {
            $types[$serviceId] = new Reference($serviceId);
        }

        return $container
            ->register("m_adamski_fetch_table.{$baseTag}_locator", ServiceLocator::class)
            ->addTag("container.service_locator")
            ->setPublic(false)
            ->setArguments([$types]);
    }
}

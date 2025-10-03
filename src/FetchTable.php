<?php

namespace Adamski\Bundle\FetchTableBundle;

use Adamski\Bundle\FetchTableBundle\Adapter\AbstractAdapter;
use Adamski\Bundle\FetchTableBundle\Column\AbstractColumn;
use Adamski\Bundle\FetchTableBundle\DependencyInjection\InstanceStorage;
use Adamski\Bundle\FetchTableBundle\Model\Pagination\Pagination;
use Adamski\Bundle\FetchTableBundle\Model\Query;
use Adamski\Bundle\FetchTableBundle\Model\Sort\Direction;
use Adamski\Bundle\FetchTableBundle\Model\Sort\Sort;
use Adamski\Bundle\FetchTableBundle\Transformer\TransformerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class FetchTable {
    private array $config = [];
    private array $columns = [];
    private ?AbstractAdapter $adapter = null;

    public function __construct(
        private readonly string          $selector,
        private readonly InstanceStorage $instanceStorage,
        private TransformerInterface     $transformer,
    ) {}

    /**
     * Get HTML element selector.
     *
     * @return string
     */
    public function getSelector(): string {
        return $this->selector;
    }

    /**
     * Get full configuration or specific property value based on the provided path.
     * Returns null if the property is not found.
     *
     * @param string|null $propertyPath
     * @return mixed
     */
    public function getConfig(?string $propertyPath = null): mixed {

        // Return only options that are supported by the FetchTable JS library
        $config = array_merge_recursive($this->config, [
            "columns" => array_map(function (AbstractColumn $column) {
                return [
                    "type"       => $column->getConfig("[type]"),
                    "label"      => $column->getConfig("[label]"),
                    "className"  => $column->getConfig("[className]"),
                    "sortable"   => $column->getConfig("[sortable]"),
                    "searchable" => $column->getConfig("[searchable]"),
                ];
            }, $this->columns),
        ]);

        // Remove unnecessary elements from the array
        $config = $this->cleanArray($config);

        if (null !== $propertyPath) {
            $propertyAccessor = $this->getPropertyAccessor();

            if (true === $propertyAccessor->isReadable($config, $propertyPath)) {
                return $propertyAccessor->getValue($config, $propertyPath);
            }

            return null;
        }

        return $config;
    }

    /**
     * Set configuration.
     * It uses the OptionsResolver component to validate and resolve configuration.
     *
     * @param array $config
     * @return FetchTable
     */
    public function setConfig(array $config): self {
        $configResolver = new OptionsResolver();

        // Define options using the Symfony OptionsResolver component
        // https://symfony.com/doc/current/components/options_resolver.html
        $configResolver->define("ajaxURL")->allowedTypes("string")->required();
        $configResolver->define("ajaxMethod")->allowedValues("GET", "POST")->default("GET");
        $configResolver->define("ajaxHeaders")->options(function (OptionsResolver $resolver) {
            $resolver->define("Content-Type")->allowedTypes("string")->default("application/json");
            $resolver->define("X-Requested-With")->allowedTypes("string")->default("XMLHttpRequest");
            $resolver->define("X-Requested-By")->allowedTypes("string")->default("fetch-table");
        });
        $configResolver->define("debug")->allowedTypes("bool")->default(false);
        $configResolver->define("elements")->options(function (OptionsResolver $resolver) {

            // Elements > Container
            $resolver->define("container")->options(function (OptionsResolver $resolver) {
                $resolver->define("container")->options(function (OptionsResolver $resolver) {
                    $resolver->define("className")->allowedTypes("string");
                    $resolver->define("querySelector")->allowedTypes("string");
                    $resolver->define("attributes")->allowedTypes("array");
                });
                $resolver->define("header")->options(function (OptionsResolver $resolver) {
                    $resolver->define("className")->allowedTypes("string");
                    $resolver->define("querySelector")->allowedTypes("string");
                    $resolver->define("attributes")->allowedTypes("array");
                });
                $resolver->define("footer")->options(function (OptionsResolver $resolver) {
                    $resolver->define("className")->allowedTypes("string");
                    $resolver->define("querySelector")->allowedTypes("string");
                    $resolver->define("attributes")->allowedTypes("array");
                });
            });

            // Elements > Table
            $resolver->define("table")->options(function (OptionsResolver $resolver) {

                // Elements > Table > Table
                $resolver->define("table")->options(function (OptionsResolver $resolver) {
                    $resolver->define("className")->allowedTypes("string");
                    $resolver->define("attributes")->allowedTypes("array");
                });

                // Elements > Table > Head
                $resolver->define("tableHead")->options(function (OptionsResolver $resolver) {
                    $resolver->define("tableHead")->options(function (OptionsResolver $resolver) {
                        $resolver->define("className")->allowedTypes("string");
                        $resolver->define("attributes")->allowedTypes("array");
                    });
                    $resolver->define("tableRow")->options(function (OptionsResolver $resolver) {
                        $resolver->define("className")->allowedTypes("string");
                        $resolver->define("attributes")->allowedTypes("array");
                    });
                    $resolver->define("tableCell")->options(function (OptionsResolver $resolver) {
                        $resolver->define("className")->allowedTypes("string");
                        $resolver->define("attributes")->allowedTypes("array");
                    });
                });

                // Elements > Table > Body
                $resolver->define("tableBody")->options(function (OptionsResolver $resolver) {
                    $resolver->define("tableBody")->options(function (OptionsResolver $resolver) {
                        $resolver->define("className")->allowedTypes("string");
                        $resolver->define("attributes")->allowedTypes("array");
                    });
                    $resolver->define("tableRow")->options(function (OptionsResolver $resolver) {
                        $resolver->define("className")->allowedTypes("string");
                        $resolver->define("attributes")->allowedTypes("array");
                    });
                    $resolver->define("tableCell")->options(function (OptionsResolver $resolver) {
                        $resolver->define("className")->allowedTypes("string");
                        $resolver->define("attributes")->allowedTypes("array");
                    });
                });

                // Elements > Table > Placeholder
                $resolver->define("placeholder")->options(function (OptionsResolver $resolver) {
                    $resolver->define("className")->allowedTypes("string");
                    $resolver->define("innerHTML")->allowedTypes("string");
                    $resolver->define("attributes")->allowedTypes("array");
                });
            });

            // Elements > Pagination
            $resolver->define("pagination")->options(function (OptionsResolver $resolver) {

                // Elements > Pagination > Container
                $resolver->define("container")->options(function (OptionsResolver $resolver) {
                    $resolver->define("className")->allowedTypes("string");
                    $resolver->define("attributes")->allowedTypes("array");
                });

                // Elements > Pagination > Button
                $resolver->define("button")->options(function (OptionsResolver $resolver) {
                    $resolver->define("primary")->options(function (OptionsResolver $resolver) {
                        $resolver->define("className")->allowedTypes("string");
                        $resolver->define("attributes")->allowedTypes("array");
                    });
                    $resolver->define("active")->options(function (OptionsResolver $resolver) {
                        $resolver->define("className")->allowedTypes("string");
                        $resolver->define("attributes")->allowedTypes("array");
                    });
                    $resolver->define("ellipsis")->options(function (OptionsResolver $resolver) {
                        $resolver->define("className")->allowedTypes("string");
                        $resolver->define("innerHTML")->allowedTypes("string");
                        $resolver->define("attributes")->allowedTypes("array");
                    });
                    $resolver->define("previous")->options(function (OptionsResolver $resolver) {
                        $resolver->define("className")->allowedTypes("string");
                        $resolver->define("innerHTML")->allowedTypes("string");
                        $resolver->define("attributes")->allowedTypes("array");
                    });
                    $resolver->define("next")->options(function (OptionsResolver $resolver) {
                        $resolver->define("className")->allowedTypes("string");
                        $resolver->define("innerHTML")->allowedTypes("string");
                        $resolver->define("attributes")->allowedTypes("array");
                    });
                });

                // Elements > Pagination > Size Selector
                $resolver->define("sizeSelector")->options(function (OptionsResolver $resolver) {
                    $resolver->define("container")->options(function (OptionsResolver $resolver) {
                        $resolver->define("className")->allowedTypes("string");
                        $resolver->define("attributes")->allowedTypes("array");
                    });
                    $resolver->define("select")->options(function (OptionsResolver $resolver) {
                        $resolver->define("className")->allowedTypes("string");
                        $resolver->define("attributes")->allowedTypes("array");
                    });
                    $resolver->define("option")->options(function (OptionsResolver $resolver) {
                        $resolver->define("className")->allowedTypes("string");
                        $resolver->define("attributes")->allowedTypes("array");
                    });
                });
            });

            // Elements > Search
            $resolver->define("search")->options(function (OptionsResolver $resolver) {
                $resolver->define("container")->options(function (OptionsResolver $resolver) {
                    $resolver->define("className")->allowedTypes("string");
                    $resolver->define("attributes")->allowedTypes("array");
                });
                $resolver->define("input")->options(function (OptionsResolver $resolver) {
                    $resolver->define("className")->allowedTypes("string");
                    $resolver->define("attributes")->allowedTypes("array");
                });
            });
        });
        $configResolver->define("components")->options(function (OptionsResolver $resolver) {

            // Components > Pagination
            $resolver->define("pagination")->options(function (OptionsResolver $resolver) {
                $resolver->define("active")->allowedTypes("boolean")->default(false);
                $resolver->define("pageSize")->allowedTypes("int")->default(25);
                $resolver->define("availableSizes")->allowedTypes("array")->default([10, 25, 50, 100]);
                $resolver->define("style")->allowedValues("standard", "simple")->default("standard");
            });

            // Components > Search
            $resolver->define("search")->options(function (OptionsResolver $resolver) {
                $resolver->define("active")->allowedTypes("boolean")->default(false);
            });
        });

        $this->config = $configResolver->resolve($config);

        return $this;
    }

    /**
     * Get columns.
     *
     * @return array
     */
    public function getColumns(): array {
        return $this->columns;
    }

    /**
     * Add a column with the specified name, class and configuration.
     * If the column with the same name already exists, an exception is thrown.
     *
     * @param string $name
     * @param string $class
     * @param array  $config
     * @return FetchTable
     * @throws \Exception
     */
    public function addColumn(string $name, string $class, array $config = []): self {
        if (array_key_exists($name, $this->columns)) {
            throw new \Exception("Column with name $name already exists");
        }

        $this->columns[$name] = $this->instanceStorage->getColumn($class);
        $this->columns[$name]->setConfig($config);

        return $this;
    }

    /**
     * Get an adapter.
     *
     * @return AbstractAdapter|null
     */
    public function getAdapter(): ?AbstractAdapter {
        return $this->adapter;
    }

    /**
     * Create an adapter with the specified class and configuration.
     *
     * @param string $adapterClass
     * @param array  $config
     * @return $this
     */
    public function createAdapter(string $adapterClass, array $config = []): self {
        return $this->setAdapter(
            $this->instanceStorage->getAdapter($adapterClass)->setConfig($config)
        );
    }

    /**
     * Set an adapter.
     *
     * @param AbstractAdapter|null $adapter
     * @return FetchTable
     */
    public function setAdapter(?AbstractAdapter $adapter): self {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * Get transformer.
     *
     * @return TransformerInterface
     */
    public function getTransformer(): TransformerInterface {
        return $this->transformer;
    }

    /**
     * Set transformer.
     *
     * @param TransformerInterface $transformer
     * @return $this
     */
    public function setTransformer(TransformerInterface $transformer): FetchTable {
        $this->transformer = $transformer;
        return $this;
    }

    /**
     * Handles an incoming Request and returns the JsonResponse if specific conditions are met.
     *
     * This method uses the Symfony PropertyAccess component to retrieve configuration values
     * and checks for a matching header in the request. If the header matches and adapter data is available,
     * it processes the adapter response and returns it as a JSON response.
     *
     * @param Request $request
     * @return JsonResponse|null
     */
    public function handleRequest(Request $request): ?JsonResponse {
        $propertyAccessor = $this->getPropertyAccessor();

        // Reading from the array using the PropertyAccess component
        // https://symfony.com/doc/current/components/property_access.html#reading-from-arrays
        $configMethod = $propertyAccessor->getValue($this->getConfig(), "[ajaxMethod]");
        $configHeaderGenerator = $propertyAccessor->getValue($this->getConfig(), "[ajaxHeaders][X-Requested-By]");
        $configPaginationActive = $propertyAccessor->getValue($this->getConfig(), "[components][pagination][active]");

        if (null !== $configMethod && null !== $configHeaderGenerator) {
            if (
                $request->isMethod($configMethod) &&
                $request->headers->has("X-Requested-By") &&
                $request->headers->get("X-Requested-By") === $configHeaderGenerator
            ) {
                $requestData = $request->isMethod("GET") ? $request->query->all() : $request->getPayload()->all();

                // Parse request looking for filter, pagination and search parameters
                $adapterQuery = new Query();

                // Search
                if ($propertyAccessor->isReadable($requestData, "[search]")) {
                    $adapterQuery->setSearch($propertyAccessor->getValue($requestData, "[search]"));
                }

                // Pagination
                if ($propertyAccessor->isReadable($requestData, "[pagination]")) {
                    $adapterQuery->setPagination(
                        new Pagination(
                            $propertyAccessor->getValue($requestData, "[pagination][page]"),
                            $propertyAccessor->getValue($requestData, "[pagination][size]")
                        )
                    );
                }

                // Sort
                if ($propertyAccessor->isReadable($requestData, "[sort]")) {
                    $adapterQuery->setSort(
                        new Sort(
                            $propertyAccessor->getValue($requestData, "[sort][column]"),
                            Direction::tryFrom($propertyAccessor->getValue($requestData, "[sort][direction]")) ?? Direction::ASC
                        )
                    );
                }

                // Fetch data from the adapter based on the generated query
                if (null !== ($adapterData = $this->getAdapter()?->fetchData($adapterQuery, $this->getTransformer(), $this->getColumns(), $this->getConfig()))) {
                    return new JsonResponse(
                        $adapterData->parseResponse($configPaginationActive)
                    );
                }
            }
        }

        return null;
    }

    /**
     * Remove unnecessary elements from the array.
     * This method recursively traverses the array and removes any elements that are empty or contain only empty arrays.
     * It is useful for removing unnecessary elements from the configuration array.
     *
     * @param array $array
     * @return array
     */
    private function cleanArray(array $array): array {
        foreach ($array as $key => $item) {
            if (is_array($item)) {
                $array[$key] = $this->cleanArray($item);
            }

            if ((is_array($array[$key]) && empty($array[$key])) || "" === $array[$key] || null === $array[$key]) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * Create and return the PropertyAccessor.
     *
     * @return PropertyAccessorInterface
     */
    private function getPropertyAccessor(): PropertyAccessorInterface {
        return PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();
    }
}

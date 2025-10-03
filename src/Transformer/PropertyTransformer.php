<?php

namespace Adamski\Bundle\FetchTableBundle\Transformer;

use Adamski\Bundle\FetchTableBundle\Column\AbstractColumn;
use Adamski\Bundle\FetchTableBundle\Model\Column;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class PropertyTransformer implements TransformerInterface {
    public function transform(array $value, array $columns): array {
        $resultData = [];

        foreach ($value as $item) {
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
}

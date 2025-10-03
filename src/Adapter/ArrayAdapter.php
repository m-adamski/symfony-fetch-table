<?php

namespace Adamski\Bundle\FetchTableBundle\Adapter;

use Adamski\Bundle\FetchTableBundle\Column\AbstractColumn;
use Adamski\Bundle\FetchTableBundle\Model\Query;
use Adamski\Bundle\FetchTableBundle\Model\Result;
use Adamski\Bundle\FetchTableBundle\Model\Sort\Direction;
use Adamski\Bundle\FetchTableBundle\Transformer\TransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArrayAdapter extends AbstractAdapter {
    protected function validateConfig(OptionsResolver $resolver): void {
        $resolver->define("data")->allowedTypes("array")->required();
    }

    public function fetchData(Query $query, TransformerInterface $transformer, array $columns, array $config): Result {
        $data = $this->config["data"];

        // Searching
        if (null !== ($searchContent = $query->getSearch())) {
            $result = [];

            /** @var AbstractColumn $column */
            foreach ($columns as $name => $column) {
                if (true === $column->getConfig("[searchable]")) {
                    $columnResult = array_filter($data, function ($item) use ($name, $searchContent) {
                        return false !== stripos($item[$name] ?? "", $searchContent);
                    });

                    $result = array_merge($result, array_keys($columnResult));
                }
            }

            // Remove duplicates and keep only the keys that are present in the result
            // https://www.php.net/manual/en/function.array-intersect-key.php
            $data = array_intersect_key($data, array_flip(array_unique($result)));
        }

        // Sorting
        if (null !== ($sort = $query->getSort())) {
            $sortColumnName = $sort->getColumn();

            /** @var AbstractColumn $sortColumn */
            if (null !== ($sortColumn = $columns[$sortColumnName])) {
                if (true === $sortColumn->getConfig("[sortable]")) {
                    usort($data, function ($a, $b) use ($sort) {
                        $aValue = $a[$sort->getColumn()] ?? null;
                        $bValue = $b[$sort->getColumn()] ?? null;

                        // Spaceship operator
                        // https://wiki.php.net/rfc/combined-comparison-operator
                        return $sort->getDirection() === Direction::ASC ? $aValue <=> $bValue : $bValue <=> $aValue;
                    });
                } else {
                    throw new \RuntimeException("Column '$sortColumnName' is not sortable");
                }
            } else {
                throw new \RuntimeException("Column '$sortColumnName' does not exist");
            }
        }

        // Pagination
        if (null !== ($pagination = $query->getPagination())) {
            $total = count($data);
            $pages = ceil($total / $pagination->getSize());
            $data = array_slice($data, ($pagination->getPage() - 1) * $pagination->getSize(), $pagination->getSize());

            return (new Result())
                ->setPage($pagination->getPage())
                ->setPageSize($pagination->getSize())
                ->setTotalPages($pages)
                ->setData($transformer->transform($data, $columns));
        }

        return (new Result())
            ->setData($transformer->transform($data, $columns));
    }
}

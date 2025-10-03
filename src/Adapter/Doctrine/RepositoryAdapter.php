<?php

namespace Adamski\Bundle\FetchTableBundle\Adapter\Doctrine;

use Adamski\Bundle\FetchTableBundle\Adapter\AbstractAdapter;
use Adamski\Bundle\FetchTableBundle\Column\AbstractColumn;
use Adamski\Bundle\FetchTableBundle\Model\Query;
use Adamski\Bundle\FetchTableBundle\Model\Result;
use Adamski\Bundle\FetchTableBundle\Transformer\TransformerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RepositoryAdapter extends AbstractAdapter {
    public function __construct(
        private readonly ?ManagerRegistry $managerRegistry = null
    ) {
        if (null === $this->managerRegistry) {
            throw new \InvalidArgumentException("Manager registry is not available. Install symfony/orm-pack to use the RepositoryAdapter");
        }
    }

    protected function validateConfig(OptionsResolver $resolver): void {
        $resolver->define("entity")->allowedTypes("string")->required();
        $resolver->define("queryBuilder")->allowedTypes("callable")->required();
    }

    public function fetchData(Query $query, TransformerInterface $transformer, array $columns, array $config): Result {
        $entityClass = $this->getConfig("[entity]");
        $queryBuilder = $this->getConfig("[queryBuilder]");
        $entityRepository = $this->managerRegistry?->getManagerForClass($entityClass)?->getRepository($entityClass);

        if (null === $entityRepository) {
            throw new \InvalidArgumentException("Repository for entity $entityClass is not available");
        }

        // Call the query builder function
        $queryBuilder = call_user_func($queryBuilder, $entityRepository, $query);

        if (!$queryBuilder instanceof QueryBuilder && !$queryBuilder instanceof Result) {
            throw new \InvalidArgumentException("Query builder must return an instance of Doctrine\ORM\QueryBuilder or FetchTable\Model\Result");
        }

        // Ignore further processing if the query builder is already a result
        if ($queryBuilder instanceof Result) {
            return $queryBuilder;
        }

        // Find root alias from the QueryBuilder
        $rootAlias = $queryBuilder->getRootAliases()[0];

        // Searching
        if (null !== ($searchContent = $query->getSearch())) {
            $orX = $queryBuilder->expr()->orX();

            foreach ($columns as $columnName => $column) {
                if (true === $column->getConfig("[searchable]")) {
                    $orX->add($queryBuilder->expr()->like(sprintf("%s.%s", $rootAlias, $columnName), ":searchContent"));
                }
            }

            $queryBuilder->andWhere($orX)
                ->setParameter("searchContent", "%$searchContent%");
        }

        // Sorting
        if (null !== ($sort = $query->getSort())) {
            $sortColumnName = $sort->getColumn();

            /** @var AbstractColumn $sortColumn */
            if (null !== ($sortColumn = $columns[$sortColumnName])) {
                if (true === $sortColumn->getConfig("[sortable]")) {
                    $queryBuilder->orderBy(sprintf("%s.%s", $rootAlias, $sort->getColumn()), $sort->getDirection()->value);
                } else {
                    throw new \RuntimeException("Column '$sortColumnName' is not sortable");
                }
            } else {
                throw new \RuntimeException("Column '$sortColumnName' does not exist");
            }
        }

        // Pagination
        if (null !== ($pagination = $query->getPagination())) {
            $queryBuilder
                ->setFirstResult($query->getPagination()->getSize() * $query->getPagination()->getPage() - $query->getPagination()->getSize())
                ->setMaxResults($query->getPagination()->getSize());

            $queryPaginator = new Paginator($queryBuilder);
            $queryResult = $queryBuilder->getQuery()->getResult();

            return (new Result())
                ->setPage($pagination->getPage())
                ->setPageSize($pagination->getSize())
                ->setTotalPages(ceil($queryPaginator->count() / $pagination->getSize()))
                ->setData(
                    $transformer->transform($queryResult, $columns)
                );
        }

        return (new Result())
            ->setData(
                $transformer->transform(
                    $queryBuilder->getQuery()->getResult(), $columns
                )
            );
    }
}

<?php

namespace Adamski\Bundle\FetchTableBundleTests\Adapter\Doctrine;

use Adamski\Bundle\FetchTableBundle\Adapter\Doctrine\RepositoryAdapter;
use Adamski\Bundle\FetchTableBundle\Column\TextColumn;
use Adamski\Bundle\FetchTableBundle\Model\Filter\Filter;
use Adamski\Bundle\FetchTableBundle\Model\Query as ModelQuery;
use Adamski\Bundle\FetchTableBundle\Model\Result;
use Adamski\Bundle\FetchTableBundle\Model\Sort\Direction;
use Adamski\Bundle\FetchTableBundle\Model\Sort\Sort;
use Adamski\Bundle\FetchTableBundle\Transformer\TransformerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class RepositoryAdapterTest extends TestCase {
    public function testConstructorThrowsExceptionWhenManagerRegistryIsNull(): void {
        $this->expectException(\InvalidArgumentException::class);
        // $this->expectExceptionMessage("Manager registry is not available. Install symfony/orm-pack to use the RepositoryAdapter");

        new RepositoryAdapter(null);
    }

    public function testValidateConfigRequiresEntityAndQueryBuilder(): void {
        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $adapter = new RepositoryAdapter($managerRegistry);

        $this->expectException(MissingOptionsException::class);
        $adapter->setConfig([]);
    }

    public function testFetchDataThrowsExceptionWhenRepositoryNotAvailable(): void {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->once())
            ->method("getManagerForClass")
            ->with("App\Entity\User")
            ->willReturn(null);

        $adapter = new RepositoryAdapter($managerRegistry);
        $adapter->setConfig([
            "entity"       => "App\Entity\User",
            "queryBuilder" => fn() => null,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains("Repository for entity App\Entity\User is not available");

        $transformer = $this->createStub(TransformerInterface::class);
        $adapter->fetchData(new ModelQuery(), $transformer, [], []);
    }

    public function testFetchDataThrowsExceptionWhenQueryBuilderReturnsInvalidType(): void {
        $entityRepository = $this->createStub(EntityRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method("getRepository")->willReturn($entityRepository);

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method("getManagerForClass")->willReturn($entityManager);

        $adapter = new RepositoryAdapter($managerRegistry);
        $adapter->setConfig([
            "entity"       => "App\Entity\User",
            "queryBuilder" => fn() => "invalid",
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains("Query builder must return an instance of Doctrine\ORM\QueryBuilder or FetchTable\Model\Result");

        $transformer = $this->createStub(TransformerInterface::class);
        $adapter->fetchData(new ModelQuery(), $transformer, [], []);
    }

    public function testFetchDataReturnsResultDirectlyWhenQueryBuilderReturnsResult(): void {
        $entityRepository = $this->createStub(EntityRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method("getRepository")->willReturn($entityRepository);

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method("getManagerForClass")->willReturn($entityManager);

        $expectedResult = new Result();
        $expectedResult->setTotalRecords(10);

        $adapter = new RepositoryAdapter($managerRegistry);
        $adapter->setConfig([
            "entity"       => "App\Entity\User",
            "queryBuilder" => fn() => $expectedResult,
        ]);

        $transformer = $this->createStub(TransformerInterface::class);
        $result = $adapter->fetchData(new ModelQuery(), $transformer, [], []);

        $this->assertSame($expectedResult, $result);
    }

    public function testFetchDataWithFilteringAppliesFilterOnlyToFilterableColumns(): void {
        $entityRepository = $this->createStub(EntityRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method("getRepository")->willReturn($entityRepository);

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method("getManagerForClass")->willReturn($entityManager);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $expr = new Expr();
        $queryBuilder->method("getRootAliases")->willReturn(["u"]);
        $queryBuilder->method("expr")->willReturn($expr);

        // Expect filterColumn_status_value to be set, but not filterColumn_email_value (email is not filterable)
        $queryBuilder->expects($this->once())
            ->method("setParameter")
            ->with("filterColumn_status_value", "active")
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method("andWhere")
            ->with($this->callback(function (Expr\Andx $andX) {
                $this->assertCount(1, $andX->getParts());
                $this->assertEquals("u.status = :filterColumn_status_value", (string) $andX->getParts()[0]);
                return true;
            }))
            ->willReturnSelf();

        $ormQuery = $this->createStub(Query::class);
        $ormQuery->method("getResult")->willReturn([["status" => "active"]]);
        $queryBuilder->method("getQuery")->willReturn($ormQuery);

        $statusColumn = new TextColumn()->setConfig(["label" => "Status", "filterable" => true]);
        $emailColumn = new TextColumn()->setConfig(["label" => "Email", "filterable" => false]);
        $columns = [
            "status" => $statusColumn,
            "email"  => $emailColumn,
        ];

        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())
            ->method("transform")
            ->with([["status" => "active"]], $columns)
            ->willReturn([["transformed" => "data"]]);

        $adapter = new RepositoryAdapter($managerRegistry);
        $adapter->setConfig([
            "entity"       => "App\Entity\User",
            "queryBuilder" => fn() => $queryBuilder,
        ]);

        $query = new ModelQuery();
        $query->addFilter(new Filter("status", "active"));
        $query->addFilter(new Filter("email", "john@example.com"));

        $result = $adapter->fetchData($query, $transformer, $columns, []);

        $this->assertEquals([["transformed" => "data"]], $result->getData());
        $this->assertEquals(1, $result->getTotalRecords());
    }

    public function testFetchDataWithSearchingAppliesSearchToSearchableColumns(): void {
        $entityRepository = $this->createStub(EntityRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method("getRepository")->willReturn($entityRepository);

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method("getManagerForClass")->willReturn($entityManager);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $expr = new Expr();
        $queryBuilder->method("getRootAliases")->willReturn(["u"]);
        $queryBuilder->method("expr")->willReturn($expr);

        $queryBuilder->expects($this->once())
            ->method("andWhere")
            ->with($this->callback(function (Expr\Orx $orX) {
                $this->assertCount(1, $orX->getParts());
                $this->assertEquals("u.name LIKE :searchContent", (string) $orX->getParts()[0]);
                return true;
            }))
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method("setParameter")
            ->with("searchContent", "%john%")
            ->willReturnSelf();

        $ormQuery = $this->createStub(Query::class);
        $ormQuery->method("getResult")->willReturn([]);
        $queryBuilder->method("getQuery")->willReturn($ormQuery);

        $nameColumn = new TextColumn()->setConfig(["label" => "Name", "searchable" => true]);
        $emailColumn = new TextColumn()->setConfig(["label" => "Email", "searchable" => false]);
        $columns = [
            "name"  => $nameColumn,
            "email" => $emailColumn,
        ];

        $transformer = $this->createStub(TransformerInterface::class);

        $adapter = new RepositoryAdapter($managerRegistry);
        $adapter->setConfig([
            "entity"       => "App\Entity\User",
            "queryBuilder" => fn() => $queryBuilder,
        ]);

        $query = new ModelQuery();
        $query->setSearchQuery("john");

        $adapter->fetchData($query, $transformer, $columns, []);
    }

    public function testFetchDataWithSortingAppliesOrderBy(): void {
        $entityRepository = $this->createStub(EntityRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method("getRepository")->willReturn($entityRepository);

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method("getManagerForClass")->willReturn($entityManager);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method("getRootAliases")->willReturn(["u"]);

        $queryBuilder->expects($this->once())
            ->method("orderBy")
            ->with("u.name", "DESC")
            ->willReturnSelf();

        $ormQuery = $this->createStub(Query::class);
        $ormQuery->method("getResult")->willReturn([]);
        $queryBuilder->method("getQuery")->willReturn($ormQuery);

        $nameColumn = new TextColumn()->setConfig(["label" => "Name", "sortable" => true]);
        $columns = ["name" => $nameColumn];

        $transformer = $this->createStub(TransformerInterface::class);

        $adapter = new RepositoryAdapter($managerRegistry);
        $adapter->setConfig([
            "entity"       => "App\Entity\User",
            "queryBuilder" => fn() => $queryBuilder,
        ]);

        $query = new ModelQuery();
        $query->setSort(new Sort("name", Direction::DESC));

        $adapter->fetchData($query, $transformer, $columns, []);
    }

    public function testFetchDataWithSortOnNonSortableColumnThrowsException(): void {
        $entityRepository = $this->createStub(EntityRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method("getRepository")->willReturn($entityRepository);

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method("getManagerForClass")->willReturn($entityManager);

        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method("getRootAliases")->willReturn(["u"]);

        $nameColumn = new TextColumn()->setConfig(["label" => "Name", "sortable" => false]);
        $columns = ["name" => $nameColumn];

        $transformer = $this->createStub(TransformerInterface::class);

        $adapter = new RepositoryAdapter($managerRegistry);
        $adapter->setConfig([
            "entity"       => "App\Entity\User",
            "queryBuilder" => fn() => $queryBuilder,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageIsOrContains("Column 'name' is not sortable");

        $query = new ModelQuery();
        $query->setSort(new Sort("name", Direction::ASC));

        $adapter->fetchData($query, $transformer, $columns, []);
    }

    public function testFetchDataWithSortOnNonExistentColumnThrowsException(): void {
        $entityRepository = $this->createStub(EntityRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method("getRepository")->willReturn($entityRepository);

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method("getManagerForClass")->willReturn($entityManager);

        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method("getRootAliases")->willReturn(["u"]);

        $columns = [];

        $transformer = $this->createStub(TransformerInterface::class);

        $adapter = new RepositoryAdapter($managerRegistry);
        $adapter->setConfig([
            "entity"       => "App\Entity\User",
            "queryBuilder" => fn() => $queryBuilder,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageIsOrContains("Column 'unknown' does not exist");

        $query = new ModelQuery();
        $query->setSort(new Sort("unknown", Direction::ASC));

        $adapter->fetchData($query, $transformer, $columns, []);
    }
}

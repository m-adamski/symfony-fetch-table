# Fetch Table Bundle for Symfony

Fetch Table Bundle integrates the [Fetch Table JS](https://github.com/m-adamski/fetch-table) library with Symfony to
handle server-side pagination, searching, sorting, and filtering for HTML tables.

## Requirements

- PHP 8.2 or higher
- Symfony 6.4, 7.0 or higher

## Installation

Install the bundle using Composer:

```bash
composer require m-adamski/symfony-fetch-table
```

If you use Symfony Flex, the bundle is enabled automatically. Otherwise, register it in `config/bundles.php`:

```php
return [
    // ...
    Adamski\Bundle\FetchTableBundle\FetchTableBundle::class => ['all' => true],
];
```

## Quick Start

### 1. Controller

Create a table instance using `FetchTableFactory`, configure columns and an adapter, and handle the incoming request:

```php
namespace App\Controller;

use Adamski\Bundle\FetchTableBundle\Adapter\Doctrine\RepositoryAdapter;
use Adamski\Bundle\FetchTableBundle\Column\DateTimeColumn;
use Adamski\Bundle\FetchTableBundle\Column\PropertyColumn;
use Adamski\Bundle\FetchTableBundle\Column\TextColumn;
use Adamski\Bundle\FetchTableBundle\Column\Twig\TwigColumn;
use Adamski\Bundle\FetchTableBundle\FetchTableFactory;
use App\Entity\Book;
use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController {
    public function __construct(
        private readonly FetchTableFactory $fetchTableFactory,
    ) {}

    #[Route('/books', name: 'app_books', methods: ['GET'])]
    public function index(Request $request): Response {
        $table = $this->fetchTableFactory
            ->create('#books-table', [
                'ajaxURL'    => $this->generateUrl('app_books'),
                'ajaxMethod' => 'GET',
                'components' => [
                    'search'     => ['active' => true],
                    'pagination' => ['active' => true, 'pageSize' => 25],
                    'filter'     => [
                        'active'  => true,
                        'filters' => [
                            [
                                'columnName' => 'status',
                                'label'      => 'Status',
                                'type'       => 'select',
                                'values'     => ['available', 'borrowed'],
                            ],
                        ],
                    ],
                ],
            ])
            ->addColumn('title', TextColumn::class, [
                'label'      => 'Title',
                'searchable' => true,
                'sortable'   => true,
            ])
            ->addColumn('author', PropertyColumn::class, [
                'label'    => 'Author',
                'property' => 'author.name',
                'sortable' => true,
            ])
            ->addColumn('status', TextColumn::class, [
                'label'      => 'Status',
                'filterable' => true,
                'sortable'   => true,
            ])
            ->addColumn('createdAt', DateTimeColumn::class, [
                'label'    => 'Created At',
                'format'   => 'Y-m-d H:i',
                'sortable' => true,
            ])
            ->addColumn('actions', TwigColumn::class, [
                'label'    => 'Actions',
                'mapped'   => false,
                'template' => 'book/_actions.html.twig',
            ])
            ->createAdapter(RepositoryAdapter::class, [
                'entity'       => Book::class,
                'queryBuilder' => function (BookRepository $repository) {
                    return $repository->createQueryBuilder('book');
                },
            ]);

        if (null !== ($response = $table->handleRequest($request))) {
            return $response;
        }

        return $this->render('book/index.html.twig', [
            'table' => $table,
        ]);
    }
}
```

### 2. Twig Template

Render the table container and initialize Fetch Table JS using the `fetch_table_config` Twig function:

```twig
{% extends 'base.html.twig' %}

{% block body %}
<div id="books-table"></div>
{% endblock %}

{% block javascripts %}
{{ parent () }}
<script>
const tableConfig = {{ fetch_table_config (table)|raw }};
new FetchTable (tableConfig.selector, tableConfig.config);
</script>
{% endblock %}
```

## Columns

All column types inherit the following base options:

- `label` (string, required): Header text for the column.
- `type` (string, default: "text"): Column type sent to the front-end library.
- `className` (string, optional): CSS class added to the column cells.
- `sortable` (bool, default: false): Enables column sorting.
- `searchable` (bool, default: false): Includes the column in text search queries.
- `filterable` (bool, default: false): Enables filtering for this column.
- `mapped` (bool, default: true): Indicates whether the column is mapped to source data.

### TextColumn

Renders text values directly from the item.

```php
->addColumn('title', TextColumn::class, [
    'label'      => 'Title',
    'searchable' => true,
    'sortable'   => true,
    'filterable' => true,
])
```

### PropertyColumn

Retrieves nested entity or array properties using Symfony PropertyAccess syntax (e.g., `author.name`).

- `property` (string, required): Property path to access.
- `defaultValue` (mixed, default: ""): Fallback value if the property is null or undefined.
- `expanded` (bool, default: true): Passes the entire object/array to the transformer.

```php
->addColumn('author', PropertyColumn::class, [
    'label'    => 'Author',
    'property' => 'author.name',
    'sortable' => true,
])
```

### DateTimeColumn

Formats `\DateTimeInterface` objects.

- `format` (string, default: "Y-m-d H:i:s"): Date format string accepted by `date()`.

```php
->addColumn('createdAt', DateTimeColumn::class, [
    'label'  => 'Created At',
    'format' => 'd.m.Y H:i',
])
```

### CallableColumn

Transforms column output using a custom PHP callback.

- `callable` (callable, required): Callback receiving the column value (or the full entity if `expanded` is true).
- `expanded` (bool, default: false): Whether to pass the entire entity/row to the callback.

```php
->addColumn('price', CallableColumn::class, [
    'label'    => 'Price',
    'callable' => function ($price) {
        return number_format($price / 100, 2) . ' USD';
    },
])
```

### TwigColumn

Renders a Twig template for each row (requires `symfony/twig-bundle`).

- `template` (string, required): Twig template path.
- `expanded` (bool, default: false): Passes the entire entity/row as `value` to the template.

```php
->addColumn('actions', TwigColumn::class, [
    'label'    => 'Actions',
    'mapped'   => false,
    'template' => 'book/_actions.html.twig',
])
```

Inside your Twig template:

```twig
<a href="{{ path('book_edit', {id: value.id}) }}">Edit</a>
```

## Adapters

Adapters connect FetchTable with your data storage.

### RepositoryAdapter

Uses Doctrine ORM QueryBuilder for database queries (requires `symfony/orm-pack`). Automatically applies sorting,
filtering, searching, and pagination to the QueryBuilder.

- `entity` (string, required): Entity class name.
- `queryBuilder` (callable, required): Callback accepting the entity repository and returning a
  `Doctrine\ORM\QueryBuilder` instance (or a `Result` instance).

```php
use Adamski\Bundle\FetchTableBundle\Adapter\Doctrine\RepositoryAdapter;

->createAdapter(RepositoryAdapter::class, [
    'entity'       => Book::class,
    'queryBuilder' => function (BookRepository $repository) {
        return $repository->createQueryBuilder('b')
            ->leftJoin('b.category', 'c')
            ->addSelect('c');
    },
])
```

### ArrayAdapter

Fetches and manipulates data from an in-memory PHP array.

- `data` (array, required): Array of records.

```php
use Adamski\Bundle\FetchTableBundle\Adapter\ArrayAdapter;

->createAdapter(ArrayAdapter::class, [
    'data' => [
        ['id' => 1, 'name' => 'Item 1', 'status' => 'active'],
        ['id' => 2, 'name' => 'Item 2', 'status' => 'inactive'],
    ],
])
```

### CallableAdapter

Gives full control over data fetching via a custom callback.

- `callable` (callable, required): Callback receiving
  `(Query $query, TransformerInterface $transformer, array $columns, array $config)`.

```php
use Adamski\Bundle\FetchTableBundle\Adapter\CallableAdapter;
use Adamski\Bundle\FetchTableBundle\Model\Query;
use Adamski\Bundle\FetchTableBundle\Model\Result;

->createAdapter(CallableAdapter::class, [
    'callable' => function (Query $query, $transformer, $columns, $config) {
        $data = [
            ['title' => 'Book A', 'status' => 'active'],
            ['title' => 'Book B', 'status' => 'inactive'],
        ];

        return (new Result())
            ->setData($transformer->transform($data, $columns))
            ->setTotalRecords(count($data));
    },
])
```

## Table Configuration

The second argument of `FetchTableFactory::create()` accepts table options:

```php
$table = $this->fetchTableFactory->create('#table-id', [
    'ajaxURL'     => '/api/books',
    'ajaxMethod'  => 'GET', // 'GET' or 'POST'
    'ajaxHeaders' => [
        'Content-Type'     => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
    ],
    'debug'       => false,
    'components'  => [
        'search'     => [
            'active' => true,
        ],
        'pagination' => [
            'active'         => true,
            'pageSize'       => 25,
            'availableSizes' => [10, 25, 50, 100],
            'style'          => 'standard', // 'standard' or 'simple'
            'description'    => [
                'active' => true,
            ],
        ],
        'filter'     => [
            'active'   => true,
            'allLabel' => 'All',
            'filters'  => [
                [
                    'columnName' => 'status',
                    'label'      => 'Status',
                    'type'       => 'select', // 'select' or 'button'
                    'values'     => ['active', 'inactive'],
                ],
            ],
        ],
    ],
]);
```

## License

This bundle is open source software licensed under the MIT License.

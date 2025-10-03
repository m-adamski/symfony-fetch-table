# Fetch Table Bundle for Symfony

The Symfony bundle that integrates the lightweight [Fetch Table JS](https://github.com/m-adamski/fetch-table) library
to handle remote data fetching and render responsive, accessible HTML tables.

## Installation

This bundle is available on Packagist and can be installed using Composer:

```shell
composer require m-adamski/symfony-fetch-table
```

## How to use it?

The library is designed to run within controller logic. It automatically generates configuration for the JS library and
handles incoming HTTP requests.

```php
public function __construct(
    private readonly FetchTableFactory $fetchTableFactory,
) {}

#[Route("/", name: "index", methods: ["GET"])]
public function index(Request $request): Response {
    $table = $this->fetchTableFactory
            ->create("#fetch-table", [
                "ajaxURL"    => $this->generateUrl("index"),
                "ajaxMethod" => "GET",
                "components" => [
                    "search"     => [
                        "active" => true,
                    ],
                    "pagination" => [
                        "active"   => true,
                    ]
                ]
            ])
            ->addColumn("title", TextColumn::class, [
                "label" => "Title",
                "searchable" => true,
                "sortable" => true
            ])
            ->addColumn("description", TextColumn::class, [
                "label" => "Description",
                "searchable" => true,
                "sortable" => true
            ])
            ->addColumn("author", PropertyColumn::class, [
                "label"    => "Author",
                "property" => "author",
                "sortable" => true
            ])
            ->addColumn("createdAt", DateTimeColumn::class, [
                "label"  => "Creation Date",
                "format" => "d.m.Y H:i",
                "sortable" => true
            ])
            ->addColumn("options", TwigColumn::class, [
                "label"    => "Options",
                "mapped"   => false,
                "expanded" => true,
                "template" => "column/options.html.twig",
            ])
            ->createAdapter(RepositoryAdapter::class, [
                "entity"       => Book::class,
                "queryBuilder" => function (BookRepository $bookRepository) {
                    return $bookRepository->createQueryBuilder("book");
                }
            ]);

    if (null !== ($tableResponse = $table->handleRequest($request))) {
        return $tableResponse;
    }

    return $this->render("index.html.twig", [
        "table" => $table,
    ]);
}
```

## Documentation

Detailed documentation of the JS library can be found
at [https://github.com/m-adamski/fetch-table](https://github.com/m-adamski/fetch-table).

### Columns Configuration

The bundle provides a set of column types that can be used to process and render different types of data.

All columns accept basic configuration parameters:

- type (string, default: "text") - Column type
- label (string, required) - Column label
- className (string, optional) - CSS class name of the column
- sortable (boolean, default: false) - Whether the column should be sortable
- searchable (boolean, default: false) - Whether the column should be searchable
- mapped (boolean, default: true) - Whether the column should be mapped to the data source

#### TextColumn::class

Example:

```php
->addColumn("title", TextColumn::class, [
    "label" => "Title",
    "searchable" => true,
    "sortable" => true
])
```

The column has no additional configuration parameters.

#### PropertyColumn::class

Example:

```php
->addColumn("author", PropertyColumn::class, [
    "label"    => "Author",
    "property" => "author",
    "sortable" => true
])
```

- property (string, required) - Property name of the entity
- defaultValue (string | int | float, default: "") - Default value if property is null or undefined
- expanded (boolean, default: true) - Whether the column value should be expanded

#### DateTimeColumn::class

Example:

```php
->addColumn("createdAt", DateTimeColumn::class, [
    "label"  => "Creation Date",
    "format" => "d/m/Y H:i",
    "sortable" => true
])
```

- format (string, default: "Y-m-d H:i:s") - Date format

#### CallableColumn::class

Example:

```php
->addColumn("description", CallableColumn::class, [
    "label"      => "Email Address",
    "callable"   => function (string $description) {
        return "Description: $description";
    },
    "searchable" => true,
    "sortable"   => true
])
```

```php
->addColumn("description", CallableColumn::class, [
    "label"      => "Email Address",
    "callable"   => function (Book $book) {
        return "Description: " . $book->getDescription();
    },
    "expanded"   => true,
    "searchable" => true,
    "sortable"   => true
])
```

- callable (callable, required) - Callable that accepts the column value and returns the rendered value
- expanded (boolean, default: false) - Whether the column value should be expanded

### Adapters Configuration

The bundle provides a set of adapters that can be used to fetch data from different sources.

#### ArrayAdapter::class

The adapter expects a table of data as a configuration parameter and handles all search, sorting, and pagination
functionality internally.

Example:

```php
->createAdapter(ArrayAdapter::class, [
    "data" => [
        ["name" => "John Doe", "emailAddress" => "test@example.com"],
        ["name" => "Jane Smith", "emailAddress" => "jane.smith@example.com"],
        ["name" => "Michael Brown", "emailAddress" => "michael.brown@example.com"],
        ["name" => "Emily Davis", "emailAddress" => "emily.davis@example.com"],
        ["name" => "David Wilson", "emailAddress" => "david.wilson@example.com"],
        ["name" => "Sarah Johnson", "emailAddress" => "sarah.johnson@example.com"],
    ]
]);
```

- data (array, required) - Array of data

#### CallableAdapter::class

The only configuration parameter for this adapter is a function that will be called when the data is rendered. We must
provide support for searching, sorting, and pagination within this function.

Example:

```php
->createAdapter(CallableAdapter::class, [
    "callable" => function (Query $query, $transformer, $columns, $config) {

        // Searching
        if (null !== ($searchContent = $query->getSearch())) {
            // ...
        }

        // Sorting
        if (null !== ($sort = $query->getSort())) {
            // ...
        }

        // Pagination
        if (null !== ($pagination = $query->getPagination())) {
            // ...
        }

        return (new Result())->setData(
            $transformer->transform([
                ["title" => "Title 1", "author" => "Author 1", "createdAt" => new \DateTime()],
                ["title" => "Title 2", "author" => "Author 2", "createdAt" => new \DateTime()],
            ], $columns)
        );
    }
]);
```

- callable (callable, required) - Callable that accepts the query, transformer, columns, and config and returns the
  result

#### RepositoryAdapter::class

To use this adapter, you need to install the symfony/orm-pack package. The adapter handles search, sorting, and
pagination functionality internally.

Example:

```php
->createAdapter(RepositoryAdapter::class, [
    "entity"       => Book::class,
    "queryBuilder" => function (BookRepository $bookRepository) {
        return $bookRepository->createQueryBuilder("book");
    }
]);
```

- entity (string, required) - Entity class name
- queryBuilder (callable, required) - Callable that accepts the entity repository and returns the query builder (if
  there is a need to bypass the internal functionality of the adapter (search, sorting, and pagination), the function
  can return the result immediately)

## License

This project is open source and available for personal and commercial use under the MIT License.

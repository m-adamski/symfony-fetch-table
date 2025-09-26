<?php

namespace Adamski\Bundle\FetchTableBundle\Model;

class Result {
    private int $page;
    private int $pageSize;
    private int $totalPages;
    private array $data;

    public function getPage(): int {
        return $this->page;
    }

    public function setPage(int $page): Result {
        $this->page = $page;
        return $this;
    }

    public function getPageSize(): int {
        return $this->pageSize;
    }

    public function setPageSize(int $pageSize): Result {
        $this->pageSize = $pageSize;
        return $this;
    }

    public function getTotalPages(): int {
        return $this->totalPages;
    }

    public function setTotalPages(int $totalPages): Result {
        $this->totalPages = $totalPages;
        return $this;
    }

    public function getData(): array {
        return $this->data;
    }

    public function setData(array $data): Result {
        $this->data = $data;
        return $this;
    }

    /**
     * Parses the response structure and returns an array containing all the data.
     *
     * @param bool $isPagination
     * @return array
     */
    public function parseResponse(bool $isPagination = false): array {
        $responseData = $this->getData();

        // Convert Column object to array
        array_walk_recursive($responseData, function (&$item) {
            if ($item instanceof Column) {
                $item = $item->toArray();
            }
        });

        // Prepare response
        $parseResponse = ["data" => $responseData];

        if ($isPagination) {
            $parseResponse["pagination"] = [
                "page"       => $this->getPage(),
                "pageSize"   => $this->getPageSize(),
                "totalPages" => $this->getTotalPages(),
            ];
        }

        return $parseResponse;
    }
}

<?php

namespace Adamski\Bundle\FetchTableBundle\Transformer;

interface TransformerInterface {
    public function transform(array $value, array $columns): array;
}

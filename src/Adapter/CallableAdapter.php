<?php

namespace Adamski\Bundle\FetchTableBundle\Adapter;

use Adamski\Bundle\FetchTableBundle\Model\Query;
use Adamski\Bundle\FetchTableBundle\Model\Result;
use Adamski\Bundle\FetchTableBundle\Transformer\TransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CallableAdapter extends AbstractAdapter {
    protected function validateConfig(OptionsResolver $resolver): void {
        $resolver->define("callable")->allowedTypes("callable")->required();
    }

    public function fetchData(Query $query, TransformerInterface $transformer, array $columns, array $config): Result {
        $function = $this->getConfig("[callable]");
        $functionResult = call_user_func($function, $query, $transformer, $columns, $config);

        if (!$functionResult instanceof Result) {
            throw new \InvalidArgumentException("Function must return an instance of FetchTable\Model\Result");
        }

        return $functionResult;
    }
}

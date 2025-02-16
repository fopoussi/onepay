<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class AmountFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if ($property !== 'amount') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName('amount');

        $queryBuilder
            ->andWhere(sprintf('%s.amount = :%s', $alias, $parameterName))
            ->setParameter($parameterName, $value);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'amount' => [
                'property' => 'amount',
                'type' => 'float',
                'required' => false,
                'description' => 'Filtrer les transactions par montant',
            ],
        ];
    }
}
<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class StatusFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if ($property !== 'status') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName('status');

        $queryBuilder
            ->andWhere(sprintf('%s.status = :%s', $alias, $parameterName))
            ->setParameter($parameterName, $value);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'status' => [
                'property' => 'status',
                'type' => 'string',
                'required' => false,
                'description' => 'Filtrer les transactions par statut',
            ],
        ];
    }
}
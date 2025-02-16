<?php

namespace App\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\OnepayTransaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class OnepayTransactionProcessor implements ProcessorInterface
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): OnepayTransaction
    {
        // Vérification que l'entité est bien une instance de OnepayTransaction
        if (!$data instanceof OnepayTransaction) {
            throw new \InvalidArgumentException('Expected instance of OnepayTransaction.');
        }

        // Valider les données
        $errors = $this->validator->validate($data);
        if (count($errors) > 0) {
            throw new \RuntimeException('Validation failed: ' . (string) $errors);
        }

        // Ajouter une logique métier personnalisée
        if ($data->getAmount() > 1000) {
            $data->setStatus('pending_approval');
        }

        // Persister les données en base
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}

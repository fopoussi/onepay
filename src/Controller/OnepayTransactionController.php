<?php

namespace App\Controller;

use App\Entity\OnepayTransaction;
use App\Repository\OnepayTransactionRepository;
use App\Service\TransactionLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/onepay_transactions')]
class OnepayTransactionController extends AbstractController
{
    private $transactionRepository;
    private $entityManager;
    private $transactionLogger;

    public function __construct(
        OnepayTransactionRepository $transactionRepository,
        EntityManagerInterface $entityManager,
        TransactionLogger $transactionLogger
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->entityManager = $entityManager;
        $this->transactionLogger = $transactionLogger;
    }

    // Route pour obtenir toutes les transactions
    #[Route('/', name: 'onepay_transaction_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(): JsonResponse
    {
        $transactions = $this->transactionRepository->findAll();
        return $this->json($transactions);
    }

    // Route pour obtenir les transactions par statut
    #[Route('/status/{status}', name: 'onepay_transaction_by_status', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getByStatus(string $status): JsonResponse
    {
        $transactions = $this->transactionRepository->findBy(['status' => $status]);
        return $this->json($transactions);
    }

    // Route pour créer une nouvelle transaction avec journalisation
    #[Route('/', name: 'onepay_transaction_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $transaction = new OnepayTransaction();
        $transaction->setTransactionType($data['transaction_type']);
        $transaction->setAmount($data['amount']);
        $transaction->setStatus($data['status'] ?? 'pending');
        $transaction->setFromOperator($data['from_operator'] ?? null);
        $transaction->setToOperator($data['to_operator'] ?? null);
        $transaction->setReceiverPhone($data['receiver_phone'] ?? null);
        $transaction->setOperator($data['operator'] ?? null);
        $transaction->setPhoneNumber($data['phone_number'] ?? null);

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        // Journaliser la création de la transaction
        $this->transactionLogger->logTransaction('create', [
            'id' => $transaction->getId(),
            'amount' => $transaction->getAmount(),
            'status' => $transaction->getStatus(),
        ]);

        return $this->json($transaction, 201); // 201 = Created
    }

    // Route pour obtenir les détails d'une transaction spécifique
    #[Route('/{id}', name: 'onepay_transaction_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(int $id): JsonResponse
    {
        $transaction = $this->transactionRepository->find($id);
        if (!$transaction) {
            return $this->json(['error' => 'Transaction not found'], 404);
        }
        return $this->json($transaction);
    }

    // Route pour mettre à jour une transaction
    #[Route('/{id}', name: 'onepay_transaction_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request): JsonResponse
    {
        $transaction = $this->transactionRepository->find($id);
        if (!$transaction) {
            return $this->json(['error' => 'Transaction not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $transaction->setTransactionType($data['transaction_type'] ?? $transaction->getTransactionType());
        $transaction->setAmount($data['amount'] ?? $transaction->getAmount());
        $transaction->setStatus($data['status'] ?? $transaction->getStatus());
        $transaction->setFromOperator($data['from_operator'] ?? $transaction->getFromOperator());
        $transaction->setToOperator($data['to_operator'] ?? $transaction->getToOperator());
        $transaction->setReceiverPhone($data['receiver_phone'] ?? $transaction->getReceiverPhone());
        $transaction->setOperator($data['operator'] ?? $transaction->getOperator());
        $transaction->setPhoneNumber($data['phone_number'] ?? $transaction->getPhoneNumber());

        $this->entityManager->flush();

        return $this->json($transaction);
    }

    // Route pour supprimer une transaction
    #[Route('/{id}', name: 'onepay_transaction_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $transaction = $this->transactionRepository->find($id);
        if (!$transaction) {
            return $this->json(['error' => 'Transaction not found'], 404);
        }

        $this->entityManager->remove($transaction);
        $this->entityManager->flush();

        return $this->json(['message' => 'Transaction deleted']);
    }

    #[Route('/user/{userId}', name: 'onepay_transaction_by_user', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getByUser(int $userId): JsonResponse
    {
        $transactions = $this->transactionRepository->findBy(['user' => $userId]);
        return $this->json($transactions);
    }

    // Nouvelle méthode de filtrage
    #[Route('/filter', name: 'onepay_transaction_filter', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function filter(Request $request): JsonResponse
    {
        // Récupérer les paramètres de filtrage depuis la requête
        $status = $request->query->get('status');
        $type = $request->query->get('type');
        $fromDate = $request->query->get('from_date');
        $toDate = $request->query->get('to_date');
        $operator = $request->query->get('operator');

        // Construire le critère de recherche
        $criteria = [];
        
        if ($status) {
            $criteria['status'] = $status;
        }
        
        if ($type) {
            $criteria['transaction_type'] = $type;
        }
        
        if ($operator) {
            $criteria['operator'] = $operator;
        }

        // Récupérer les transactions selon les critères
        $transactions = $this->transactionRepository->findBy($criteria);

        // Filtrer par date si spécifié
        if ($fromDate || $toDate) {
            $transactions = array_filter($transactions, function($transaction) use ($fromDate, $toDate) {
                $transactionDate = $transaction->getCreatedAt();
                
                if ($fromDate && $transactionDate < new \DateTime($fromDate)) {
                    return false;
                }
                
                if ($toDate && $transactionDate > new \DateTime($toDate)) {
                    return false;
                }
                
                return true;
            });
        }

        return $this->json(array_values($transactions));
    }

    #[Route('/user/{userId}/balance', name: 'onepay_user_balance', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getUserBalance(int $userId): JsonResponse
    {
        $balance = $this->transactionRepository->calculateUserBalance($userId);
        return $this->json(['balance' => $balance]);
    }
}
<?php

namespace App\Service;

use App\Entity\MobileMoneyAccount;
use App\Entity\Transaction;

interface MobileMoneyService
{
    /**
     * Vérifie si un compte Mobile Money est valide
     */
    public function verifyAccount(MobileMoneyAccount $account): bool;
    
    /**
     * Récupère le solde d'un compte Mobile Money
     */
    public function getBalance(MobileMoneyAccount $account): float;
    
    /**
     * Initie un paiement et retourne la référence de transaction
     */
    public function initiatePayment(Transaction $transaction): string;
    
    /**
     * Vérifie le statut d'un paiement
     * @return array{
     *     status: string,
     *     message: string,
     *     operatorReference: ?string,
     *     completedAt: ?\DateTimeImmutable
     * }
     */
    public function checkPaymentStatus(string $reference): array;
    
    /**
     * Effectue un achat de crédit téléphonique
     */
    public function purchaseAirtime(Transaction $transaction): bool;
    
    /**
     * Effectue un transfert d'argent
     */
    public function transferMoney(Transaction $transaction): bool;
}

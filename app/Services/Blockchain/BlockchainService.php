<?php

namespace App\Services\Blockchain;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlockchainService
{
    private $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('BLOCKCHAIN_SERVICE_URL', 'http://localhost:3000/api/wallet');
    }

    public function createWallet($network, $userId)
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/create", [
                'network' => $network,
                'userId' => $userId
            ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Blockchain service error - Create Wallet', [
                    'network' => $network,
                    'userId' => $userId,
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Blockchain service exception - Create Wallet', [
                'network' => $network,
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function sendTransaction($network, $fromWalletId, $toAddress, $amount)
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/send", [
                'network' => $network,
                'fromWalletId' => $fromWalletId,
                'toAddress' => $toAddress,
                'amount' => $amount
            ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Blockchain service error - Send Transaction', [
                    'network' => $network,
                    'fromWalletId' => $fromWalletId,
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Blockchain service exception - Send Transaction', [
                'network' => $network,
                'fromWalletId' => $fromWalletId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getTransactionStatus($network, $txId)
    {
        try {
            $response = Http::timeout(30)->get("{$this->baseUrl}/tx/{$network}/{$txId}");

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Blockchain service error - Get TX Status', [
                    'network' => $network,
                    'txId' => $txId,
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Blockchain service exception - Get TX Status', [
                'network' => $network,
                'txId' => $txId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * دریافت لیست تراکنش‌ها با فیلترهای مختلف
     */
    public function getTransactions(array $filters = [])
    {
        try {
            $response = Http::timeout(30)->get("{$this->baseUrl}/transactions", $filters);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Transaction service error - Get Transactions', [
                    'filters' => $filters,
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Transaction service exception - Get Transactions', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

}

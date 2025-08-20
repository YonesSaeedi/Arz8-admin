<?php

namespace App\Http\Controllers\Reports\Shaparak;

use App\Http\Controllers\Controller;
use App\Models\Cryptocurrency;
use App\Models\CryptoNetwork;
use App\Models\TransactionCrypto;
use App\Models\User;
use Morilog\Jalali\CalendarUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class Table2Controller extends Controller
{
    const HEADERS = [
        'F1','F2','F3','F4','F5','F6','F7','F8','F9','F10',
        'F11','F12','F13','F14','F15','F16','F17','F18','F19','F20',
        'F21','F22'
    ];

    public function downloadCsv(Request $request)
    {
        if (isset($request->dateStart)) {
            try{
                $dateStart = CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
            }catch(\Exception $e){}
        }

        $transactions = TransactionCrypto::query()
            ->where('status', 'success')
            ->whereNull('id_order')->whereNull('id_trade')
            ->where('id_user','!=',1)
           /* ->where('users_transaction_crypto.description','!=','for wheel of luck')
            ->where('users_transaction_crypto.description','!=','Convert small inventories')
            ->where('users_transaction_crypto.description','!=','جایزه کمپین 100 + 100')
            ->where('users_transaction_crypto.description','!=','To use the gift card')
            ->where('users_transaction_crypto.description','!=','To issue a gift card')
            ->where('data','!=',null)*/
            ->where(function ($query)  {
                $query->whereNotNull('txid')->orWhere(function ($query)  {
                    $query->where('description','Transfer to another user')->orWhere('description','Transfer from another user');
                });
            })
            ->whereBetween('created_at', [
                isset($dateStart)? $dateStart : now()->subDay()->startOfDay(),
                isset($dateStop)? $dateStop : now()->startOfDay()
            ])
            //->whereNotIn('id',[2574147])
            //->whereIn('id',[1386105,1386106])
            ->get();

        $rows = [];

        foreach ($transactions as $transaction) {
            $rows[] = $this->mapTransactionToRow($transaction);
        }

        //dd($rows);

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, self::HEADERS);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=crypto_transactions'.CalendarUtils::strftime('Y-m-d', now()->subDay()).'.csv',
        ]);
    }

    private function mapTransactionToRow(TransactionCrypto $transaction): array
    {
        $user = User::find($transaction->id_user);
        $crypto = Cryptocurrency::find($transaction->id_crypto);
        $data = json_decode($transaction->data??'{}');
        $isDeposit = $transaction->type === 'deposit';
        $isTransfer = $transaction->description ==='Transfer to another user' || $transaction->description =='Transfer from another user';
        if (!$isTransfer) {
            $network = CryptoNetwork::find($transaction->id_network);
            if (!isset($network))
                $network = CryptoNetwork::where('symbol', $data->deposit->chain ?? null)->orWhere('name', $data->deposit->chain ?? null)->first();
            if (!isset($network))
                $network = CryptoNetwork::find(1);
        }

        if($isTransfer){
            $isTransferTo = User::find($data->senderUser??$transaction->id_user)->national_code;
            $isTransferFrom = User::find($data->receivingUser??$transaction->id_user)->national_code;
        }
        if (isset($data->info)) {
            $fromAddress = $data->info->from_address
                ?? ($data->withdraw->withdrawalId ?? '')
                ?? '';
            $toAddress   = $data->info->to_address
                ?? (property_exists($data->info, 'address') ? $data->info->address : '');
        } elseif (isset($data->deposit)) {
            $fromAddress = $data->deposit->from_address ?? 'kucoin';
            $toAddress   = $data->deposit->to_address
                ?? (property_exists($data->deposit, 'address') ? $data->deposit->address : '');
        } elseif ($isTransfer) {
            $fromAddress = $isTransferTo ?? '';
            $toAddress   = $isTransferFrom ?? '';
        }
        return [
            //$transaction->id,
            'azar',                                              // F1
            $isTransfer ?'03' :($isDeposit?'01':'02'),           // F2
            $isTransfer ? $transaction->id :$transaction->txid,  // F3
            isset($network->name)? self::getNetworkTransferCode($network->name) : '20',        // F4
            $network->name??'Internal',                                      // F5
            $isDeposit?'10':($isTransfer?'10':'11'),                                                // F6
            $isTransfer?$isTransferTo: ($isDeposit?$user->national_code:'14012604037'),    // F7
            !$isDeposit?'10':($isTransfer?'10':'11'),                                                // F8
            $isTransfer?$isTransferFrom:( !$isDeposit?$user->national_code:'14012604037'),   // F9
            $crypto->symbol,                                     // F10
            $transaction->amount,                                // F11
            $transaction->created_at->format('His'),             // F12
            $transaction->created_at->format('Ymd'),             // F13
            $transaction->updated_at->format('His'),             // F14
            $transaction->updated_at->format('Ymd'),             // F15
            null,null,                                           // F16 - F17
            $fromAddress,                            // F18
            $toAddress,                            // F19
            $isDeposit ? (isset($data->deposit->memo)?$data->deposit->memo:$transaction->destination_tag) : (isset($data->deposit->memo)?$data->deposit->memo:$transaction->destination_tag),                                          // F20
            $transaction->withdraw_fee??0,                                                   // F21
            '01',                                                // F22
        ];

    }

    private function getNetworkTransferCode(string $networkName): string
    {
        $map = [
            'BTC' => '01',
            'ERC20' => '02',
            'TRC20' => '03',
            'BEP20' => '04',
        ];

        foreach ($map as $key => $code) {
            if (stripos($networkName, $key) !== false) {
                return $code;
            }
        }

        return '20'; // کد پیش‌فرض برای "سایر"
    }

}

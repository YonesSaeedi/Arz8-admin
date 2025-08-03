<?php

namespace App\Http\Controllers\Reports\Shaparak;

use App\Http\Controllers\Controller;
use App\Models\TransactionInternal;
use App\Models\User;
use App\Models\UserCardBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Morilog\Jalali\CalendarUtils;


class Table1Controller extends Controller
{
    const HEADERS = [
        'F1','F2','F3','F4','F5','F6','F7','F8','F9','F10',
        'F11','F12','F13','F14','F15','F16','F17','F18','F19','F20',
        'F21','F22','F23','F24','F25','F26','F27',
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


        $transactions = TransactionInternal::query()
            ->where('status', 'success')
            ->where(function ($query)  {
                $query->whereNotNull('payment_gateway')->orWhere('description', 'like', '%Deposit with ID%');
            })
            ->where(function ($query)  {
                $query->whereNotNull('id_cardbank')->orWhereNotNull('data->resultVerify->cardNumber');
            })
            ->where('payment_gateway', '!=','manual')
            ->where('description', 'not like', '%Receipt%')
            ->whereNull('id_order')
           /* ->whereBetween('created_at', [
                '2024-09-22',
                '2025-04-28'
            ])*/
            ->whereBetween('created_at', [
                isset($dateStart)? $dateStart : now()->subDay()->startOfDay(),
                isset($dateStop)? $dateStop : now()->startOfDay()
            ])
            ->whereNotIn('id_user',[112785,6710])
            //->whereIn('id',[1849118])
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
            'Content-Disposition' => 'attachment; filename=rial_transactions'.CalendarUtils::strftime('Y-m-d', now()->subDay()).'.csv',
        ]);
    }

    private function mapTransactionToRow(TransactionInternal $transaction): array
    {
        $user = User::find($transaction->id_user);
        $data = json_decode($transaction->data??'{}');
        try {
            if($transaction->id_cardbank)
                $card = UserCardBank::find($transaction->id_cardbank);
            else{

                $cardNumber = $data->resultVerify->cardNumber;
                $card = UserCardBank::where('card_number', 'like', str_replace('*','%',$cardNumber))->first();
            }

            $isDeposit = $transaction->type === 'deposit';
            $gateway = $transaction->payment_gateway;

            if($isDeposit){
                $ibanCompany = ($gateway === 'zibal' ? '170540205720101399073601' : ($gateway === 'paystar' ? '480190000000120137445003' : ''));
                $ibanUser = ($gateway ==='zibal'|| $gateway=== 'paystar' ? $this->cleanIban($card->card_number) : $this->cleanIban($card->iban));
            }else{
                $ibanCompany = ($gateway === 'baje' ? '170540205720101399073601' : ($gateway === 'paystar' ? '480190000000120137445003' : 'zibal'));
                $ibanUser = ($this->cleanIban($card->iban));
            }
            $bankCodeCompony = ($gateway === 'zibal'||$gateway === 'baje' ? '054' : ($gateway === 'paystar' ? '019' : ''));
            $bankCodeUser =  $this->getBankCodeFromName($card->bank_name);
        }catch (\Exception $e){
            dd($transaction);
        }




        $sender = $isDeposit
            ? [$user->national_code, $ibanUser, $bankCodeUser]
            : ['14012604037', $ibanCompany, $bankCodeCompony];

        $receiver = !$isDeposit
            ? [$user->national_code, $ibanUser, $bankCodeUser]
            : ['14012604037', $ibanCompany, $bankCodeCompony];


      /*  if($isDeposit && $gateway=='zibal')
            dd( $ibanUser , $ibanCompany,$transaction->id,$gateway,$sender,$receiver);*/


        return [
            //$transaction->id,
            'azar',                                              // F1
            hash('sha256', $transaction->created_at),            // F2
            isset($transaction->trace_number)?$transaction->trace_number:((isset($data->baje->refCode)?$data->baje->refCode:(isset($data->paystar->transaction_trace)?$data->paystar->transaction_trace:''))),                                                  // F3
            $transaction->id,                                    // F4
            '10',                                                // F5
            $transaction->created_at->format('His'),             // F6
            $transaction->created_at->format('Ymd'),             // F7
            '11',                                                // F8
            $transaction->amount * 10,                           // F9
            $isDeposit ? '19' : '19',                            // F10
            now()->format('His'),                                // F11
            now()->format('Ymd'),                                // F12
            $isDeposit ? '10' : '11',                            // F13
            null, null, null, null, null,                        // F14 - F18
            $isDeposit ? '10' : '11',                            // F19
            $sender[0],                                          // F20 - کد ملی
            $sender[1],                                          // F21 - IBAN
            $sender[2],                                          // F22 - کد بانک
            !$isDeposit ? '10' : '11',                           // F23
            $receiver[0],                                        // F24
            $receiver[1],                                        // F25
            $receiver[2],                                        // F26
            null                                                 // F27
        ];

    }

    private function getBankCodeFromName(string $bankName): ?string
    {
        $bankCodes = [
            'ملی ایران' => '017',
            'سپه' => '015',
            'توسعه صادرات ایران' => '020',
            'صنعت و معدن' => '011',
            'کشاورزی' => '016',
            'مسکن' => '014',
            'پست بانک' => '021',
            'توسعه تعاون' => '022',
            'اقتصاد نوین' => '055',
            'پارسیان' => '054',
            'پاسارگاد' => '057',
            'کارآفرین' => '053',
            'سامان' => '056',
            'سینا' => '059',
            'سرمایه' => '058',
            'شهر' => '061',
            'دی' => '066',
            'صادرات' => '019',
            'ملت' => '012',
            'تجارت' => '018',
            'رفاه' => '013',
            'گردشگری' => '064',
            'ایران زمین' => '069',
            'خاورمیانه' => '078',
            'ایران ونزوئال' => '095',
            'آینده' => '062',
            'اعتباری توسعه' => '051',
            'اعتباری ملل' => '075',
            'اعتباری نور' => '080',
            'قرض الحسنه مهر ایران' => '060',
            'قرض الحسنه رسالت' => '070',
        ];

        foreach ($bankCodes as $name => $code) {
            if (mb_strpos($bankName, $name) !== false) {
                return $code;
            }
        }

        return null;
    }

    private function cleanIban(string $iban): string
    {
        // حذف فاصله، خط‌تیره و حروف IR یا ir
        $iban = strtoupper($iban);
        $iban = str_replace([' ', '-', 'IR'], '', $iban);

        // تبدیل اعداد فارسی به انگلیسی
        $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $english = ['0','1','2','3','4','5','6','7','8','9'];

        return str_replace($persian, $english, $iban);
    }

}

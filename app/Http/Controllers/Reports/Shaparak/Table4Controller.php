<?php
namespace App\Http\Controllers\Reports\Shaparak;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Morilog\Jalali\CalendarUtils;

class Table4Controller extends Controller
{
    const HEADERS = [
        'F1','F2','F3','F4'
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


        // استفاده از chunk برای کاهش مصرف حافظه
        $cryptoMap = DB::table('cryptocurrency')->pluck('symbol', 'id');

        $callback = function () use ($cryptoMap) {
            $file = fopen('php://output', 'w');
            fputcsv($file, self::HEADERS);

            DB::table('users_wallets')
                ->join('users', 'users.id', '=', 'users_wallets.id_user')
                ->select('users_wallets.id_crypto', 'users.national_code', 'users_wallets.balance')
                ->where('users_wallets.type', Wallet::TYPE_ASSET)
                ->where('users_wallets.balance', '>', 0)
                ->whereNotNull('users.national_code')
                ->whereNotIn('users_wallets.id_user', [43, 638, 1])
                ->orderBy('users_wallets.id_user')
                ->chunk(1000, function ($records) use ($file, $cryptoMap) {
                    foreach ($records as $row) {
                        fputcsv($file, [
                            'azar',
                            '10',
                            $row->national_code,
                            json_encode([
                                $cryptoMap[$row->id_crypto] ?? '',
                                ($row->balance < 0.0001 && $row->balance != 0) ? sprintf('%f', $row->balance) : $row->balance
                            ])
                        ]);
                    }
                });

            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=user_balances'.CalendarUtils::strftime('Y-m-d', now()->subDay()).'.csv',
        ]);
    }
}

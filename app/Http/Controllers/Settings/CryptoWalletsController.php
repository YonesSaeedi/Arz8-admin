<?php
namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Exchange\ExchangeApi;
use App\Models\CryptoWallets;
use App\Models\TransactionCrypto;
use DB;
use Illuminate\Http\Request;

class CryptoWalletsController extends ExchangeApi
{
    function listWallets(Request $request){
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $cryptos = CryptoWallets::query()
            ->where('active', 1)
            ->leftJoin('cryptocurrency', 'cryptocurrency.id', 'cryptocurrency_wallets.id_crypto')
            ->leftJoin('cryptocurrency_network', 'cryptocurrency_network.id', 'cryptocurrency_wallets.id_network')
            ->leftJoin('users_wallets_crypto_deposit', 'users_wallets_crypto_deposit.id_wallet', 'cryptocurrency_wallets.id')
            ->select(
                'cryptocurrency_wallets.*',
                'cryptocurrency.symbol as symbol',
                'cryptocurrency.percent',
                'cryptocurrency.name',
                'icon',
                'cryptocurrency_network.name as net_name',
                'cryptocurrency_network.symbol as net_symbol',
                DB::raw('COUNT(users_wallets_crypto_deposit.id) as count_wallet')
            )
            ->groupBy('cryptocurrency_wallets.id');

        $cryptos = self::filters($cryptos, $request);

        $paginated = $cryptos->paginate($limit);

        $addresses = $paginated->pluck('address')->toArray();
        $sums = TransactionCrypto::whereIn('destination', $addresses)
            ->where(['type'=>'deposit','status'=>'success'])
            ->selectRaw('destination, SUM(amount) as total')
            ->groupBy('destination')
            ->pluck('total','destination')
            ->toArray();

        foreach ($paginated as $item) {
            $item->updated = $this->convertDate($item->updated_at, 'd F Y H:i');
            $item->amount_wallet = round($sums[$item->address] ?? 0, $item->percent);
        }

        $result = (object)[
            'lists' => $paginated->items(),
            'total' => $paginated->total(),
        ];

        return response()->json($result);

    }



    private function filters($cryptos,$request,$sum = false){
        $search = $request->search;
        if(!$sum)
            switch ($request->sortBy){
                case 'id': $sortBy = 'cryptocurrency.id'; break;
                case 'logo': $sortBy = 'cryptocurrency.id'; break;
                case 'symbol': $sortBy = 'cryptocurrency.symbol'; break;
                case 'network': $sortBy = 'cryptocurrency_network.id'; break;
                case 'TxId': $sortBy = 'for_txid'; break;
                case 'updated': $sortBy = 'cryptocurrency_wallets.created_at'; break;
                case 'Count': $sortBy = 'count(users_wallets_crypto_deposit.id)'; break;
                default: $sortBy = $request->sortBy;
            }

        if (!empty($search)) {
            $fields = [ 'cryptocurrency.name','cryptocurrency.symbol','cryptocurrency_network.name','cryptocurrency.locale','address','address_tag'];
            $cryptos = $cryptos->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }

        if(isset($sortBy) && !$sum)
            $cryptos->orderByRaw('abs('.$sortBy.') '.($request->sortDesc?'desc':'asc'));
        return $cryptos;
    }
}

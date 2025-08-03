<?php

namespace App\Http\Controllers;
use \Binance\API as Binance;
class BinanceController extends Binance
{
    public function depositAddressNetwork(string $asset, $network = null)
    {
        $options = [
            "sapi" => true,
            "coin" => $asset,
        ];
        if (is_null($network) === false && empty($network) === false) {
            $options['network'] = $network;
        }
        return $this->httpRequest("v1/capital/deposit/address", "GET", $options, true);
    }

    public function depositHistory(string $asset = null, array $params = [])
    {
        $params["sapi"] = true;
        if (is_null($asset) === false) {
            $params['coin'] = $asset;
        }
        $return = $this->httpRequest("v1/capital/deposit/hisrec", "GET", $params, true);
        // Adding for backwards compatibility with wapi
        foreach ($return as $key=>$item) {
            $return[$key]['asset'] = $item['coin'];
        }
        return $return;
    }
}

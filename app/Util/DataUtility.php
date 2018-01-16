<?php
/**
 * Contains miscellaneous utility functions for handling market data.
 *
 * @author Austin Jones
 */

namespace App\Util;


use Illuminate\Support\Facades\Redis;

class DataUtility
{
    public function getRecentData($data_source = 'ohlcv', $timeframe = '2 hours ago', $exchange = 'bitfinex') {
        $timeframe = strtotime($timeframe);
        $data = [];

        // Pull data from Redis
        // TODO Implement spread
        switch($data_source)
        {
            case 'ohlcv':
                $keys = Redis::keys($exchange . '-' . $data_source);
                foreach ($keys as $key) {
                    array_push($data, json_decode(Redis::get($key)));
                }
                break;
        }

        // Decide to pull data from MySQL (older than 24hrs)
        // TODO implement
        if(time() - $timeframe > 86400) {

            // Pull data from MySQL

        }

        return $data;
    }
}
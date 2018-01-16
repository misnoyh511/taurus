<?php
/**
 * This script pulls market data continually from a generic Exchange, and stores it on the cache server. After 24 hours, it migrates the data to the database server.
 * This script should run continually, and be monitored by the Linux supervisor daemon.
 *
 * Redis key patterns and channel names should match each other for a given exchange, and follow the format <exchange>-<data-source>(-<timestamp>). Key patterns have timestamps.
 *
 * Supported exchanges:
 * - Bitfinex
 *
 * Supported data sources:
 * - 'spread' (Ask/bid spread)
 * - 'ohlcv' (OHLCV candlestick charts)
 *
 * e.g. the channel name for ask/bid spread data on Bitfinex is 'bitfinex-spread'.
 *
 *
 * @author Austin Jones (@optimalpandemic)
 */

namespace App\Console\Commands;

use ccxt\bitfinex;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Console\Command;
use React\EventLoop\Factory;

abstract class ExchangeWebsocketCommand extends Command
{
    protected $signature = 'taurus:websocket_bitfinex';

    protected $description = 'Connect to the Bitfinex websocket and store OHLC data';

    protected $exchange;

    protected $loop;

    function __construct() {
        parent::__construct();
        $this->loop = Factory::create();
        $this->exchange = new bitfinex();
    }

    /**
     * Pulls the best market price from exchange for BTC/USD and calculates bidask spread. Stores data in Redis.
     *
     * @param $exchange_name    string Name of exchange
     * @param $delay            int     Rate-limit delay in seconds
     * @param $symbol           string  Trading symbol to pull data for (default 'BTC/USD')
     */
    protected function fetchSpread($exchange_name, $delay, $symbol = 'BTC/USD') {

        $this->loop->addPeriodicTimer($delay, function() use($exchange_name, $symbol) {
            $orderbook = $this->exchange->fetchOrderBook($symbol);
            $bid = count($orderbook['bids']) ? $orderbook['bids'][0][0] : null;
            $ask = count($orderbook['asks']) ? $orderbook['asks'][0][0] : null;
            $spread = ($bid && $ask) ? $ask - $bid : null;
            $result = array(
                'bid' => $bid,
                'ask' => $ask,
                'spread' => $spread,
                'timestamp' => time()
            );

            $channel = $exchange_name . '-spread-';
            $key = $channel . $result['time'];

            $this->storeData($key, $result, $channel);
        });

    }

    /**
     * Pulls Open, High, Low, Close, Volume data (candles) for a certain timeframe.
     *
     * @param $exchange_name    string Name of exchange
     * @param integer $delay    int    Rate-limit delay in seconds
     * @param string $symbol    string Trading symbol to pull data for (default 'BTC/USD')
     * @param string $timeframe int    How far back to pull candles (default one minute '1m')
     */
    protected function fetchOHLCV($exchange_name, $delay, $symbol = 'BTC/USD', $timeframe = '1m') {
        if($this->exchange->hasFetchOHLCV) {
            $this->loop->addPeriodicTimer($delay, function () use ($exchange_name, $symbol, $timeframe) {
                $data = $this->exchange->fetch_ohlcv($symbol, $timeframe);
                $channel = $exchange_name . '-ohlcv-';
                $key = $channel . $data[0]; // Timestamp is [0]
                $this->storeData($key, $data, $channel);
            });
        }
    }

    protected function storeData($key, $data, $channel) {
        //Store data in cache
        Redis::set($key, json_encode($data));

        // Notify database management channel of cache update
        Redis::publish($channel, $key);
    }


    /**
     * Periodically checks that Redis only stores ~24h of data, all older is in MySQL.
     * Is notified by exchange-specific Redis channel when price data is pulled.
     *
     * @param $data_source  string  Market data source (see class PHPDoc comment)
     * @param $pattern      string  Search pattern for Redis keys
     * @param $exchange     string  The name of the exchange the data is from. Should match the list of 'id's on GitHub (https://github.com/ccxt/ccxt/wiki/Exchange-Markets).
     */
    protected function manageDatabases($data_source, $pattern, $exchange) {
        $keys = Redis::keys($pattern);
        foreach($keys as $key) {

            // Only move if older than 24h
            if(time() - $key > 86400) {
                $data = json_decode(Redis::get($key));

                // Create MySQL entry based on data source
                switch($data_source)
                {
                    case 'spread':
                        DB::insert("INSERT into spreads (ask, bid, spread, created_time, exchange) VALUES (?,?,?,?)", [$data['ask'], $data['bid'], $data['spread'], $data['time'], $exchange]);
                        break;
                    case 'ohlcv' :
                        DB::insert("INSERT into candles (open_price, high_price, low_price, close_price, volume, created_time, exchange) VALUES (?,?,?,?,?,?,?)", [$data[1], $data[2], $data[3], $data[4], $data[5], $data[0], $exchange]);
                        break;
                }


                // Delete Redis key
                Redis::del($key);
            }
        }

    }

    public abstract function handle();
}
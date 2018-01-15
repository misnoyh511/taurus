<?php

/**
 * This script pulls market data continually from Bitfinex, and stores it on the cache server. After 24 hours, it migrates the data to the database server.
 * This script should run continually, and be monitored by the Linux supervisor daemon.
 *
 * @author Austin Jones (@optimalpandemic)
 */

namespace App\Console\Commands;

use ccxt\bitfinex;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use React\EventLoop\Factory;

/**
 * Class BitfinexWebsocketCommand
 * @package App\Console\Commands
 */
class BitfinexWebsocketCommand
{

    protected $signature = 'taurus:websocket_bitfinex';

    protected $description = 'Connect to the Bitfinex websocket and store OHLC data';

    private $bitfinex;

    private $loop;

    /**
     * BitfinexWebsocketCommand constructor.
     */
    function __construct()
    {
        $this->bitfinex = new bitfinex();
        $this->bitfinex->load_markets();
        $this->loop = Factory::create();
    }

    /**
     * Pulls the best market price from Bitfinex for BTC/USD and calculates bidask spread. Stores data in Redis.
     *
     * @param $delay    int     Rate-limit delay in seconds
     * @param $symbol   string  Trading symbol to pull data for (default 'BTC/USD')
     */
    private function fetchSpread($delay, $symbol = 'BTC/USD') {

        $this->loop->addPeriodicTimer($delay, function() use($symbol) {
            $orderbook = $this->bitfinex->fetchOrderBook($symbol);
            $bid = count($orderbook['bids']) ? $orderbook['bids'][0][0] : null;
            $ask = count($orderbook['asks']) ? $orderbook['asks'][0][0] : null;
            $spread = ($bid && $ask) ? $ask - $bid : null;
            $result = array(
                'bid' => $bid,
                'ask' => $ask,
                'spread' => $spread,
                'timestamp' => time()
            );

            $this->storeSpread($result);
        });

    }

    /**
     * Stores spread array in Redis as encoded JSON, by timestamp.
     *
     * @param $spread   array   Array of price spreads: (bid price, ask price, spread, timestamp)
     */
    private function storeSpread($spread) {
        //Store data in cache
        Redis::set($spread['time'], json_encode($spread));

        // Notify database management channel of cache update
        Redis::publish('price-data', $spread['time']);
    }

    /**
     * Periodically checks that Redis only stores ~24h of data, all older is in MySQL
     */
    private function manageDatabases() {
        $keys = Redis::keys('*');
        foreach($keys as $key) {
            // Only move if older than 24h
            if(time() - $key > 7200) {
                $data = json_decode(Redis::get($key));

                // Create MySQL entry
                DB::insert("INSERT into spreads (ask, bid, spread, created_time) VALUES (?,?,?,?)", [$data['ask'], $data['bid'], $data['spread'], $data['time']]);

                // Delete Redis key
                Redis::del($key);
            }
        }

    }

    /**
     *
     */
    public function handle() {
        Redis::subscribe(['price-data'], function() {
            $this->manageDatabases();
        });
        $this->fetchSpread(env('BITFINEX_RATE_LIMIT'));
        $this->manageDatabases();
        $this->loop->run();
    }
}
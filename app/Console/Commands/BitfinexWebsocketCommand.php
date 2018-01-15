<?php

/**
 * This script pulls order book data continually from Bitfinex, and stores it on the cache server. After 24 hours, it migrates the data to the database server.
 * This script should run continually, and be monitored by the Linux supervisor daemon.
 *
 * @author Austin Jones (@optimalpandemic)
 */

namespace App\Console\Commands;

include $_SERVER['DOCUMENT_ROOT'] . '/vendor/ccxt/ccxt/ccxt.php';

class BitfinexWebsocketCommand
{
    protected $signature = 'taurus:websocket_bitfinex';

    protected $description = 'Connect to the Bitfinex websocket and store OHLC data';

    protected $bitfinex;

    function __construct()
    {
        $this->bitfinex = new \ccxt\bitfinex();
        $this->bitfinex->load_markets();
    }

    // TODO store this in cache
    public function fetchOrderBook() {
        $delay = 2000000;
        foreach($this->bitfinex->markets as $symbol => $market) {
            var_dump($this->bitfinex->fetch_order_book($symbol));
            usleep($delay);
        }
    }

    public function handle() {
        $this->fetchOrderBook();
    }
}
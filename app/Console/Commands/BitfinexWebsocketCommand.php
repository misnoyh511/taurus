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
class BitfinexWebsocketCommand extends ExchangeWebsocketCommand
{

    protected $signature = 'taurus:websocket_bitfinex';

    protected $description = 'Connect to the Bitfinex websocket and store OHLC data';

    private $exchange_name = 'bitfinex';

    /**
     * BitfinexWebsocketCommand constructor.
     */
    function __construct()
    {
        parent::__construct();
        $this->exchange = new bitfinex();
        $this->exchange->load_markets();
    }

    /**
     *
     */
    public function handle() {
        Redis::subscribe(['bitfinex-spread'], function() {
            $this->manageDatabases('bitifinex-spread-*', $this->exchange_name);
        });
        $this->fetchSpread($this->exchange_name, env('BITFINEX_RATE_LIMIT'));
        $this->loop->run();
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: austin
 * Date: 1/15/2018
 * Time: 10:12 PM
 */

namespace App\Strategies;


use App\Util\DataUtility;

abstract class Strategy
{
    protected $recentData;
    protected $symbol;
    protected $exchange;

    function __construct($symbol = 'BTC/USD', $exchange = 'gdax') {
        $this->recentData = new DataUtility();
        $this->symbol = $symbol;
        $this->exchange = new $exchange();
    }

    /**
     * Executes buy and sell orders on $this->exchange.
     */
    protected function trade(){

    }

    /**
     * Performs calculations based on strategy algorithm, and makes trade decisions.
     */
    abstract function evaluate();
}
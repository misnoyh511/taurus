<?php
/**
 * A simple strategy that determines if an instrument is overbought or underbought, based on CCI, CMO, and MFI.
 *
 * @author Austin Jones (@optimalpandemic)
 */

namespace App\Strategies;


use App\Util\DataUtility;

class BasicStrategy extends Strategy
{
    private $overbought;
    private $underbought;

    public function evaluate() {

        // TODO Sort data for cci
        $cci;

        // TODO Sort data for cmo
        $cmo;

        // TODO Sort data for mfi
        $mfi;

        // instrument is overbought, we will short
        if ($cci == -1 && $cmo == -1 && $mfi == -1) {
            $this->overbought = 1;
        }
        // It is underbought, we will hodl
        if ($cci == 1 && $cmo == 1 && $mfi == 1) {
            $this->underbought = 1;
        }

        $this->trade();
    }
}
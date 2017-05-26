<?php

namespace Jamosaur\FMS\Messages;

use Carbon\Carbon;
use Jamosaur\FMS\Translator;
use Jamosaur\FMS\Unit\State;

class FMSF3
{
    public $unitId;
    public $ignition;
    public $engine;
    public $unitState;
    public $gpsType;
    public $gpsSatellites;
    public $gpsLat;
    public $gpsHDOP;
    public $gpsLng;
    public $utcTimestamp;
    public $speed;
    private $message;
    public $decompressed;

    /**
     * FMSF3 constructor.
     *
     * Removes the first 7 characters from the message.
     * These are always FMSF3xx. xx refers to an internal message number from the unit.
     *
     * @param $message
     */
    function __construct($message)
    {
        $this->message      = substr($message, 7);
        $this->decompressed = $this->decompress($this->message);
        $this->decodeMessage();
    }

    public function decompress($message)
    {
        $decompressedMessage = '';
        for ($i = 0; $i < strlen($message); $i++) {
            $decompressedMessage .= Translator::$characterMap[ord($message[$i])];
        }

        return $decompressedMessage;
    }

    private function decodeMessage()
    {
        $this->unitId = (int)substr($this->decompressed, 0, 6);

        $ignitionAndEngine = substr($this->decompressed, 6, 1);
        $this->ignition = (($ignitionAndEngine & 1) != 0) ? true : false;
        $this->engine = (($ignitionAndEngine & 2) != 0) ? true : false;

        $latLngSigns = substr($this->decompressed, 7, 1);
        $this->gpsLat = (($latLngSigns & (1 << 1)) != 0) ? '+' : '-';
        $this->gpsLng = (($latLngSigns & (1 << 2)) != 0) ? '+' : '-';

        $unitState = substr($this->decompressed, 8, 1);
        $this->unitState = State::$unitState[$unitState];

        $this->gpsType = substr($this->decompressed, 10, 1);
        $this->gpsSatellites = substr($this->decompressed, 11, 1);

        $this->gpsLat .= substr($this->decompressed, 12, 8);
        $this->gpsHDOP = substr($this->decompressed, 20, 3);
        $this->gpsLng .= substr($this->decompressed, 23, 9);
        $timestamp = substr($this->decompressed, 32, 12);
        $h = $timestamp[0] . $timestamp[1];
        $min = $timestamp[2] . $timestamp[3];
        $s = $timestamp[4] . $timestamp[5];
        $d = $timestamp[6] . $timestamp[7];
        $m = $timestamp[8] . $timestamp[9];
        $y = $timestamp[10] . $timestamp[11];
        $this->utcTimestamp = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            '20' . $y . '-' . $m . '-' . $d . ' ' . $h . ':' . $min . ':' . $s
        );
        $this->speed = (float) (substr($this->decompressed, 45, 5)/100);
    }

}

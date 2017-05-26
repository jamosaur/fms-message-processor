<?php

namespace Jamosaur\FMS;

use Jamosaur\FMS\Messages\FMSF3;

class Processor
{
    const F3 = 'FMSF3';
    private $data;

    /**
     * Create a new Processor Instance
     *
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $this->process($data);
    }

    private function process($data)
    {
        // Remove the 'ALL: ' that can come in from mongo logs.
        $messages = str_replace('ALL: ', '', $data);

        // Change the hex into ascii characters
        $messages = explode(PHP_EOL, hex2bin($messages));

        $messageArray = [];

        foreach ($messages as $message) {
            if (strstr($message, self::F3)) {
                // This is an FMS F3 Message.
                $messageArray[] = new FMSF3($message);
            }
        }

        return $messageArray;
    }
}

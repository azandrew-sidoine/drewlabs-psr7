<?php

namespace Drewlabs\Psr7;

use Drewlabs\Psr7Stream\CreatesStream;
use Drewlabs\Psr7Stream\Stream;

class CreatesJSONStream implements CreatesStream
{
    /**
     * 
     * @var array|\JsonSerializable|object
     */
    private $value;

    /**
     * 
     * @var int
     */
    private $depth = 512;

    /**
     * 
     * @var int
     */
    private $flags = JSON_PRETTY_PRINT;

    /**
     * Create a JSON encoder stream
     * 
     * @param array|\JsonSerializable|object $value 
     * @param null|int $depth 
     * @param int $flags 
     * @return void 
     */
    public function __construct($value, ?int $depth = null, int $flags = 0)
    {
        $this->value = $value ?? [];
        $this->depth = $depth ?? 512;
        $this->flags =  $flags ?? JSON_PRETTY_PRINT;
    }

    public function createStream()
    {
        return Stream::new(@json_encode($this->value, $this->flags, $this->depth));
    }
}

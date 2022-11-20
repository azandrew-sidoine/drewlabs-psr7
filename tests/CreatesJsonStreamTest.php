<?php

use Drewlabs\Psr7\CreatesJSONStream;
use Drewlabs\Psr7\Streams;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class CreatesJsonStreamTest extends TestCase
{

    public function test_creates_json_stream_operator()
    {
        $stream = Streams::lazy(new CreatesJSONStream([]));

        $this->assertInstanceOf(StreamInterface::class, $stream->getStream());
    }

    public function test_creates_json_stream_operator_to_string()
    {
        $requests = require __DIR__ . '/requests.php';
        $stream = Streams::lazy(new CreatesJSONStream($requests['json']));
        $this->assertEquals(json_encode($requests['json']), $stream->__toString());
    }
}
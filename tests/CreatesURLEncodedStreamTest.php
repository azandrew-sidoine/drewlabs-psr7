<?php

use Drewlabs\Psr7\CreatesURLEncodedStream;
use Drewlabs\Psr7\Streams;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class CreatesURLEncodedStreamTest extends TestCase
{

    public function test_creates_url_encoded_stream_operator()
    {
        $stream = Streams::lazy(new CreatesURLEncodedStream([]));

        $this->assertInstanceOf(StreamInterface::class, $stream->getStream());
    }

    public function test_creates_url_encoded_operator_to_string()
    {
        $requests = require __DIR__ . '/requests.php';
        $stream = Streams::lazy(new CreatesURLEncodedStream($requests['body']));
        $this->assertEquals((new CreatesURLEncodedStream($requests['body']))->getEncodedData(), $stream->__toString());
    }

}
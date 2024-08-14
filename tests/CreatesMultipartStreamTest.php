<?php

use Drewlabs\Psr7\CreatesMultipartStream;
use Drewlabs\Psr7\Streams;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class CreatesMultipartStreamTest extends TestCase
{

    public function test_creates_multipart_stream_operator()
    {
        $stream = Streams::lazy(new CreatesMultipartStream([]));
        $this->assertInstanceOf(StreamInterface::class, $stream->getStream());
    }

    public function test_creates_multipart_operator_to_string()
    {
        $requests = require __DIR__ . '/requests.php';
        $stream = Streams::lazy(new CreatesMultipartStream($requests['multipart']));
        $this->assertNotEmpty($stream->__toString());
    }

    public function test_creates_multipart__spfile_info_to_string()
    {
        $stream = Streams::lazy(new CreatesMultipartStream([
            [
                'name' => 'post_id',
                'contents' => '2'
            ],
            [
                'name' => 'class',
                'contents' => new \SplFileInfo(__DIR__ . '/' . __CLASS__ . '.php'),
            ],
            [
                'name' => 'files',
                'contents' => [
                    new \SplFileInfo(__DIR__ . '/requests.php'),
                    new \SplFileInfo(__DIR__ . '/' . __CLASS__ . '.php'),
                ]
            ]
        ]));
        $this->assertNotEmpty($stream->__toString());
    }
}
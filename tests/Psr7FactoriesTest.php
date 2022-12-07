<?php

use Drewlabs\Psr7\Factories\RequestFactory;
use Drewlabs\Psr7\Factories\ResponseFactory;
use Drewlabs\Psr7\Factories\ServerRequestFactory;
use Drewlabs\Psr7\Factories\UploadedFileFactory;
use Drewlabs\Psr7\Factories\UriFactory;
use Drewlabs\Psr7Stream\StreamFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Psr7FactoriesTest extends TestCase
{

    public function test_stream_factory()
    {
        $expected = (new StreamFactory)->createStream('Hello World!');
        $this->assertInstanceOf(StreamInterface::class, $expected);
        $this->assertEquals($expected->__toString(), 'Hello World!');
        $this->assertEquals($expected->getSize(), strlen('Hello World!'));

        $expected2 = (new StreamFactory)->createStreamFromFile(__DIR__ . '/Resources/foo.txt', 'r');
        $this->assertEquals($expected2->__toString(), "Foobar\n");
    }

    public function test_request_factory()
    {
        $expected = (new RequestFactory)->createRequest('GET', 'https://127.0.0.1:8000/api/posts');
        $this->assertInstanceOf(RequestInterface::class, $expected);
        $this->assertEquals('GET', $expected->getMethod());
        $this->assertEquals('https', $expected->getUri()->getScheme());
        $this->assertEquals('8000', $expected->getUri()->getPort());
        $this->assertEquals('127.0.0.1', $expected->getUri()->getHost());
        $this->assertEquals('/api/posts', $expected->getUri()->getPath());
    }

    public function test_response_factory()
    {
        $expected = (new ResponseFactory)->createResponse(404, 'Not Found');
        $this->assertInstanceOf(ResponseInterface::class, $expected);
        $this->assertEquals(404, $expected->getStatusCode());
        $this->assertEquals('Not Found', $expected->getReasonPhrase());
        $expected = (new ResponseFactory)->createResponse(405);
        $this->assertEquals('Method Not Allowed', $expected->getReasonPhrase());
    }

    public function test_server_request_factory()
    {
        $expected = (new ServerRequestFactory)->createServerRequest('POST', 'https://127.0.0.1:8000/api/posts');
        $this->assertInstanceOf(ServerRequestInterface::class, $expected);
        $this->assertEquals('POST', $expected->getMethod());
    }

    public function test_uploaded_file_factory()
    {
        $stream = (new StreamFactory)->createStreamFromFile(__DIR__ . '/Resources/foo.txt');
        $expected = (new UploadedFileFactory)->createUploadedFile($stream, 0, UPLOAD_ERR_OK);
        $this->assertSame($stream, $expected->getStream());
        $this->assertInstanceOf(StreamInterface::class, $expected->getStream());
        $this->assertEquals('Foobar' . PHP_EOL, $stream->__toString());
    }

    public function test_uri_factory()
    {
        $expected = (new UriFactory)->createUri('https://127.0.0.1:8000/api/posts');
        $this->assertInstanceOf(UriInterface::class, $expected);
        $this->assertEquals('https', $expected->getScheme());
        $this->assertEquals('8000', $expected->getPort());
        $this->assertEquals('127.0.0.1', $expected->getHost());
        $this->assertEquals('/api/posts', $expected->getPath());
    }
}
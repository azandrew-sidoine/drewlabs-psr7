<?php

use Drewlabs\Psr7\Request;
use Drewlabs\Psr7\Uri;
use Drewlabs\Psr7Stream\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

class RequestTest extends TestCase
{

    public function test_create_request_class()
    {
        $this->assertInstanceOf(RequestInterface::class, new Request());
    }

    public function test_request_with_method_call()
    {
        $request = new Request();
        $request = $request->withMethod('PUT');
        $this->assertEquals('PUT', $request->getMethod());
    }

    public function test_request_with_header_call()
    {
        $request = new Request();
        $request = $request->withHeader('Content-Type', 'application/json');
        $this->assertEquals(['application/json'], $request->getHeader('content-type'));
    }

    public function test_request_with_added_header_call()
    {
        $request = new Request();
        $request = $request->withHeader('Accept-Encoding', 'gzip');
        $request = $request->withAddedHeader('Accept-Encoding', 'deflate');
        $this->assertEquals(['gzip', 'deflate'], $request->getHeader('accept-encoding'));
    }
    
    public function test_request_with_body_call()
    {
        $request = new Request();
        $request = $request->withBody(Stream::new());
        $this->assertInstanceOf(StreamInterface::class, $request->getBody());
    }
    
    public function test_request_with_protocol_version_call()
    {
        $request = new Request();
        $request = $request->withProtocolVersion('2.0');
        $this->assertEquals('2.0', $request->getProtocolVersion());
    }
    
    public function test_request_with_query_call()
    {
        $request = new Request();
        $uri = Uri::new('https://127.0.0.1:8000/api/posts?post_id=21&title=Hello World');
        $request = $request->withUri($uri);
        $this->assertEquals($uri, $request->getUri());
        $this->assertEquals('https', $request->getUri()->getScheme());
        $this->assertEquals('8000', $request->getUri()->getPort());
        $this->assertEquals('127.0.0.1', $request->getUri()->getHost());
        $this->assertEquals('/api/posts', $request->getUri()->getPath());
        $this->assertEquals('post_id=21&title=Hello%20World', $request->getUri()->getQuery());
    }
}
<?php

use Drewlabs\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ResponseTest extends TestCase
{
    public function test_create_psr7_response()
    {
        $response = new Response();
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function test_expect_psr7_response_defaults()
    {
        $response = new Response();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals('', $response->getBody()->__toString());
        $this->assertTrue($response->ok());
    }

    public function test_psr7_response_with_status_code()
    {
        $response = new Response();
        $response2 = $response->withStatus(404);

        // Expect the response object to be immutable
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(404, $response2->getStatusCode());
    }

    public function test_psr7_response_with_headers()
    {
        $response = new Response();
        $response2 = $response->withHeader('Content-Type', 'application/json');

        // Expect the response object to be immutable
        $this->assertTrue([] === $response->getHeader('content-type'));
        $this->assertEquals(['application/json'], $response2->getHeader('content-type'));
    }

    public function test_psr7_response_without_header()
    {
        $response = new Response(200, ['Content-type' => 'application/json']);
        $response2 = $response->withoutHeader('Content-Type');
        $this->assertTrue([] === $response2->getHeader('content-type'));
        $this->assertEquals(['application/json'], $response->getHeader('content-type'));

    }
}
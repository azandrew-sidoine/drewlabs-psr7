<?php

use Drewlabs\Psr7\Exceptions\RequestException;
use Drewlabs\Psr7\Request;
use Drewlabs\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RequestExceptionTest extends TestCase
{

    public function test_request_exception_get_request_returns_request_passed_parameter()
    {
        $request = new Request();
        $exception = new RequestException($request);

        $this->assertInstanceOf(RequestInterface::class, $exception->getRequest());
        $this->assertSame($request, $exception->getRequest());
    }


    public function test_request_exception_has_response_returns_false_if_no_response_provided()
    {
        $request = new Request();
        $exception = new RequestException($request);

        $this->assertFalse($exception->hasResponse());
    }


    public function test_request_exception_get_response_return_response_instance_provided_to_it_constructor()
    {
        $request = new Request();
        $response = new Response();
        $exception = new RequestException($request, 'Not Found', 404, $response);

        $this->assertInstanceOf(ResponseInterface::class, $exception->getResponse());
        $this->assertSame($response, $exception->getResponse());
    }

    public function test_request_exception_passes_error_message_and_code_to_parent_exception_class()
    {
        $exception = new RequestException(new Request(), 'Forbidden Access', null, new Response(423));
        $this->assertEquals(423, $exception->getCode());
        $this->assertEquals('Forbidden Access', $exception->getMessage());
    }
}

<?php

namespace Drewlabs\Psr7\Exceptions;

use Exception;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RequestException extends Exception implements RequestExceptionInterface
{
    /**
     * 
     * @var RequestInterface
     */
    private $request;

    /**
     * 
     * @var ResponseInterface
     */
    private $response;

    /**
     * Creates exception class instance
     * 
     * @param RequestInterface $request 
     * @param string $message 
     * @param mixed $errorcode 
     */
    public function __construct(
        RequestInterface $request,
        string $message = 'Server Error',
        $errorcode = 500,
        ResponseInterface $response = null
    ) {
        parent::__construct($message, $errorcode ?? ($response ? $response->getStatusCode() : null));
        $this->request = $request;
        $this->response = $response;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Check if the request has a response attached
     * 
     * @return bool 
     */
    public function hasResponse(): bool
    {
        return null !== $this->response;
    }

    /**
     * Return the request exception response instance
     * 
     * @return ResponseInterface 
     */
    public function getResponse()
    {
        return $this->response;
    }
}

<?php

namespace Drewlabs\Psr7\Exceptions;

use Exception;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;

class RequestException extends Exception implements RequestExceptionInterface
{
    /**
     * 
     * @var RequestInterface
     */
    private $request;

    /**
     * 
     * @param RequestInterface $request 
     * @param string $message 
     * @param mixed $errorcode 
     */
    public function __construct(RequestInterface $request, string $message, $errorcode)
    {
        parent::__construct($message, $errorcode);
        $this->request = $request;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}

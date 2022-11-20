<?php

namespace Drewlabs\Psr7;

use Drewlabs\Psr7Stream\Stream;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;


class Request implements RequestInterface
{
    use Message, RequestTrait;

    /** @var string */
    private $method;

    /** @var string|null */
    private $requestTarget;

    /** @var UriInterface */
    protected $uri;

    /**
     * Creates a request instance
     * 
     * @param string $method                        HTTP request method
     * @param UriInterface|string|null $uri              Request URI
     * @param array $headers                        Request headers
     * @param string|StreamInterface|null $body     Request body
     * @param string $version                       HTTP protocol version
     * @return void 
     * @throws InvalidArgumentException 
     */
    public function __construct(
        string $method = 'GET',
        $uri = null,
        array $headers = [],
        $body = null,
        string $version = '1.1'
    ) {
        $this->assertMethod($method);
        $this->method = strtoupper($method);
        $this->uri = Uri::new($uri);
        $this->headers = HeadersBag::new($headers);
        $this->protocol = $version;

        if (!$this->headers->has('host')) {
            $this->updateHostFromUri();
        }
        if ($body !== '' && $body !== null) {
            $this->stream = Stream::new($body);
        }
    }
}
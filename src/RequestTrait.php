<?php

namespace Drewlabs\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

trait RequestTrait
{
    /** @var string */
    private $method;

    /** @var string|null */
    private $requestTarget;

    /** @var UriInterface */
    protected $uri;


    private function initDefaultAttributes(
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
            $this->stream = Streams::lazy($body);
        }
    }

    #[\ReturnTypeWillChange]
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }
        $uri = $this->getUri();
        $target = $uri->getPath();
        if ($target === '') {
            $target = '/';
        }
        if ($this->uri->getQuery() != '') {
            $target .= '?' . $uri->getQuery();
        }
        return $target;
    }

    #[\ReturnTypeWillChange]
    public function withRequestTarget($requestTarget): RequestInterface
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace'
            );
        }

        $object = clone $this;
        $object->requestTarget = $requestTarget;
        return $object;
    }

    #[\ReturnTypeWillChange]
    public function getMethod(): string
    {
        return $this->method;
    }

    #[\ReturnTypeWillChange]
    public function withMethod($method): RequestInterface
    {
        $this->assertMethod($method);
        $object = clone $this;
        $object->method = strtoupper($method);
        return $object;
    }


    #[\ReturnTypeWillChange]
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    #[\ReturnTypeWillChange]
    public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
    {
        if ($uri === $this->getUri()) {
            return $this;
        }
        $object = clone $this;
        $object->uri = $uri;
        if (!$preserveHost || !$this->headers->has('Host')) {
            $object->updateHostFromUri();
        }
        return $object;
    }

    private function updateHostFromUri()
    {
        $uri = $this->getUri();
        $host = $uri->getHost();
        if ($host == '') {
            return;
        }
        if (($port = $uri->getPort()) !== null) {
            $host .= ':' . $port;
        }
        // Ensure Host is the first header.
        // See: http://tools.ietf.org/html/rfc7230#section-5.4
        $this->headers = HeadersBag::new(['Host' => [$host]] + $this->headers->toArray());
    }

    /**
     * @param mixed $method
     */
    private function assertMethod($method)
    {
        if (!is_string($method) || $method === '') {
            throw new InvalidArgumentException('Method must be a non-empty string.');
        }
    }
}

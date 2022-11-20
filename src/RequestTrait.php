<?php

namespace Drewlabs\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

trait RequestTrait
{
    #[\ReturnTypeWillChange]
    public function getRequestTarget()
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
    public function withRequestTarget($requestTarget)
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
    public function getMethod()
    {
        return $this->method;
    }

    #[\ReturnTypeWillChange]
    public function withMethod($method)
    {
        $this->assertMethod($method);
        $object = clone $this;
        $object->method = strtoupper($method);
        return $object;
    }


    #[\ReturnTypeWillChange]
    public function getUri()
    {
        return $this->uri;
    }

    #[\ReturnTypeWillChange]
    public function withUri(UriInterface $uri, $preserveHost = false)
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

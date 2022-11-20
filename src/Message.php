<?php

namespace Drewlabs\Psr7;

use Drewlabs\Psr7Stream\Stream;
use Psr\Http\Message\StreamInterface;

/**
 * Trait implementing functionality common to requests and responses.
 */
trait Message
{
    /**
     * Dictionary of registered headers
     * 
     * @var HeadersBag
     */
    private $headers;

    /**
     * @var string
     */
    private $protocol = '1.1';

    /**
     * 
     * @var StreamInterface|string
     */
    private $body;

    #[\ReturnTypeWillChange]
    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    #[\ReturnTypeWillChange]
    public function withProtocolVersion($version)
    {
        if ($this->protocol === $version) {
            return $this;
        }
        $object = clone $this;
        $object->protocol = $version;
        return $object;
    }

    #[\ReturnTypeWillChange]
    public function getHeaders()
    {
        return $this->headers->toArray();
    }

    #[\ReturnTypeWillChange]
    public function hasHeader($header)
    {
        return $this->headers->offsetExists($header);
    }

    #[\ReturnTypeWillChange]
    public function getHeader($header)
    {
        return $this->headers->get($header);
    }


    #[\ReturnTypeWillChange]
    public function getHeaderLine($header)
    {
        return implode(', ', $this->getHeader($header));
    }


    #[\ReturnTypeWillChange]
    public function withHeader($header, $value)
    {
        /**
         * @var self
         */
        $object = (clone $this);
        $object->headers->offsetUnset($header);
        $object->headers->set($header, $value);
        return $object;
    }

    #[\ReturnTypeWillChange]
    public function withAddedHeader($header, $value)
    {
        /**
         * @var object
         */
        $object = (clone $this);
        $object->headers->set($header, $value);
        return $object;
    }

    #[\ReturnTypeWillChange]
    public function withoutHeader($header)
    {
        /**
         * @var object
         */
        $object = (clone $this);
        $object->headers->remove($header);
        return $object;
    }

    public function getBody()
    {
        return $this->body instanceof StreamInterface ?
            $this->body :
            Stream::new($this->body);
    }

    public function withBody($body)
    {
        if (is_string($body)) {
            $body = Stream::new($body);
        }
        if ($body === $this->body) {
            return $this;
        }
        $object = clone $this;
        $object->body = $body;
        return $object;
    }


    public function __clone()
    {
        // We clone the http headers when we clone the curren object
        $this->headers = clone $this->headers;
    }
}

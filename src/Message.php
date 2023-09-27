<?php

namespace Drewlabs\Psr7;

use Drewlabs\Psr7Stream\Stream;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @extends MessageInterface
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
    private $stream;

    #[\ReturnTypeWillChange]
    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    #[\ReturnTypeWillChange]
    public function withProtocolVersion($version): MessageInterface
    {
        if ($this->protocol === $version) {
            return $this;
        }
        $object = clone $this;
        $object->protocol = $version;
        return $object;
    }

    #[\ReturnTypeWillChange]
    public function getHeaders(): array
    {
        return $this->headers->toArray();
    }

    #[\ReturnTypeWillChange]
    public function hasHeader($header): bool
    {
        return $this->headers->offsetExists($header);
    }

    #[\ReturnTypeWillChange]
    public function getHeader($header): array
    {
        return $this->headers->get($header);
    }


    #[\ReturnTypeWillChange]
    public function getHeaderLine($header): string
    {
        return implode(', ', $this->getHeader($header));
    }


    #[\ReturnTypeWillChange]
    public function withHeader($header, $value): MessageInterface
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
    public function withAddedHeader($header, $value): MessageInterface
    {
        /**
         * @var object
         */
        $object = (clone $this);
        $object->headers->set($header, $value);
        return $object;
    }

    #[\ReturnTypeWillChange]
    public function withoutHeader($header): MessageInterface
    {
        /**
         * @var object
         */
        $object = (clone $this);
        $object->headers->remove($header);
        return $object;
    }

    public function getBody(): StreamInterface
    {
        return $this->stream instanceof StreamInterface ?
            $this->stream :
            Stream::new($this->stream);
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        if (is_string($body)) {
            $body = Stream::new($body);
        }
        if ($body === $this->stream) {
            return $this;
        }
        $object = clone $this;
        $object->stream = $body;

        return $object;
    }


    public function __clone()
    {
        // We clone the http headers when we clone the curren object
        $this->headers = clone $this->headers;
    }
}

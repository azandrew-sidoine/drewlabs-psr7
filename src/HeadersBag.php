<?php

namespace Drewlabs\Psr7;

use ArrayAccess;
use InvalidArgumentException;

class HeadersBag implements ArrayAccess
{
    /**
     * 
     * @var array<string,string[]>
     */
    private $headers = [];

    /**
     * 
     * @param array $headers 
     * @return void 
     */
    public function __construct(array $headers)
    {
        foreach ($headers as $key => $value) {
            $this->set($key, $value);
        }
    }
    /**
     * 
     * @param string|array|self $headers 
     * @return static 
     */
    public static function new($headers = [])
    {
        $headers = $headers ?? [];
        if ($headers instanceof HeadersBag) {
            $headers = $headers->toArray();
        }
        if (!is_string($headers) && !is_array($headers)) {
            throw new InvalidArgumentException(__METHOD__ . ' expect parameter to be a php string or array');
        }
        if (is_array($headers)) {
            $object = new static([]);
            foreach ($headers as $name => $value) {
                $object->set($name, $value);
            }
            return $object;
        }
        return static::parseHeaders($headers);
    }

    /**
     * Add a header entry.
     * 
     * @param string $key 
     * @param mixed $value 
     * @return static 
     */
    public function set(string $header, $value)
    {
        $normalized = $this->normalizeHeader($header);
        $value = $this->normalizeHeaderValue($value);
        if (isset($this->headers[$normalized])) {
            $this->headers[$normalized] = array_merge($this->headers[$normalized], $value);
        } else {
            $this->headers[$normalized] = $value;
        }
        return $this;
    }

    /**
     * Query for a header value
     * 
     * @param string $header 
     * @return string[] 
     */
    public function get(string $header)
    {
        return $this->headers[strtolower($header)] ?? [];
    }

    /**
     * Checks if a given header exists
     * 
     * @param string $name 
     * @return bool 
     */
    public function has(string $header)
    {
        return array_key_exists(strtolower($header), $this->headers);
    }

    /**
     * Remove the matching header value
     * 
     * @param string $header 
     */
    public function remove(string $header)
    {
        unset($this->headers[strtolower($header)]);
    }

    /**
     * Returns the array/dictionary representation of the header bag
     * 
     * @return array<string,string[]> 
     */
    public function toArray()
    {
        return $this->headers;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return null !== $this->get($offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    private static function parseHeaders($list)
    {
        $list = preg_split('/\r\n/', (string) $list, -1, PREG_SPLIT_NO_EMPTY);
        $httpHeaders = new HeadersBag([]);
        $httpHeaders->set('Request-Line', reset($list) ?? '');
        for ($i = 1; $i < count($list); $i++) {
            if (strpos($list[$i], ':') !== false) {
                [$key, $value] = array_map(function ($item) {
                    return $item ? trim($item) : null;
                }, explode(':', $list[$i], 2));
                $httpHeaders->set($key, $value);
            }
        }
        return $httpHeaders;
    }

    /**
     * 
     * @param mixed $header 
     * @return string 
     * @throws InvalidArgumentException 
     */
    private function normalizeHeader($header)
    {
        $this->assertHeader($header);
        $normalized = strtolower($header);
        return $normalized;
    }

    /**
     * @param mixed $value
     *
     * @return string[]
     */
    private function normalizeHeaderValue($value)
    {
        if (!is_array($value)) {
            return $this->trimAndValidateHeaderValues([$value]);
        }
        if (count($value) === 0) {
            throw new \InvalidArgumentException('Header value can not be an empty array.');
        }
        return $this->trimAndValidateHeaderValues($value);
    }

    /**
     * Trims whitespace from the header values.
     *
     * Spaces and tabs ought to be excluded by parsers when extracting the field value from a header field.
     *
     * header-field = field-name ":" OWS field-value OWS
     * OWS          = *( SP / HTAB )
     *
     * @param mixed[] $values Header values
     *
     * @return string[] Trimmed header values
     *
     * @see https://tools.ietf.org/html/rfc7230#section-3.2.4
     */
    private function trimAndValidateHeaderValues(array $values)
    {
        return array_map(function ($value) {
            if (!is_scalar($value) && null !== $value) {
                throw new \InvalidArgumentException(sprintf(
                    'Header value must be scalar or null but %s provided.',
                    is_object($value) ? get_class($value) : gettype($value)
                ));
            }
            $trimmed = trim((string) $value, " \t");
            $this->assertValue($trimmed);
            return $trimmed;
        }, array_values($values));
    }

    /**
     * @see https://tools.ietf.org/html/rfc7230#section-3.2
     *
     * @param mixed $header
     */
    private function assertHeader($header)
    {
        if (!is_string($header)) {
            throw new \InvalidArgumentException(sprintf(
                'Header name must be a string but %s provided.',
                is_object($header) ? get_class($header) : gettype($header)
            ));
        }

        if (!preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $header)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '"%s" is not valid header name',
                    $header
                )
            );
        }
    }

    /**
     * @see https://tools.ietf.org/html/rfc7230#section-3.2
     *
     * field-value    = *( field-content / obs-fold )
     * field-content  = field-vchar [ 1*( SP / HTAB ) field-vchar ]
     * field-vchar    = VCHAR / obs-text
     * VCHAR          = %x21-7E
     * obs-text       = %x80-FF
     * obs-fold       = CRLF 1*( SP / HTAB )
     */
    private function assertValue(string $value)
    {
        // The regular expression intentionally does not support the obs-fold production, because as
        // per RFC 7230#3.2.4:
        //
        // A sender MUST NOT generate a message that includes
        // line folding (i.e., that has any field-value that contains a match to
        // the obs-fold rule) unless the message is intended for packaging
        // within the message/http media type.
        //
        // Clients must not send a request with line folding and a server sending folded headers is
        // likely very rare. Line folding is a fairly obscure feature of HTTP/1.1 and thus not accepting
        // folding is not likely to break any legitimate use case.
        if (!preg_match('/^[\x20\x09\x21-\x7E\x80-\xFF]*$/', $value)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not valid header value', $value));
        }
    }
}

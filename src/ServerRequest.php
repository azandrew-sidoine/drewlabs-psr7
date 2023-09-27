<?php

namespace Drewlabs\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class ServerRequest implements ServerRequestInterface
{
    use Message, RequestTrait;

    /**
     * 
     * @var array<string,mixed>
     */
    private $attributes = [];

    /**
     * 
     * @var array<string,mixed>
     */
    private $serverParams = [];

    /**
     * 
     * @var array|object|null
     */
    private $parsedBody;

    /**
     * 
     * @var UploadedFileInterface[]
     */
    private $files = [];

    /**
     * 
     * @var array<string,mixed>
     */
    private $queryParams = [];

    /**
     * 
     * @var array<string,mixed>
     */
    private $cookieParams = [];

    /**
     * Creates a ServerRequest instance
     * 
     * @param string $method 
     * @param mixed $uri 
     * @param array $headers 
     * @param mixed $body 
     * @param string $version 
     * @param array $serverParams 
     * @return void 
     * @throws InvalidArgumentException 
     */
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        array $serverParams = []
    ) {
        $this->initDefaultAttributes($method, $uri, $headers ?? [], $body, $version);
        $this->serverParams = $serverParams;
    }

    public function getServerParams(): array
    {
        return $this->serverParams ?? [];
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams ?? [];
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $object = clone $this;
        $object->cookieParams = $cookies;
        return $object;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams ?? [];
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        $object = clone $this;
        $object->queryParams = $query;
        return $object;
    }

    public function getUploadedFiles(): array 
    {
        return $this->files ?? [];
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $object = clone $this;
        $object->files = $uploadedFiles;
        return $object;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        if (!\is_array($data) && !\is_object($data) && null !== $data) {
            throw new \InvalidArgumentException('withParsedBody expects array, object or null as parameter, got ' . (gettype($data)));
        }
        $object = clone $this;
        $object->parsedBody = $data;
        return $object;
    }

    public function getAttributes(): array
    {
        return $this->attributes ?? [];
    }

    public function getAttribute($name, $default = null)
    {
        if (!array_key_exists($name, $this->attributes ?? [])) {
            return $default;
        }
        return $this->attributes[$name];
    }

    public function withAttribute($name, $value): ServerRequestInterface
    {
        $object = clone $this;
        $object->attributes[$name] = $value;
        return $object;
    }

    public function withoutAttribute($name): ServerRequestInterface
    {
        if (!array_key_exists($name, $this->attributes ?? [])) {
            return $this;
        }
        $object = clone $this;
        unset($object->attributes[$name]);
        return $object;
    }
}

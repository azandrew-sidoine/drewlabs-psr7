<?php

declare(strict_types=1);

namespace Drewlabs\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * PSR-7 URI implementation.
 *
 * @author Michael Dowling
 * @author Tobias Schultze
 * @author Matthew Weier O'Phinney
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Martijn van der Ven <martijn@vanderven.se>
 *
 */
final class Uri implements UriInterface
{
    /**
     * HTTP schemes
     */
    private const SCHEMES = ['http' => 80, 'https' => 443];

    /**
     * Not reserved characters
     * 
     * @var string
     */
    private const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    /**
     * @var string
     */
    private const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    /** @var string Uri scheme. */
    private $scheme = '';

    /** @var string Uri user info. */
    private $userInfo = '';

    /** @var string Uri host. */
    private $host = '';

    /** @var int|null Uri port. */
    private $port;

    /** @var string Uri path. */
    private $path = '';

    /** @var string Uri query string. */
    private $query = '';

    /** @var string Uri fragment. */
    private $fragment = '';

    /**
     * Creates a URI instance
     * 
     * @param string $uri 
     * @return void 
     * @throws InvalidArgumentException 
     */
    public function __construct(string $uri = '')
    {
        if ('' !== $uri) {
            if (false === $parts = \parse_url($uri)) {
                throw new \InvalidArgumentException(\sprintf('Unable to parse URI: "%s"', $uri));
            }
            $this->scheme = isset($parts['scheme']) ? $this->translate($parts['scheme']) : '';
            $this->host = isset($parts['host']) ? $this->translate($parts['host']) : '';
            $this->port = isset($parts['port']) ? $this->filterPort($parts['port']) : null;
            $this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
            $this->query = isset($parts['query']) ? $this->filterQueryAndFragment($parts['query']) : '';
            $this->fragment = isset($parts['fragment']) ? $this->filterQueryAndFragment($parts['fragment']) : '';
            $this->userInfo = $parts['user'] ?? '';
            if (isset($parts['pass'])) {
                $this->userInfo .= ':' . $parts['pass'];
            }
        }
    }

    /**
     * Static method for creating Uri instance
     * 
     * @param UriInterface|string|null $uri 
     * @return static 
     * @throws InvalidArgumentException 
     */
    public static function new($uri = null)
    {
        if (null === $uri) {
            return new static;
        }
        if ($uri instanceof UriInterface) {
            return (new static)->withScheme($uri->getScheme())
                ->withHost($uri->getHost())
                ->withPort($uri->getPort())
                ->withPath($uri->getPath())
                ->withQuery($uri->getQuery())
                ->withFragment($uri->getFragment())
                ->withUserInfo($uri->getUserInfo());
        }
        return new static((string)$uri);
    }

    #[\ReturnTypeWillChange]
    public function __toString()
    {
        return self::createUriString($this->scheme, $this->getAuthority(), $this->path, $this->query, $this->fragment);
    }

    #[\ReturnTypeWillChange]
    public function getScheme()
    {
        return $this->scheme;
    }

    #[\ReturnTypeWillChange]
    public function getAuthority()
    {
        if ('' === $this->host) {
            return '';
        }

        $authority = $this->host;
        if ('' !== $this->userInfo) {
            $authority = $this->userInfo . '@' . $authority;
        }

        if (null !== $this->port) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    #[\ReturnTypeWillChange]
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    #[\ReturnTypeWillChange]
    public function getHost()
    {
        return $this->host;
    }

    #[\ReturnTypeWillChange]
    public function getPort()
    {
        return $this->port;
    }

    #[\ReturnTypeWillChange]
    public function getPath()
    {
        return $this->path;
    }

    #[\ReturnTypeWillChange]
    public function getQuery()
    {
        return $this->query;
    }

    #[\ReturnTypeWillChange]
    public function getFragment()
    {
        return $this->fragment;
    }

    #[\ReturnTypeWillChange]
    public function withScheme($scheme)
    {
        if (!\is_string($scheme)) {
            throw new \InvalidArgumentException('Scheme must be a string');
        }

        if ($this->scheme === $scheme = $this->translate($scheme)) {
            return $this;
        }

        $object = clone $this;
        $object->scheme = $scheme;
        $object->port = $object->filterPort($object->port);
        return $object;
    }

    #[\ReturnTypeWillChange]
    public function withUserInfo($user, $password = null)
    {
        $info = $user;
        if (null !== $password && '' !== $password) {
            $info .= ':' . $password;
        }

        if ($this->userInfo === $info) {
            return $this;
        }

        $object = clone $this;
        $object->userInfo = $info;
        return $object;
    }

    #[\ReturnTypeWillChange]
    public function withHost($host)
    {
        if (!\is_string($host)) {
            throw new \InvalidArgumentException('Host must be a string');
        }

        if ($this->host === ($host = $this->translate($host))) {
            return $this;
        }

        $object = clone $this;
        $object->host = $host;
        return $object;
    }

    #[\ReturnTypeWillChange]
    public function withPort($port)
    {
        if ($this->port === ($port = $this->filterPort($port))) {
            return $this;
        }

        $object = clone $this;
        $object->port = $port;
        return $object;
    }

    #[\ReturnTypeWillChange]
    public function withPath($path)
    {
        if ($this->path === ($path = $this->filterPath($path))) {
            return $this;
        }

        $object = clone $this;
        $object->path = $path;
        return $object;
    }

    #[\ReturnTypeWillChange]
    public function withQuery($query)
    {
        if ($this->query === ($query = $this->filterQueryAndFragment($query))) {
            return $this;
        }

        $object = clone $this;
        $object->query = $query;
        return $object;
    }

    #[\ReturnTypeWillChange]
    public function withFragment($fragment)
    {
        if ($this->fragment === ($fragment = $this->filterQueryAndFragment($fragment))) {
            return $this;
        }

        $object = clone $this;
        $object->fragment = $fragment;
        return $object;
    }

    /**
     * Create a URI string from its various parts.
     */
    #[\ReturnTypeWillChange]
    private static function createUriString(string $scheme, string $authority, string $path, string $query, string $fragment)
    {
        $uri = '';
        if ('' !== $scheme) {
            $uri .= $scheme . ':';
        }

        if ('' !== $authority) {
            $uri .= '//' . $authority;
        }

        if ('' !== $path) {
            if ('/' !== $path[0]) {
                if ('' !== $authority) {
                    // If the path is rootless and an authority is present, the path MUST be prefixed by "/"
                    $path = '/' . $path;
                }
            } elseif (isset($path[1]) && '/' === $path[1]) {
                if ('' === $authority) {
                    // If the path is starting with more than one "/" and no authority is present, the
                    // starting slashes MUST be reduced to one.
                    $path = '/' . \ltrim($path, '/');
                }
            }

            $uri .= $path;
        }

        if ('' !== $query) {
            $uri .= '?' . $query;
        }

        if ('' !== $fragment) {
            $uri .= '#' . $fragment;
        }

        return $uri;
    }

    /**
     * Is a given port non-standard for the current scheme?
     */
    private static function isNonStandardPort(string $scheme, int $port)
    {
        return !isset(self::SCHEMES[$scheme]) || $port !== self::SCHEMES[$scheme];
    }

    private function filterPort($port)
    {
        if (null === $port) {
            return null;
        }

        $port = (int) $port;
        if (0 > $port || 0xffff < $port) {
            throw new \InvalidArgumentException(\sprintf('Invalid port: %d. Must be between 0 and 65535', $port));
        }
        return self::isNonStandardPort($this->scheme, $port) ? $port : null;
    }

    private function filterPath($path)
    {
        if (!\is_string($path)) {
            throw new \InvalidArgumentException('Path must be a string');
        }
        return \preg_replace_callback('/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/]++|%(?![A-Fa-f0-9]{2}))/', [__CLASS__, 'rawurlencodeMatchZero'], $path);
    }

    private function filterQueryAndFragment($str)
    {
        if (!\is_string($str)) {
            throw new \InvalidArgumentException('Query and fragment must be a string');
        }
        return \preg_replace_callback('/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/', [__CLASS__, 'rawurlencodeMatchZero'], $str);
    }

    /**
     * Translate string replacing uppercase occurences of the alphabet with lowercase
     * 
     * @param string $host 
     * @return string 
     */
    private function translate(string $host)
    {
        return \strtr($host, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
    }

    private static function rawurlencodeMatchZero(array $match)
    {
        return \rawurlencode($match[0]);
    }
}

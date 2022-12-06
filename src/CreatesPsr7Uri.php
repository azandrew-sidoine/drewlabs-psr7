<?php



namespace Drewlabs\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

trait CreatesPsr7Uri
{

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

    /**
     * Creates uri interface from PHP $_SERVER array
     * 
     * @param array $server 
     * @return UriInterface 
     * @throws InvalidArgumentException 
     */
    public static function createFromServerGlobal(array $server): UriInterface
    {
        $self = new self();

        if (isset($server['HTTP_X_FORWARDED_PROTO'])) {
            $self = $self->withScheme($server['HTTP_X_FORWARDED_PROTO']);
        } else {
            if (isset($server['REQUEST_SCHEME'])) {
                $self = $self->withScheme($server['REQUEST_SCHEME']);
            } elseif (isset($server['HTTPS'])) {
                $self = $self->withScheme('on' === $server['HTTPS'] ? 'https' : 'http');
            }

            if (isset($server['SERVER_PORT'])) {
                $self = $self->withPort($server['SERVER_PORT']);
            }
        }

        if (isset($server['HTTP_HOST'])) {
            if (1 === \preg_match('/^(.+)\:(\d+)$/', $server['HTTP_HOST'], $matches)) {
                $self = $self->withHost($matches[1])->withPort($matches[2]);
            } else {
                $self = $self->withHost($server['HTTP_HOST']);
            }
        } elseif (isset($server['SERVER_NAME'])) {
            $self = $self->withHost($server['SERVER_NAME']);
        }

        if (isset($server['REQUEST_URI'])) {
            $self = $self->withPath(\current(\explode('?', $server['REQUEST_URI'])));
        }

        if (isset($server['QUERY_STRING'])) {
            $self = $self->withQuery($server['QUERY_STRING']);
        }

        return $self;
    }

    /**
     * Creates uri instance from PHP environments
     * 
     * @param array $environment 
     * @return UriInterface 
     * @throws InvalidArgumentException 
     */
    public static function createFromEnvWithHTTP(array $environment): UriInterface
    {
        $uri = self::createFromServerGlobal($environment);
        if (empty($uri->getScheme())) {
            $uri = $uri->withScheme('http');
        }
        return $uri;
    }
}

<?php

namespace Drewlabs\Psr7;

use Drewlabs\Psr7Stream\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Stringable;

class Response implements ResponseInterface
{

    use Message, ResponseTrait;

    /**
     * 
     * @param int $status 
     * @param array|string|HeadersBag $headers 
     * @param Stringable|mixed $body 
     * @param string $version 
     * @param string|null $reason 
     */
    public function __construct(
        int $status = 200,
        $headers = [],
        $body = null,
        string $version = '1.1',
        string $reason = null
    ) {
        $this->assertStatusCodeRange($status);
        $this->statusCode = $status;
        $this->body = $body instanceof StreamInterface ? $body : Stream::new((string)($body ?? ''));
        $this->headers = HeadersBag::new($headers);
        $this->reasonPhrase = $reason == '' && ('' != ($reasonPhrase = ResponseReasonPhrase::getPrase($this->statusCode))) ? $reasonPhrase : (string) $reason;
        $this->protocol = $version;
    }

    /**
     * Returns true if the response completed successfully
     * 
     * @return bool 
     */
    public function ok()
    {
        return !in_array((int) floor($this->statusCode / 100), [4, 5], true);
    }
}

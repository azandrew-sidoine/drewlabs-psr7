<?php

namespace Drewlabs\Psr7;

use Psr\Http\Message\ResponseInterface;

trait ResponseTrait
{
    /**
     * 
     * @var int
     */
    private $statusCode;

    #[\ReturnTypeWillChange]
    public function getStatusCode(): int
    {
        return intval($this->statusCode);
    }

    #[\ReturnTypeWillChange]
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase ?? '';
    }

    #[\ReturnTypeWillChange]
    public function withStatus($code, $reason = ''): ResponseInterface
    {
        $this->assertStatusCodeIsInteger($code);
        $code = (int) $code;
        $this->assertStatusCodeRange($code);
        $object = clone $this;
        $object->statusCode = $code;
        $object->reasonPhrase = $reason == '' && ('' != ($reasonPhrase = ResponseReasonPhrase::getPrase($this->statusCode))) ? $reasonPhrase : (string) $reason;
        return $object;
    }

    /**
     * @param mixed $statusCode
     */
    private function assertStatusCodeIsInteger($statusCode)
    {
        if (filter_var($statusCode, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException('Status code must be an integer value.');
        }
    }

    private function assertStatusCodeRange(int $statusCode)
    {
        if ($statusCode < 100 || $statusCode >= 600) {
            throw new \InvalidArgumentException('Status code must be an integer value between 1xx and 5xx.');
        }
    }
}

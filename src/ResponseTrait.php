<?php

namespace Drewlabs\Psr7;

trait ResponseTrait
{
    /**
     * 
     * @var int
     */
    private $statusCode;

    #[\ReturnTypeWillChange]
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    #[\ReturnTypeWillChange]
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    #[\ReturnTypeWillChange]
    public function withStatus($code, $reason = '')
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

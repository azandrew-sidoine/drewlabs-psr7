<?php

namespace Drewlabs\Psr7;

trait FilesAttributesAware
{
    /**
     * 
     * @var string
     */
    private $name;

    /**
     * 
     * @var int
     */
    private $size;

    /**
     * 
     * @var null|string
     */
    private $mediaType;

    /**
     * 
     * @var int
     */
    private $error;

    public function getSize(): int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): string
    {
        return $this->name;
    }

    public function getClientMediaType(): string
    {
        return $this->mediaType;
    }
}

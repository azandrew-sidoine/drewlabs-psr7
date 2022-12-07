<?php

namespace Drewlabs\Psr7\Factories;

use Drewlabs\Psr7\UploadedFile;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFileFactory implements UploadedFileFactoryInterface
{
    public function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = \UPLOAD_ERR_OK,
        string $name = null,
        string $mediaType = null
    ): UploadedFileInterface {
        return new UploadedFile($stream, $size, $error, $name, $mediaType);
    }
}

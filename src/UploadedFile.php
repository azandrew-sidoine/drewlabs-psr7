<?php

namespace Drewlabs\Psr7;

use Drewlabs\Psr7Stream\Exceptions\StreamException;
use Drewlabs\Psr7Stream\Stream;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;


final class UploadedFile implements UploadedFileInterface
{

    use FilesAttributesAware;

    /** @var array */
    const UPLOAD_ERRORS = [
        \UPLOAD_ERR_OK => 1,
        \UPLOAD_ERR_INI_SIZE => 1,
        \UPLOAD_ERR_FORM_SIZE => 1,
        \UPLOAD_ERR_PARTIAL => 1,
        \UPLOAD_ERR_NO_FILE => 1,
        \UPLOAD_ERR_NO_TMP_DIR => 1,
        \UPLOAD_ERR_CANT_WRITE => 1,
        \UPLOAD_ERR_EXTENSION => 1,
    ];
    /**
     * 
     * @var string
     */
    private $path;

    /**
     * 
     * @var StreamInterface
     */
    private $stream;

    /**
     * Indicates whether the uploaded files was moved or not
     * 
     * @var bool
     */
    private $moved = false;

    /**
     * Creates an uploaded file instance
     * 
     * @param StreamInterface|string|resource $resource 
     * @param int $size 
     * @param mixed $error 
     * @param string|null $name 
     * @param string|null $mediaType 
     * @return void 
     * @throws InvalidArgumentException
     */
    public function __construct($resource, int $size, int $error = \UPLOAD_ERR_OK, string $name = null, string $mediaType = null)
    {
        if (false === \is_int($error) || !isset(self::UPLOAD_ERRORS[$error])) {
            throw new InvalidArgumentException('file error status must be an integer and one of PHP "UPLOAD_ERR_*" constants.');
        }

        if (false === \is_int($size)) {
            throw new InvalidArgumentException('file size must be an integer');
        }

        if (null !== $name && !\is_string($name)) {
            throw new InvalidArgumentException('file client filename must be a string or null');
        }

        if (null !== $mediaType && !\is_string($mediaType)) {
            throw new InvalidArgumentException('file client media type must be a string or null');
        }

        $this->error = $error;
        $this->size = $size;
        $this->name = $name;
        $this->mediaType = $mediaType;

        if (\UPLOAD_ERR_OK === $this->error) {
            $this->stream = $this->createStream($resource);
        }
    }

    public function moveTo($targetPath)
    {
        $this->assertMoved();
        if (null !== $this->path) {
            return $this->rename($targetPath);
        }
        $this->moveStream($targetPath);
    }

    private function openLazyStream(string $path, $mode = 'r')
    {
        return Streams::lazy(function () use ($path, $mode) {
            if (false === $resource = @\fopen($path, $mode)) {
                throw new RuntimeException(\sprintf('destination file "%s" cannot be opened: %s', $path, \error_get_last()['message'] ?? ''));
            }
            return Streams::create($resource);
        });
    }

    /**
     * 
     * @param mixed $path 
     * @return void 
     * @throws RuntimeException 
     */
    private function rename($path)
    {
        $this->moved = 'cli' === \PHP_SAPI ? @\rename($this->path, $path) : @\move_uploaded_file($this->path, $path);
        if (false === $this->moved) {
            throw new RuntimeException(\sprintf('file could not be moved to "%s": %s', $path, \error_get_last()['message'] ?? ''));
        }
    }

    /**
     * Move stream content to the destination file
     * 
     * @param mixed $path 
     * @return void 
     * @throws RuntimeException 
     * @throws InvalidArgumentException 
     * @throws StreamException 
     */
    private function moveStream($path)
    {
        $destination = $this->openLazyStream($path, 'w');
        if (($stream = $this->getStream())->isSeekable()) {
            $stream->rewind();
        }
        while (!$stream->eof()) {
            if (!$destination->write($stream->read(1048576))) {
                break;
            }
        }
        // Release any stream resource
        $stream->close();
        // If the uploaded file was created from a path, we unlink the file
        if (null !== $this->path) {
            @\unlink($this->path);
        }
        $this->moved = true;
    }

    /**
     * Creates a Psr7 stream from the resource provided by the user
     * 
     * @param StreamInterface|string|resource $resource 
     * @return StreamInterface 
     * @throws InvalidArgumentException 
     */
    private function createStream($resource)
    {
        if (\is_string($resource) && '' !== $resource && is_file($resource)) {
            $this->path = $resource;
            return $this->openLazyStream($this->path);
        }
        if (\is_resource($resource)) {
            return Stream::new($resource);
        }

        if ($resource instanceof StreamInterface) {
            return $resource;
        }
        throw new \InvalidArgumentException('Invalid resource provided for UploadedFile');
    }

    /**
     * Returns the internal stream instance
     * 
     * @return StreamInterface 
     * @throws RuntimeException 
     */
    public function getStream(): StreamInterface
    {
        $this->assertMoved();
        if (null !== $this->stream) {
            throw new \RuntimeException('Cannot retrieve stream, due to upload error');
        }
        return $this->stream;
    }

    /**
     * Throws an exception if the target path is not a valid string nor system path
     * 
     * @param mixed $path 
     * @return void 
     * @throws InvalidArgumentException 
     */
    private function assertTargetPath($path)
    {
        if (!\is_string($path) || '' === $path) {
            throw new \InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
        }
    }

    /**
     * Throws an exception if the file has already being moved
     * 
     * @return void 
     * @throws RuntimeException 
     */
    private function assertMoved()
    {
        if ($this->moved) {
            throw new \RuntimeException('Cannot operates on an already moved file');
        }
    }
}

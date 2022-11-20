<?php

namespace Drewlabs\Psr7;

use Drewlabs\Psr7Stream\LazyStream;
use Drewlabs\Psr7Stream\StackedStream;
use Drewlabs\Psr7Stream\Stream;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

class Streams
{

    /**
     * @param string|resource|StreamInterface $resource
     * @return Stream 
     * @throws InvalidArgumentException 
     */
    public static function create($resource)
    {
        return Stream::new();
    }

    /**
     * Creates a Psr7 stream instance that lazily creates the internal stream
     * at runtime when requested by user
     * 
     * @param callable|CreatesStream|null|string $createStream
     * 
     * @return  LazyStream&StreamInterface
     */
    public static function lazy($createStream)
    {
        return new LazyStream($createStream);

    }

    /**
     * Creates a stream list using a stack data structure abstraction
     * 
     * @param (StreamInterface|string)[] $streams 
     * @return StackedStream 
     */
    public static function stacked(...$streams)
    {
        return new StackedStream(...$streams);
    }
}
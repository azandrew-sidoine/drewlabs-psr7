<?php

namespace Drewlabs\Psr7;

use Closure;
use Drewlabs\Psr7Stream\CreatesStream;
use Drewlabs\Psr7Stream\StackedStream;
use Drewlabs\Psr7Stream\Stream;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use UnexpectedValueException;

class CreatesMultipartStream implements CreatesStream
{
    /**
     * 
     * @var array<array<string,mixed>>
     */
    private $attributes;

    /**
     * 
     * @var string
     */
    private $boundary;

    public function __construct(array $attributes, string $boundary = null)
    {
        $this->boundary = $boundary ?? bin2hex(random_bytes(20));
        $this->attributes = $attributes ?? [];
    }

    /**
     * Returns the boundary of the multipart stream
     * 
     * @return string 
     */
    public function getBoundary()
    {
        return $this->boundary;
    }

    /**
     * Get the headers needed before transferring the content of a POST file
     * 
     * @param array<string,string|string[]> $headers 
     * @return string 
     */
    private function getHeaders(array $headers): string
    {
        $str = '';
        foreach ($headers as $key => $value) {
            $value = is_array($value) ? implode(',', $value) : $value;
            $str .= "{$key}: {$value}\r\n";
        }

        return "--{$this->boundary}\r\n" . trim($str) . "\r\n\r\n";
    }

    public function createStream()
    {
        $stack = new StackedStream();

        if (null === $this->attributes || !is_array($this->attributes)) {
            throw new \UnexpectedValueException('multipart attribute must be a valid php array');
        }

        foreach ($this->attributes as $attribute) {
            $this->assertDictionary($attribute);
            $this->addDictPart(
                $stack,
                $attribute['name'],
                $attribute['contents'],
                $attribute['filename'] ?? null,
                $attribute['headers'] ?? []
            );
        }
        // Add the trailing boundary with CRLF
        $stack->push(Stream::new("--{$this->boundary}--\r\n"));

        return $stack;
    }

    private function addDictPart(StackedStream $stream, $name, $contents, $filename = null, $headers = [])
    {
        if (is_scalar($contents) || $contents instanceof StreamInterface) {
            return $this->addPart($stream, $name, $contents, $filename, $headers);
        }
        if (is_array($contents) && $this->isNotAssociativeArray($contents)) {
            return $this->addArrayPart($stream, $name, $contents);
        }
    }


    private function addArrayPart(StackedStream $stream, $name, $contents)
    {
        // We add the [] to tell the HTTP request parser we are sending a list entry
        $index = null;
        foreach ($contents as $content) {
            $index = null === $index ? 0 : $index + 1;
            // Here we are required to parse the body as dictionary
            if (is_array($content) && isset($content['name']) && isset($content['contents'])) {
                $this->addDictPart($stream, $name . '[' . $content['name'] . ']', $content['contents'], $content['filename'] ?? null, $content['headers'] ?? []);
                continue;
            }
            if ($this->isNotAssociativeArray($content)) {
                $this->addArrayPart($stream, $name . '[' . $index . ']', $content);
                continue;
            }

            if (is_scalar($content) || $content instanceof StreamInterface) {
                $this->addPart($stream, $name . '[' . $index . ']', $content, null, []);
                continue;
            }
            throw new UnexpectedValueException('Unable to parse multipart value ' . is_array($content) ? json_encode($content) : strval($content));
        }
    }


    /**
     * Add a multipart to the stacked stream
     * 
     * @param StackedStream $stream 
     * @param array $attribute 
     * @return void 
     * @throws InvalidArgumentException 
     */
    private function addPart(StackedStream $stream, $name, $contents, $filename = null, $headers = [])
    {
        $contents = Stream::new(is_scalar($contents) ? (string)$contents : $contents);
        if (empty($filename = ($attribute['filename'] ?? null))) {
            $uri = $contents->getMetadata('uri');
            if ($uri && \is_string($uri) && \substr($uri, 0, 6) !== 'php://' && \substr($uri, 0, 7) !== 'data://') {
                $filename = $uri;
            }
        }
        $this->createPart(
            $name,
            $contents,
            $filename,
            HeadersBag::new($headers)
        )(function (StreamInterface $s, $headers) use ($stream) {
            $this->pushPart($stream, $s, $headers);
        });
    }

    /**
     * Creates a part to the multipart stream
     * 
     * @param mixed $name 
     * @param StreamInterface $stream 
     * @param string|null $filename 
     * @param HeadersBag $headers 
     * @return Closure 
     */
    private function createPart(
        $name,
        StreamInterface $stream,
        string $filename = null,
        HeadersBag $headers
    ) {
        return function (\Closure $callback) use ($name, $stream, $filename, $headers) {
            $disposition = $headers->get('Content-Disposition');
            if (empty($disposition)) {
                $headers['Content-Disposition'] = ($filename === '0' || $filename)
                    ? sprintf(
                        'form-data; name="%s"; filename="%s"',
                        $name,
                        basename($filename)
                    )
                    : "form-data; name=\"{$name}\"";
            }

            $length = $headers->get('Content-Length');
            if (!$length) {
                if ($length = $stream->getSize()) {
                    $headers['Content-Length'] = (string) $length;
                }
            }
            $type = $headers->get('Content-Type');
            if (!$type && ($filename === '0' || $filename)) {
                if ($type = MimeType::extToMime(pathinfo($filename, PATHINFO_EXTENSION))) {
                    $headers['Content-Type'] = $type;
                }
            }
            $callback($stream, $headers->toArray());
        };
    }

    /**
     * Push a part to the multipart stream
     * 
     * @param StackedStream $stream 
     * @param StreamInterface $body 
     * @param array $headers 
     * @return void 
     * @throws InvalidArgumentException 
     */
    private function pushPart(
        StackedStream $stream,
        StreamInterface $body,
        array $headers = []
    ) {
        $stream->push(Stream::new($this->getHeaders($headers)));
        $stream->push($body);
        $stream->push(Stream::new("\r\n"));
    }

    private function isNotAssociativeArray($content)
    {
        if (!is_array($content)) {
            return false;
        }
        $contains_scalar = null;
        foreach ($content as $value) {
            $is_array = is_array($value);
            if (!$is_array && (null === $contains_scalar)) {
                $contains_scalar = true;
            }
            if ($is_array && $contains_scalar) {
                throw new UnexpectedValueException("Please do not mix multipart attributes with scalar type!");
            }
        }
        return (array_keys($content) === range(0, count($content) - 1));
    }

    private function assertDictionary(array $attributes)
    {
        if (!is_array($attributes)) {
            throw new \UnexpectedValueException('Expect each item of the multipart data to be an associative array');
        }
        foreach (['contents', 'name'] as $key) {
            if (!array_key_exists($key, (array)$attributes)) {
                throw new \InvalidArgumentException("'{$key}' is required in multipart attribute");
            }
        }
    }
}

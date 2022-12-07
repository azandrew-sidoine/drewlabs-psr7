<?php

namespace Drewlabs\Psr7;

use Drewlabs\Psr7\Factories\ServerRequestFactory;
use Drewlabs\Psr7\Factories\UploadedFileFactory;
use Drewlabs\Psr7Stream\StreamFactory;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

class CreatesServerRequest
{

    /**
     * 
     * @var ServerRequestFactoryInterface
     */
    private $serverRequestFactory;

    /**
     * 
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * 
     * @var UploadedFileFactoryInterface
     */
    private $uploadedFileFactory;

    /**
     * Creates as server request creator class
     * 
     * @param ServerRequestFactoryInterface|null $serverRequestFactory 
     * @param StreamFactoryInterface|null $streamFactory 
     * @param UploadedFileFactoryInterface|null $uploadedFileFactory 
     * 
     */
    public function __construct(
        ServerRequestFactoryInterface $serverRequestFactory = null,
        UploadedFileFactoryInterface $uploadedFileFactory = null,
        StreamFactoryInterface $streamFactory = null
    ) {
        $this->serverRequestFactory = $serverRequestFactory ?? new ServerRequestFactory;
        $this->streamFactory = $streamFactory ?? new StreamFactory;
        $this->uploadedFileFactory = $uploadedFileFactory ?? new UploadedFileFactory;
    }

    /**
     * Creates server request from PHP globals
     * 
     * @return ServerRequestInterface 
     * @throws InvalidArgumentException 
     */
    public function fromServerGlobals()
    {
        $server = $_SERVER;
        if (!isset($server['REQUEST_METHOD'])) {
            $server['REQUEST_METHOD'] = 'GET';
        }
        $headers = \function_exists('getallheaders') ?
            new HeadersBag(getallheaders() ?? []) :
            HeadersBag::fromServerGlobals($server);

        return $this->fromArrays(
            $server,
            $headers,
            $_COOKIE,
            $_GET,
            $this->createPostVariables($server, $headers),
            $_FILES,
            \fopen('php://input', 'r') ?: null
        );
    }


    /**
     * Creates a serverr request from array of attributes
     * 
     * @param array $server 
     * @param array|\Traversable $headers 
     * @param array $cookie 
     * @param array $get 
     * @param null|array $post 
     * @param array $files 
     * @param mixed $body 
     * @return ServerRequestInterface 
     * @throws InvalidArgumentException 
     */
    public function fromArrays(
        array $server,
        $headers = [],
        array $cookie = [],
        array $get = [],
        ?array $post = null,
        array $files = [],
        $body = null
    ) {
        if (!isset($server['REQUEST_METHOD'])) {
            throw new \InvalidArgumentException('Cannot determine HTTP method');
        }
        $method = $server['REQUEST_METHOD'];
        $uri = Uri::createFromEnvWithHTTP($server);
        $protocol = isset($server['SERVER_PROTOCOL']) ?
            \str_replace('HTTP/', '', $server['SERVER_PROTOCOL']) :
            '1.1';
        /**
         * @var ServerRequestInterface
         */
        $serverRequest = $this->serverRequestFactory->createServerRequest($method, $uri, $server);
        foreach ($headers as $name => $value) {
            $serverRequest = $serverRequest->withAddedHeader($name, $value);
        }
        $serverRequest = $serverRequest
            ->withProtocolVersion($protocol)
            ->withCookieParams($cookie)
            ->withQueryParams($get)
            ->withParsedBody($post)
            ->withUploadedFiles($this->normalizeFiles($files));

        if (null === $body) {
            return $serverRequest;
        }
        return $serverRequest->withBody(Streams::lazy($body));
    }

    /**
     * 
     * @param array $server 
     * @param array|\Traversable $headers 
     * @return array|null 
     */
    private function createPostVariables(array $server, $headers)
    {
        /**
         * @var array|null
         */
        $post = null;
        if ('POST' === $server['REQUEST_METHOD']) {
            foreach ($headers as $name => $header) {
                $header = is_array($header) ? implode(',', $header) : $header;
                if (true === \is_int($name) || 'content-type' !== \strtolower($name)) {
                    continue;
                }
                if (\in_array(
                    \strtolower(\trim(\explode(';', $header, 2)[0])),
                    ['application/x-www-form-urlencoded', 'multipart/form-data']
                )) {
                    $post = $_POST;
                    break;
                }
            }
        }
        return $post;
    }


    /**
     * Normalize list of uploaded files
     * 
     * @param array $files 
     * @return array 
     * @throws InvalidArgumentException 
     */
    private function normalizeFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
            } elseif (\is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = $this->createUploadedFileFromSpec($value);
            } elseif (\is_array($value)) {
                $normalized[$key] = $this->normalizeFiles($value);
            } else {
                throw new \InvalidArgumentException('not a supported value in files specification');
            }
        }
        return $normalized;
    }

    /**
     * Creates uploaded files from file specs
     * 
     * @param array $value 
     * @return array|UploadedFileInterface 
     * @throws InvalidArgumentException 
     */
    private function createUploadedFileFromSpec(array $value)
    {
        if (\is_array($value['tmp_name'])) {
            return $this->normalizeNestedFileSpec($value);
        }
        if (\UPLOAD_ERR_OK !== ($error = is_numeric($value['error'] ?? null) ? intval($value['error']) : $value['error'] ?? null)) {
            $stream = $this->streamFactory->createStream();
        } else {
            try {
                $stream = $this->streamFactory->createStreamFromFile($value['tmp_name']);
            } catch (\RuntimeException $e) {
                $stream = $this->streamFactory->createStream();
            }
        }
        return $this->uploadedFileFactory->createUploadedFile(
            $stream,
            intval($value['size']),
            $error,
            $value['name'] ?? null,
            $value['type'] ?? null
        );
    }


    /**
     * Normalize nested file specs
     * 
     * @param array $files 
     * @return array 
     * @throws InvalidArgumentException 
     */
    private function normalizeNestedFileSpec(array $files = []): array
    {
        $normalizedFiles = [];

        foreach (\array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
            ];
            $normalizedFiles[$key] = $this->createUploadedFileFromSpec($spec);
        }

        return $normalizedFiles;
    }
}

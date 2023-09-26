<?php

use PHPUnit\Framework\TestCase;

use Drewlabs\Psr7\CreatesServerRequest;
use Drewlabs\Psr7\HeadersBag;
use Drewlabs\Psr7\UploadedFile;
use Drewlabs\Psr7\Uri;
use Drewlabs\Psr7Stream\StreamFactory;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

class CreatesServerRequestTest extends TestCase
{

    const NUMBER_OF_FILES = 11;

    private static $filenames = [];

    /** @var CreatesServerRequest */
    private static $creator;

    private static function initTestFiles()
    {
        if (!empty(self::$filenames)) {
            return;
        }
        $tmpDir = sys_get_temp_dir();
        for ($i = 0; $i < self::NUMBER_OF_FILES; ++$i) {
            self::$filenames[] = $filename = $tmpDir . '/file_' . $i;
            file_put_contents($filename, 'foo' . $i);
        }
    }

    private static function setCreatorInstance()
    {
        if (null === self::$creator) {
            self::$creator = new CreatesServerRequest();
        }
    }

    private static function invoke($objectOrClass, string $method, ...$arguments)
    {
        if (is_string($objectOrClass)) {
            if (!class_exists($objectOrClass)) {
                throw new \LogicException('Could not find class "%s"', $objectOrClass);
            }
        } else {
            if ($objectOrClass instanceof \stdClass) {
                throw new InvalidArgumentException('Can not get a method of \stdClass.');
            }
            if (!is_object($objectOrClass)) {
                throw new UnexpectedValueException(sprintf('Can not get a property of a non object. Variable of type "%s" was given.',  gettype($objectOrClass)));
            }
        }
        $reflector = new \ReflectionClass($objectOrClass);
        if (!$reflector->hasMethod($method)) {
            throw new \LogicException(sprintf('The method %s::%s does not exist.', get_class($objectOrClass), $method));
        }

        $method = $reflector->getMethod($method);
        $method->setAccessible(true);
        // If it is a static call we should pass null as first parameter to \ReflectionMethod::invokeArgs
        $object = null;
        if (!$method->isStatic()) {
            $object = $objectOrClass;
            if (!is_object($objectOrClass)) {
                throw new UnexpectedValueException(sprintf('Can not access non-static method without an object.'));
            }
        }
        return $method->invokeArgs($object, $arguments);
    }

    public static function normalizeFiles()
    {
        self::initTestFiles();

        return [
            'Single file' => [
                [
                    'file' => [
                        'name' => 'MyFile.txt',
                        'type' => 'text/plain',
                        'tmp_name' => self::$filenames[0],
                        'error' => '0',
                        'size' => '123',
                    ],
                ],
                [
                    'file' => new UploadedFile(
                        self::$filenames[0],
                        123,
                        UPLOAD_ERR_OK,
                        'MyFile.txt',
                        'text/plain'
                    ),
                ],
            ],
            'Already Converted' => [
                [
                    'file' => new UploadedFile(
                        self::$filenames[1],
                        123,
                        UPLOAD_ERR_OK,
                        'MyFile.txt',
                        'text/plain'
                    ),
                ],
                [
                    'file' => new UploadedFile(
                        self::$filenames[1],
                        123,
                        UPLOAD_ERR_OK,
                        'MyFile.txt',
                        'text/plain'
                    ),
                ],
            ],
            'Already Converted array' => [
                [
                    'file' => [
                        new UploadedFile(
                            self::$filenames[2],
                            123,
                            UPLOAD_ERR_OK,
                            'MyFile.txt',
                            'text/plain'
                        ),
                        new UploadedFile(
                            '',
                            0,
                            UPLOAD_ERR_NO_FILE,
                            '',
                            ''
                        ),
                    ],
                ],
                [
                    'file' => [
                        new UploadedFile(
                            self::$filenames[2],
                            123,
                            UPLOAD_ERR_OK,
                            'MyFile.txt',
                            'text/plain'
                        ),
                        new UploadedFile(
                            '',
                            0,
                            UPLOAD_ERR_NO_FILE,
                            '',
                            ''
                        ),
                    ],
                ],
            ],
            'Multiple files' => [
                [
                    'text_file' => [
                        'name' => 'MyFile.txt',
                        'type' => 'text/plain',
                        'tmp_name' => self::$filenames[3],
                        'error' => '0',
                        'size' => '123',
                    ],
                    'image_file' => [
                        'name' => '',
                        'type' => '',
                        'tmp_name' => self::$filenames[4],
                        'error' => '4',
                        'size' => '0',
                    ],
                ],
                [
                    'text_file' => new UploadedFile(
                        self::$filenames[3],
                        123,
                        UPLOAD_ERR_OK,
                        'MyFile.txt',
                        'text/plain'
                    ),
                    'image_file' => new UploadedFile(
                        self::$filenames[4],
                        0,
                        UPLOAD_ERR_NO_FILE,
                        '',
                        ''
                    ),
                ],
            ],
            'Nested files' => [
                [
                    'file' => [
                        'name' => [
                            0 => 'MyFile.txt',
                            1 => 'Image.png',
                        ],
                        'type' => [
                            0 => 'text/plain',
                            1 => 'image/png',
                        ],
                        'tmp_name' => [
                            0 => self::$filenames[5],
                            1 => self::$filenames[6],
                        ],
                        'error' => [
                            0 => '0',
                            1 => '0',
                        ],
                        'size' => [
                            0 => '123',
                            1 => '7349',
                        ],
                    ],
                    'nested' => [
                        'name' => [
                            'other' => 'Flag.txt',
                            'test' => [
                                0 => 'Stuff.txt',
                                1 => '',
                            ],
                        ],
                        'type' => [
                            'other' => 'text/plain',
                            'test' => [
                                0 => 'text/plain',
                                1 => '',
                            ],
                        ],
                        'tmp_name' => [
                            'other' => self::$filenames[7],
                            'test' => [
                                0 => self::$filenames[8],
                                1 => self::$filenames[9],
                            ],
                        ],
                        'error' => [
                            'other' => '0',
                            'test' => [
                                0 => '0',
                                1 => '4',
                            ],
                        ],
                        'size' => [
                            'other' => '421',
                            'test' => [
                                0 => '32',
                                1 => '0',
                            ],
                        ],
                    ],
                ],
                [
                    'file' => [
                        0 => new UploadedFile(
                            self::$filenames[5],
                            123,
                            UPLOAD_ERR_OK,
                            'MyFile.txt',
                            'text/plain'
                        ),
                        1 => new UploadedFile(
                            self::$filenames[6],
                            7349,
                            UPLOAD_ERR_OK,
                            'Image.png',
                            'image/png'
                        ),
                    ],
                    'nested' => [
                        'other' => new UploadedFile(
                            self::$filenames[7],
                            421,
                            UPLOAD_ERR_OK,
                            'Flag.txt',
                            'text/plain'
                        ),
                        'test' => [
                            0 => new UploadedFile(
                                self::$filenames[8],
                                32,
                                UPLOAD_ERR_OK,
                                'Stuff.txt',
                                'text/plain'
                            ),
                            1 => new UploadedFile(
                                self::$filenames[9],
                                0,
                                UPLOAD_ERR_NO_FILE,
                                '',
                                ''
                            ),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider normalizeFiles
     */
    public function test_normalize_files($files, $expected)
    {
        //#region Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        self::initTestFiles();
        $this->setCreatorInstance();
        //#endregion Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        $result = self::$creator
            ->fromArrays(['REQUEST_METHOD' => 'POST'], [], [], [], [], $files)
            ->getUploadedFiles();

        $deepValidate = function (UploadedFileInterface $expectedFile, UploadedFileInterface $file, $index) {
            $this->assertEquals($expectedFile->getSize(), $file->getSize());
            $this->assertEquals($expectedFile->getError(), $file->getError());
            $this->assertEquals($expectedFile->getClientFilename(), $file->getClientFilename());
            $this->assertEquals($expectedFile->getClientMediaType(), $file->getClientMediaType());
            if (UPLOAD_ERR_OK === $expectedFile->getError()) {
                $this->assertEquals(
                    $expectedFile->getStream()->getMetadata('uri'),
                    $file->getStream()->getMetadata('uri')
                );
            }
        };

        $validate = function ($expected, $result, $self) use ($deepValidate) {
            foreach ($expected as $i => $e) {
                if (is_array($e)) {
                    $self($e, $result[$i], $self);

                    continue;
                }
                $this->assertNotEmpty($result[$i]);
                $deepValidate($e, $result[$i], $i);
            }
        };

        $validate($expected, $result, $validate);
    }

    public function test_normalize_files_raises_exception()
    {
        //#region Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        self::initTestFiles();
        $this->setCreatorInstance();
        //#endregion Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not a supported value in files specification');
        self::$creator->fromArrays(['REQUEST_METHOD' => 'POST'], [], [], [], [], ['test' => 'something']);
    }

    public function test_numeric_header_from_header_array()
    {
        //#region Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        self::initTestFiles();
        $this->setCreatorInstance();
        //#endregion Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        $server = [
            'REQUEST_METHOD' => 'GET',
        ];

        $server = self::$creator->fromArrays($server, ['1234' => 'NumericHeader']);
        $this->assertEquals(['1234' => ['NumericHeader']], $server->getHeaders());
    }

    public function test_from_arrays()
    {
        //#region Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        self::initTestFiles();
        $this->setCreatorInstance();
        //#endregion Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        $server = [
            'PHP_SELF' => '/blog/article.php',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'SERVER_ADDR' => 'Server IP: 217.112.82.20',
            'SERVER_NAME' => 'www.blakesimpson.co.uk',
            'SERVER_SOFTWARE' => 'Apache/2.2.15 (Win32) JRun/4.0 PHP/5.2.13',
            'SERVER_PROTOCOL' => 'HTTP/1.0',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_TIME' => 'Request start time: 1280149029',
            'QUERY_STRING' => 'id=10&user=foo',
            'DOCUMENT_ROOT' => '/path/to/your/server/root/',
            'HTTP_0' => 'NumericHeaderZero',
            'HTTP_1234' => 'NumericHeader',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'HTTP_ACCEPT_ENCODING' => 'gzip,deflate',
            'HTTP_ACCEPT_LANGUAGE' => 'en-gb,en;q=0.5',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_HOST' => 'www.blakesimpson.co.uk',
            'HTTP_REFERER' => 'http://previous.url.com',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.9.2.6) Gecko/20100625 Firefox/3.6.6 ( .NET CLR 3.5.30729)',
            'HTTPS' => '1',
            'REMOTE_ADDR' => '193.60.168.69',
            'REMOTE_HOST' => 'Client server\'s host name',
            'REMOTE_PORT' => '5390',
            'SCRIPT_FILENAME' => '/path/to/this/script.php',
            'SERVER_ADMIN' => 'webmaster@blakesimpson.co.uk',
            'SERVER_PORT' => '80',
            'SERVER_SIGNATURE' => 'Version signature: 5.123',
            'SCRIPT_NAME' => '/blog/article.php',
            'REQUEST_URI' => '/blog/article.php?id=10&user=foo',
        ];

        $cookie = [
            'logged-in' => 'yes!',
        ];

        $post = [
            'name' => 'Pesho',
            'email' => 'pesho@example.com',
        ];

        $get = [
            'id' => 10,
            'user' => 'foo',
        ];

        $files = [
            'file' => [
                'name' => 'MyFile.txt',
                'type' => 'text/plain',
                'tmp_name' => self::$filenames[10],
                'error' => UPLOAD_ERR_OK,
                'size' => 5,
            ],
        ];

        $server = self::$creator->fromArrays($server, [], $cookie, $get, $post, $files, 'foobar');

        $this->assertEquals('POST', $server->getMethod());
        $this->assertEquals(['host' => ['www.blakesimpson.co.uk']], $server->getHeaders());
        $this->assertEquals('foobar', (string) $server->getBody());
        $this->assertEquals('1.0', $server->getProtocolVersion());
        $this->assertEquals($cookie, $server->getCookieParams());
        $this->assertEquals($post, $server->getParsedBody());
        $this->assertEquals($get, $server->getQueryParams());

        $this->assertEquals(
            Uri::new('http://www.blakesimpson.co.uk/blog/article.php?id=10&user=foo'),
            $server->getUri()
        );

        /** @var UploadedFile $file */
        $file = $server->getUploadedFiles()['file'];

        $this->assertEquals(5, $file->getSize());
        $this->assertEquals(UPLOAD_ERR_OK, $file->getError());
        $this->assertEquals('MyFile.txt', $file->getClientFilename());
        $this->assertEquals('text/plain', $file->getClientMediaType());
        $this->assertEquals(self::$filenames[10], $file->getStream()->getMetadata('uri'));
    }

    public static function getUriFromGlobals()
    {
        //#region Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        self::initTestFiles();
        self::setCreatorInstance();
        //#endregion Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        self::initTestFiles();
        $server = [
            'PHP_SELF' => '/blog/article.php',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'SERVER_ADDR' => 'Server IP: 217.112.82.20',
            'SERVER_NAME' => 'www.blakesimpson.co.uk',
            'SERVER_SOFTWARE' => 'Apache/2.2.15 (Win32) JRun/4.0 PHP/5.2.13',
            'SERVER_PROTOCOL' => 'HTTP/1.0',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_TIME' => 'Request start time: 1280149029',
            'QUERY_STRING' => 'id=10&user=foo',
            'DOCUMENT_ROOT' => '/path/to/your/server/root/',
            'HTTP_0' => 'NumericHeaderZero',
            'HTTP_1234' => 'NumericHeader',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'HTTP_ACCEPT_ENCODING' => 'gzip,deflate',
            'HTTP_ACCEPT_LANGUAGE' => 'en-gb,en;q=0.5',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_HOST' => 'www.blakesimpson.co.uk',
            'HTTP_REFERER' => 'http://previous.url.com',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.9.2.6) Gecko/20100625 Firefox/3.6.6 ( .NET CLR 3.5.30729)',
            'HTTPS' => '1',
            'REMOTE_ADDR' => '193.60.168.69',
            'REMOTE_HOST' => 'Client server\'s host name',
            'REMOTE_PORT' => '5390',
            'SCRIPT_FILENAME' => '/path/to/this/script.php',
            'SERVER_ADMIN' => 'webmaster@blakesimpson.co.uk',
            'SERVER_PORT' => '80',
            'SERVER_SIGNATURE' => 'Version signature: 5.123',
            'SCRIPT_NAME' => '/blog/article.php',
            'REQUEST_URI' => '/blog/article.php?id=10&user=foo',
        ];

        return [
            'Normal request' => [
                'http://www.blakesimpson.co.uk/blog/article.php?id=10&user=foo',
                $server,
            ],
            'Secure request' => [
                'https://www.blakesimpson.co.uk/blog/article.php?id=10&user=foo',
                array_merge($server, ['HTTPS' => 'on', 'SERVER_PORT' => '443']),
            ],
            'Secure request via proxy' => [
                'https://www.blakesimpson.co.uk/blog/article.php?id=10&user=foo',
                array_merge($server, ['HTTP_X_FORWARDED_PROTO' => 'https', 'SERVER_PORT' => '80']),
            ],
            'HTTP_HOST missing' => [
                'http://www.blakesimpson.co.uk/blog/article.php?id=10&user=foo',
                array_merge($server, ['HTTP_HOST' => null]),
            ],
            'No query String' => [
                'http://www.blakesimpson.co.uk/blog/article.php',
                array_merge($server, ['REQUEST_URI' => '/blog/article.php', 'QUERY_STRING' => '']),
            ],
            'Different port' => [
                'http://www.blakesimpson.co.uk:8324/blog/article.php?id=10&user=foo',
                array_merge($server, ['SERVER_PORT' => '8324', 'HTTP_HOST' => $server['HTTP_HOST'] . ':8324']),
            ],
            'IPv4' => [
                'http://127.0.0.1/blog/article.php?id=10&user=foo',
                array_merge($server, ['SERVER_PORT' => '80', 'HTTP_HOST' => '127.0.0.1']),
            ],
            'IPv4 with port' => [
                'http://127.0.0.1:8324/blog/article.php?id=10&user=foo',
                array_merge($server, ['SERVER_PORT' => '8324', 'HTTP_HOST' => '127.0.0.1:8324']),
            ],
            'IPv6 with port' => [
                'http://::1:8324/blog/article.php?id=10&user=foo',
                array_merge($server, ['SERVER_PORT' => '8324', 'HTTP_HOST' => '::1:8324']),
            ],
            'Empty server variable' => [
                '',
                [],
            ],
        ];
    }

    /**
     * @dataProvider getUriFromGlobals
     */
    public function test_get_uri_from_flobals($expected, $serverParams)
    {
        $this->assertEquals(new Uri($expected), static::invoke(Uri::class, 'createFromServerGlobal', $serverParams));
    }

    /**
     * Test from laminas/laminas-diactoros (formerly zendframework/zend-diactoros).
     */
    public function test_marshals_expected_headers_from_server_array()
    {
        $server = [
            'HTTP_0' => 'NumericHeaderZero',
            'HTTP_1234' => 'NumericHeader',
            'HTTP_COOKIE' => 'COOKIE',
            'HTTP_AUTHORIZATION' => 'token',
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_FOO_BAR' => 'FOOBAR',
            'CONTENT_MD5' => 'CONTENT-MD5',
            'CONTENT_LENGTH' => 'UNSPECIFIED',
        ];

        $expected = [
            '0' => ['NumericHeaderZero'],
            '1234' => ['NumericHeader'],
            'cookie' => ['COOKIE'],
            'authorization' => ['token'],
            'content-type' => ['application/json'],
            'accept' => ['application/json'],
            'x-foo-bar' => ['FOOBAR'],
            'content-md5' => ['CONTENT-MD5'],
            'content-length' => ['UNSPECIFIED'],
        ];

        $this->assertSame($expected, HeadersBag::fromServerGlobals($server)->toArray());
    }


    public function test_marshals_variables_prefixed_by_apache_from_server_array()
    {
        // Non-prefixed versions will be preferred
        $server = [
            'HTTP_X_FOO_BAR' => 'nonprefixed',
            'REDIRECT_HTTP_AUTHORIZATION' => 'token',
            'REDIRECT_HTTP_X_FOO_BAR' => 'prefixed',
        ];

        $expected = [
            'authorization' => ['token'],
            'x-foo-bar' => ['nonprefixed'],
        ];
        $this->assertEquals($expected, HeadersBag::fromServerGlobals($server)->toArray());
    }

    /**
     * Test the fallback for a failing StreamFactoryInterface::createStreamFromFile.
     */
    public function test_failing_stream_from_file()
    {
        //#region Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        self::initTestFiles();
        $this->setCreatorInstance();
        //#endregion Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        $defaultStreamFactory = new StreamFactory;
        $psr17StreamFactory = $this->createMock(StreamFactoryInterface::class);
        $psr17StreamFactory->method('createStreamFromFile')
            ->will($this->throwException(new \RuntimeException()));
        $psr17StreamFactory->method('createStream')
            ->will($this->returnCallback([$defaultStreamFactory, 'createStream']));
        $creator = new CreatesServerRequest(null, null, $psr17StreamFactory);
        $expected = new UploadedFile(
            '',
            0,
            \UPLOAD_ERR_CANT_WRITE,
            'MyFile.txt',
            'text/plain'
        );
        $created = static::invoke(
            $creator,
            'createUploadedFileFromSpec',
            [
                'name' => 'MyFile.txt',
                'type' => 'text/plain',
                'tmp_name' => '',
                'error' => \UPLOAD_ERR_CANT_WRITE,
                'size' => 0,
            ]
        );
        $this->assertEquals($expected, $created);
    }

    public function test_no_parsed_body_without_post_method()
    {
        //#region Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        self::initTestFiles();
        $this->setCreatorInstance();
        //#endregion Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        $_POST = ['a' => 'b', 'c' => 'd'];
        $instance = self::$creator->fromServerGlobals();
        $this->assertNull($instance->getParsedBody());
    }

    /**
     * @backupGlobals enabled
     */
    public function test_no_parsed_body_with_post_method_without_content_type()
    {
        //#region Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        self::initTestFiles();
        $this->setCreatorInstance();
        //#endregion Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['a' => 'b', 'c' => 'd'];
        $instance = self::$creator->fromServerGlobals();
        $this->assertNull($instance->getParsedBody());
    }

    /**
     * @backupGlobals enabled
     * @dataProvider contentTypesThatTriggerParsedBody
     */
    public function test_no_parsed_body_with_post_method_different_content_type($parsedBody, $contentType)
    {
        //#region Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        self::initTestFiles();
        $this->setCreatorInstance();
        //#endregion Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_CONTENT_TYPE'] = $contentType;
        $_POST = ['a' => 'b', 'c' => 'd'];
        $instance = self::$creator->fromServerGlobals();
        $this->assertSame($parsedBody ? $_POST : null, $instance->getParsedBody());
    }

    public static function contentTypesThatTriggerParsedBody()
    {
        return [
            // Acceptable values
            'Standard HTML Form' => [
                true, 'application/x-www-form-urlencoded',
            ],
            'HTML Form with MIME body' => [
                true, 'multipart/form-data',
            ],
            'Standard HTML Form, mixed case MIME' => [
                true, 'appLication/x-WWW-form-URLEncoded',
            ],
            'Standard HTML Form, surrounding whitespace' => [
                true, '  application/x-www-form-urlencoded ',
            ],
            'Standard HTML Form, with flags' => [
                true, 'application/x-www-form-urlencoded;charset=utf-8',
            ],
            // Nonacceptable values
            'JSON is not parsed by PHP' => [
                false, 'application/json',
            ],
        ];
    }

    /**
     * @backupGlobals enabled
     */
    public function test_numeric_header_from_globals()
    {
        //#region Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        self::initTestFiles();
        $this->setCreatorInstance();
        //#endregion Because of PHP 7.0 issues we can not use the setUp method of PHP unit
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_1234'] = 'NumericHeader';
        $server = self::$creator->fromServerGlobals();
        $this->assertEquals(['1234' => ['NumericHeader']], $server->getHeaders());
    }
}

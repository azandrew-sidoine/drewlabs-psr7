<?php

use Drewlabs\Psr7\Streams;
use Drewlabs\Psr7\UploadedFile;
use Drewlabs\Psr7Stream\StreamFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\Attributes\DataProvider;

class UploadedFileTest extends TestCase
{
    protected $cleanup;

    public function runSetUp()
    {
        $this->cleanup = [];
        return;
    }

    public function runTearDown()
    {
        foreach ($this->cleanup as $file) {
            if (is_scalar($file) && file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function runUnitTest(\Closure $callback)
    {
        $this->runSetUp();
        $callback();
        $this->runTearDown();
    }

    public static function invalidStreams()
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.1],
            'array' => [['filename']],
            'object' => [(object) ['filename']],
        ];
    }

    /**
     * @dataProvider invalidStreams
     */
    #[DataProvider('invalidStreams')]
    public function test_raises_exception_on_invalid_stream_or_file($streamOrFile)
    {
        $this->runUnitTest(function () use ($streamOrFile) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Invalid resource provided for file');
            new UploadedFile($streamOrFile, 0, UPLOAD_ERR_OK);
        });
    }

    public static function invalidFilenamesAndMediaTypes()
    {
        return [
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.1],
            'array' => [['string']],
            'object' => [(object) ['string']],
        ];
    }

    /**
     * @dataProvider invalidFilenamesAndMediaTypes
     */
    #[DataProvider('invalidFilenamesAndMediaTypes')]
    public function test_raises_exception_on_invalid_client_filename($filename)
    {
        $this->runUnitTest(function () use ($filename) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('filename');
            new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, $filename);
        });
    }

    /**
     * @dataProvider invalidFilenamesAndMediaTypes
     */
    #[DataProvider('invalidFilenamesAndMediaTypes')]
    public function test_raises_exception_on_invalid_client_media_type($mediaType)
    {
        $this->runUnitTest(function () use ($mediaType) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('media type');
            new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, 'foobar.baz', $mediaType);
        });
    }

    public function test_get_stream_returns_original_stream_object()
    {
        $this->runUnitTest(function () {
            $stream = Streams::create('');
            $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
            $this->assertSame($stream, $upload->getStream());
        });
    }

    public function test_get_stream_returns_wrapped_php_stream()
    {
        $this->runUnitTest(function () {
            $stream = fopen('php://temp', 'wb+');
            $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
            $uploadStream = $upload->getStream()->detach();
            $this->assertSame($stream, $uploadStream);
        });
    }

    public function test_get_stream()
    {
        $this->runUnitTest(function () {
            $upload = new UploadedFile(__DIR__ . '/Resources/foo.txt', 0, UPLOAD_ERR_OK);
            $stream = $upload->getStream();
            $this->assertInstanceOf(StreamInterface::class, $stream);
            $this->assertEquals('Foobar' . PHP_EOL, $stream->__toString());
        });
    }

    public function test_successful()
    {
        $this->runUnitTest(function () {
            $stream = Streams::create('Foo bar!');
            $upload = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK, 'filename.txt', 'text/plain');
            $this->assertEquals($stream->getSize(), $upload->getSize());
            $this->assertEquals('filename.txt', $upload->getClientFilename());
            $this->assertEquals('text/plain', $upload->getClientMediaType());
            $this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'successful');
            $upload->moveTo($to);
            $this->assertFileExists($to);
            $this->assertEquals($stream->__toString(), file_get_contents($to));
        });
    }

    public function test_move_cannot_be_called_more_than_once()
    {
        $this->runUnitTest(function () {
            $stream = (new  StreamFactory)->createStream('Foo bar!');
            $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
            $this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'diac');
            $upload->moveTo($to);
            $this->assertTrue(file_exists($to));
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('moved');
            $upload->moveTo($to);
        });
    }

    public function test_cannot_retrieve_stream_after_move()
    {
        $this->runUnitTest(function () {
            $stream = (new  StreamFactory)->createStream('Foo bar!');
            $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
            $this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'diac');
            $upload->moveTo($to);
            $this->assertFileExists($to);
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('moved');
            $upload->getStream();
        });
    }

    public static function nonOkErrorStatus()
    {
        return [
            'UPLOAD_ERR_INI_SIZE' => [UPLOAD_ERR_INI_SIZE],
            'UPLOAD_ERR_FORM_SIZE' => [UPLOAD_ERR_FORM_SIZE],
            'UPLOAD_ERR_PARTIAL' => [UPLOAD_ERR_PARTIAL],
            'UPLOAD_ERR_NO_FILE' => [UPLOAD_ERR_NO_FILE],
            'UPLOAD_ERR_NO_TMP_DIR' => [UPLOAD_ERR_NO_TMP_DIR],
            'UPLOAD_ERR_CANT_WRITE' => [UPLOAD_ERR_CANT_WRITE],
            'UPLOAD_ERR_EXTENSION' => [UPLOAD_ERR_EXTENSION],
        ];
    }

    /**
     * @dataProvider nonOkErrorStatus
     */
    #[DataProvider('nonOkErrorStatus')]
    public function test_constructor_does_not_raise_exception_for_invalid_stream_when_error_status_present($status)
    {
        $this->runUnitTest(function () use ($status) {
            $uploadedFile = new UploadedFile('not ok', 0, $status);
            $this->assertSame($status, $uploadedFile->getError());
        });
    }

    /**
     * @dataProvider nonOkErrorStatus
     */
    #[DataProvider('nonOkErrorStatus')]
    public function test_move_to_raises_exception_when_error_status_present($status)
    {
        $this->runUnitTest(function () use ($status) {
            $uploadedFile = new UploadedFile('not ok', 0, $status);
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('upload error');
            $uploadedFile->moveTo(__DIR__ . '/' . uniqid());
        });
    }

    /**
     * @dataProvider nonOkErrorStatus
     */
    #[DataProvider('nonOkErrorStatus')]
    public function test_get_stream_raises_exception_when_error_status_present($status)
    {
        $this->runUnitTest(function () use ($status) {
            $uploadedFile = new UploadedFile('not ok', 0, $status);
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('upload error');
            $uploadedFile->getStream();
        });
    }

    public function test_move_to_creates_stream_if_only_a_filename_was_provided()
    {
        $this->runUnitTest(function () {
            $this->cleanup[] = $from = tempnam(sys_get_temp_dir(), 'copy_from');
            $this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'copy_to');
            copy(__FILE__, $from);
            $uploadedFile = new UploadedFile($from, 100, UPLOAD_ERR_OK, basename($from), 'text/plain');
            $uploadedFile->moveTo($to);
            $this->assertFileEquals(__FILE__, $to);
        });
    }
}

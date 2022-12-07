<?php

use Drewlabs\Psr7\ServerRequest;
use Drewlabs\Psr7\UploadedFile;
use PHPUnit\Framework\TestCase;

class ServerRequestTest extends TestCase
{
    public function testUploadedFiles()
    {
        $request1 = new ServerRequest('GET', '/');

        $files = [
            'file' => new UploadedFile('test', 123, UPLOAD_ERR_OK),
        ];

        $request2 = $request1->withUploadedFiles($files);

        $this->assertNotSame($request2, $request1);
        $this->assertSame([], $request1->getUploadedFiles());
        $this->assertSame($files, $request2->getUploadedFiles());
    }

    public function testServerParams()
    {
        $params = ['name' => 'value'];

        $request = new ServerRequest('GET', '/', [], null, '1.1', $params);
        $this->assertSame($params, $request->getServerParams());
    }

    public function testCookieParams()
    {
        $request1 = new ServerRequest('GET', '/');

        $params = ['name' => 'value'];

        $request2 = $request1->withCookieParams($params);

        $this->assertNotSame($request2, $request1);
        $this->assertEmpty($request1->getCookieParams());
        $this->assertSame($params, $request2->getCookieParams());
    }

    public function test_server_request_query_params()
    {
        $request1 = new ServerRequest('GET', '/');

        $params = ['name' => 'value'];

        $request2 = $request1->withQueryParams($params);

        $this->assertNotSame($request2, $request1);
        $this->assertEmpty($request1->getQueryParams());
        $this->assertSame($params, $request2->getQueryParams());
    }

    public function test_server_request_parsed_body()
    {
        $request1 = new ServerRequest('GET', '/');
        $params = ['name' => 'value'];
        $request2 = $request1->withParsedBody($params);

        $this->assertNotSame($request2, $request1);
        $this->assertEmpty($request1->getParsedBody());
        $this->assertSame($params, $request2->getParsedBody());
    }

    public function test_server_request_attributes_methods()
    {
        $request1 = new ServerRequest('GET', '/');

        $request2 = $request1->withAttribute('name', 'value');
        $request3 = $request2->withAttribute('other', 'otherValue');
        $request4 = $request3->withoutAttribute('other');
        $request5 = $request3->withoutAttribute('unknown');

        $this->assertNotSame($request2, $request1);
        $this->assertNotSame($request3, $request2);
        $this->assertNotSame($request4, $request3);
        $this->assertNotSame($request5, $request4);

        $this->assertEmpty($request1->getAttributes());
        $this->assertEmpty($request1->getAttribute('name'));
        $this->assertEquals(
            'something',
            $request1->getAttribute('name', 'something'),
            'Should return the default value'
        );

        $this->assertEquals('value', $request2->getAttribute('name'));
        $this->assertEquals(['name' => 'value'], $request2->getAttributes());
        $this->assertEquals(['name' => 'value', 'other' => 'otherValue'], $request3->getAttributes());
        $this->assertEquals(['name' => 'value'], $request4->getAttributes());
    }

    public function test_server_request_null_attributes()
    {
        $request = (new ServerRequest('GET', '/'))->withAttribute('name', null);
        $this->assertSame(['name' => null], $request->getAttributes());
        $this->assertNull($request->getAttribute('name', 'different-default'));
        $requestWithoutAttribute = $request->withoutAttribute('name');
        $this->assertSame([], $requestWithoutAttribute->getAttributes());
        $this->assertSame('different-default', $requestWithoutAttribute->getAttribute('name', 'different-default'));
    }
}

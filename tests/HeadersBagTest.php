<?php

use Drewlabs\Psr7\HeadersBag;
use PHPUnit\Framework\TestCase;

class HeadersBagTest extends TestCase
{

    public function test_headers_bag_constructor()
    {
        $headers = HeadersBag::new([]);
        $this->assertInstanceOf(HeadersBag::class, $headers);
    }

    public function test_headers_bag_set_method()
    {
        $headers = HeadersBag::new();
        $headers->set('Content-Type', 'application/json');

        $this->assertEquals(['application/json'], $headers->get('content-type'));
    }

    public function test_headers_bag_get_returns_empty_array_if_header_is_missing()
    {
        $headers = HeadersBag::new(['Accept-Encoding' => 'gzip,deflate']);
        $this->assertEquals([], $headers->get('Content-Type'));
    }

    public function test_headers_bag_remove()
    {
        $headers = HeadersBag::new(['Accept-Encoding' => 'gzip,deflate']);
        $this->assertEquals(['gzip,deflate'], $headers->get('Accept-Encoding'));
        $headers->remove('Accept-Encoding');
        $this->assertEquals([], $headers->get('Accept-Encoding'));
    }

    public function test_headers_bag_has_returns_true_if_header_is_present()
    {
        $headers = HeadersBag::new(['Accept-Encoding' => 'gzip,deflate']);
        $this->assertTrue($headers->has('Accept-Encoding'));
        $this->assertFalse($headers->has('Content-Type'));
    }
}
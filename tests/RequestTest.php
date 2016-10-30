<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-29
 * Time: 下午3:17
 */

namespace Tests\PhMessage;

use PhMessage\FnStream;
use PhMessage\Request;
use PhMessage\Uri;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testRequestUriMeBeString()
    {
        $r = new Request('GET', '/');
        $this->assertEquals('/', $r->getUri());
    }

    public function testRequestUriMayBeUri()
    {
        $uri = new Uri('/');
        $r = new Request('GET', $uri);

        $this->assertEquals('/', $r->getUri());
    }

    public function testValidateRequestUri()
    {
        $r = new Request('GET', '/', [], 'baz');

        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $r->getBody());

        $this->assertEquals('baz', (string) $r->getBody());
    }

    public function testNullBody()
    {
        $r = new Request('GET', '/', [], null);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $r->getBody());
        $this->assertSame('', (string) $r->getBody());
    }

    public function testFalseyBody()
    {
        $r = new Request('GET', '/', [], '0');
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $r->getBody());
        $this->assertSame('0', (string) $r->getBody());
    }


    public function testConstructorDoseNotReadStreamBody()
    {
        $streamIsRead = false;

        $body = FnStream::decorate(\PhMessage\stream_for(''), [
            '__toString' => function () use (&$streamIsRead) {
                $streamIsRead = true;
                return '';
            }
        ]);

        $r = new Request('GET', '/', [], $body);

        $this->assertFalse($streamIsRead);
        $this->assertSame($body, $r->getBody());

    }
}

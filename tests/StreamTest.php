<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-29
 * Time: 下午4:24
 */

namespace Tests\PhMessage;


use PhMessage\Stream;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorThrowsExceptionOnInvalidArgument()
    {
        new Stream(true);
    }

    public function testConstructorInitializesProperties()
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, 'data');

        $stream = new Stream($handle);
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isSeekable());
        $this->assertEquals('php://temp', $stream->getMetadata('uri'));
        $this->assertInternalType('array', $stream->getMetadata());
        $this->assertEquals(4, $stream->getSize());
        $this->assertFalse($stream->eof());

        $stream->close();
    }

    public function testStreamClosesHandleOnDestruct()
    {
        $handle = fopen('php://temp', 'r');
        $stream = new Stream($handle);
        unset($stream);
        $this->assertFalse(is_resource($handle));
    }
}

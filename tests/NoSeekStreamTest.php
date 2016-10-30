<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-30
 * Time: 下午8:18
 */

namespace Tests\PhMessage;


use PhMessage\NoSeekStream;
use PHPUnit\Framework\TestCase;

class NoSeekStreamTest  extends TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot seek a NoSeekStream
     */
    public function testCannotSeek()
    {
        $s = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(['isSeekable', 'seek'])
            ->getMockForAbstractClass();
        $s->expects($this->never())->method('seek');
        $s->expects($this->never())->method('isSeekable');
        $wrapped = new NoSeekStream($s);
        $this->assertFalse($wrapped->isSeekable());
        $wrapped->seek(2);
    }
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot write to a non-writable stream
     */
    public function testHandlesClose()
    {
        $s = \PhMessage\stream_for('foo');
        $wrapped = new NoSeekStream($s);
        $wrapped->close();
        $wrapped->write('foo');
    }
}

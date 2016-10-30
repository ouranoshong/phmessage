<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-30
 * Time: 下午9:18
 */

namespace Tests\PhMessage;


use PhMessage\BufferStream;
use PhMessage\DroppingStream;
use PHPUnit\Framework\TestCase;

class DroppingStreamTest extends TestCase
{
      public function testBeginsDroppingWhenSizeExceeded()
      {
          $stream = new BufferStream();
          $drop = new DroppingStream($stream, 5);
          $this->assertEquals(3, $drop->write('hel'));
          $this->assertEquals(2, $drop->write('lo'));
          $this->assertEquals(5, $drop->getSize());
          $this->assertEquals('hello', $drop->read(5));
          $this->assertEquals(0, $drop->getSize());
          $drop->write('12345678910');
          $this->assertEquals(5, $stream->getSize());
          $this->assertEquals(5, $drop->getSize());
          $this->assertEquals('12345', (string) $drop);
          $this->assertEquals(0, $drop->getSize());
          $drop->write('hello');
          $this->assertSame(0, $drop->write('test'));
      }
}

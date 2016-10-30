<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-30
 * Time: 下午8:54
 */

namespace Tests\PhMessage;


use PhMessage\InflateStream;
use PHPUnit\Framework\TestCase;

class InflateStreamTest extends TestCase
{
    public function testInflatesStreams()
    {
        $content = gzencode('test');
        $a = \PhMessage\stream_for($content);
        $b = new InflateStream($a);
        $this->assertEquals('test', (string) $b);
    }

}

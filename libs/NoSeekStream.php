<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-27
 * Time: 下午10:50.
 */

namespace PhMessage;

use Psr\Http\Message\StreamInterface;

class NoSeekStream implements StreamInterface
{
    use handleStreamDecorator;

    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \RuntimeException('Cannot seek a NoSeekStream');
    }

    public function isSeekable()
    {
        return false;
    }
}

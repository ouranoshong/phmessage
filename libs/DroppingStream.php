<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 10/28/16
 * Time: 2:49 PM
 */

namespace PhMessage;


use Psr\Http\Message\StreamInterface;

class DroppingStream implements StreamInterface
{
    use handleStreamDecorator;

    protected $maxLength;

    public function __construct(StreamInterface $stream, $maxLength)
    {
        $this->stream = $stream;
        $this->maxLength = $maxLength;
    }

    public function write($string)
    {
        $diff = $this->maxLength - $this->stream->getSize();

        if ($diff <= 0) {
            return 0;
        }

        if (strlen($string) < $diff) {
            return $this->stream->write($string);
        }

        return $this->stream->write(substr($string, 0, $diff));

    }

}

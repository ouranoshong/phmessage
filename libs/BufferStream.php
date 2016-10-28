<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 10/28/16
 * Time: 9:46 AM.
 */

namespace PhMessage;

use Psr\Http\Message\StreamInterface;

class BufferStream implements StreamInterface
{
    protected $hwm;
    protected $buffer;

    public function __construct($hwm = 16384)
    {
        $this->hwm = $hwm;
    }

    public function __toString()
    {
        $this->getContents();
    }

    public function close()
    {
        $this->detach();
    }

    public function detach()
    {
        $this->buffer = '';
    }

    public function getSize()
    {
        return strlen($this->buffer);
    }

    public function tell()
    {
        throw new \RuntimeException('Cannot determine the position of a BufferStream');
    }

    public function eof()
    {
        return strlen($this->buffer) === 0;
    }

    public function isSeekable()
    {
        return false;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \RuntimeException('Cannot seek a BufferStream');
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function isWritable()
    {
        return true;
    }

    public function write($string)
    {
        $this->buffer .= $string;

        if (strlen($this->buffer) >= $this->hwm) {
            return false;
        }

        return strlen($string);
    }

    public function isReadable()
    {
        return false;
    }

    public function read($length)
    {
        $currentLength = strlen($this->buffer);

        if ($length >= $currentLength) {
            // No need to slice the buffer because we don't have enough data.
            $result = $this->buffer;
            $this->buffer = '';
        } else {
            // Slice up the result to provide a subset of the buffer.
            $result = substr($this->buffer, 0, $length);
            $this->buffer = substr($this->buffer, $length);
        }

        return $result;
    }

    public function getContents()
    {
        $buffer = $this->buffer;
        $this->buffer = '';

        return $buffer;
    }

    public function getMetadata($key = null)
    {
        if ($key == 'hwm') {
            return $this->hwm;
        }

        return $key ? null : [];
    }
}

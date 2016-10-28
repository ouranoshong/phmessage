<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 10/28/16
 * Time: 10:57 AM
 */

namespace PhMessage;


use Psr\Http\Message\StreamInterface;

class AppendStream implements StreamInterface 
{
    protected $streams = [];

    protected $seekable = true;
    protected $current = 0;
    protected $pos = 0;
    protected $detached = false;


    public function __construct(array $streams = [])
    {
        foreach ($streams as $stream) {
            $this->addStream($stream);
        }
    }

    public function __toString()
    {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (\Exception $e) {
            return '';
        }
    }

    public function addStream(StreamInterface $stream)
    {
        if (!$stream->isReadable()) {
            throw new \InvalidArgumentException('Each stream must be readable');
        }

        // The stream is only seekable if all streams are seekable
        if (!$stream->isSeekable()) {
            $this->seekable = false;
        }

        $this->streams[] = $stream;
    }

    public function close()
    {
        $this->pos = $this->current = 0;

        foreach ($this->streams as $stream) {
            $stream->close();
        }

        $this->streams = [];
    }

    public function detach()
    {
        $this->close();
        $this->detached = true;
    }

    public function getSize()
    {
        $size = 0;

        foreach ($this->streams as $stream) {
            $s = $stream->getSize();
            if ($s === null) {
                return null;
            }
            $size += $s;
        }

        return $size;
    }

    public function tell()
    {
        return $this->pos;
    }

    public function eof()
    {
        return !$this->streams ||
        ($this->current >= count($this->streams) - 1 &&
            $this->streams[$this->current]->eof());
    }

    public function isSeekable()
    {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->seekable) {
            throw new \RuntimeException('This AppendStream is not seekable');
        } elseif ($whence !== SEEK_SET) {
            throw new \RuntimeException('The AppendStream can only seek with SEEK_SET');
        }

        $this->pos = $this->current = 0;

        // Rewind each stream
        foreach ($this->streams as $i => $stream) {
            try {
                $stream->rewind();
            } catch (\Exception $e) {
                throw new \RuntimeException('Unable to seek stream '
                    . $i . ' of the AppendStream', 0, $e);
            }
        }

        // Seek to the actual position by reading from each stream
        while ($this->pos < $offset && !$this->eof()) {
            $result = $this->read(min(8096, $offset - $this->pos));
            if ($result === '') {
                break;
            }
        }
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function isWritable()
    {
        return false;
    }

    public function write($string)
    {
        throw new \RuntimeException('Cannot write to an AppendStream');
    }

    public function isReadable()
    {
        return true;
    }

    public function read($length)
    {

            $buffer = '';
            $total = count($this->streams) - 1;
            $remaining = $length;
            $progressToNext = false;

            while ($remaining > 0) {

                // Progress to the next stream if needed.
                if ($progressToNext || $this->streams[$this->current]->eof()) {
                    $progressToNext = false;
                    if ($this->current === $total) {
                        break;
                    }
                    $this->current++;
                }

                $result = $this->streams[$this->current]->read($remaining);

                // Using a loose comparison here to match on '', false, and null
                if ($result == null) {
                    $progressToNext = true;
                    continue;
                }

                $buffer .= $result;
                $remaining = $length - strlen($buffer);
            }

            $this->pos += strlen($buffer);

            return $buffer;
    }

    public function getContents()
    {
        return copy_to_string($this);
    }

    public function getMetadata($key = null)
    {
        return $key ? null : [];
    }


}

<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 10/27/16
 * Time: 3:48 PM
 */

namespace PhMessage;


use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface 
{
    protected $stream;
    protected $size;
    protected $seekable;
    protected $readable;
    protected $writable;
    protected $uri;
    protected $customMetadata;

    protected static $readWriteHash = [
        'read' => [
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true
        ],
        'write' => [
            'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true
        ]
    ];

    public function __construct($stream, $options = [])
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a resource');
        }

        if (isset($options['size'])) {
            $this->size = $options['size'];
        }

        $this->customMetadata = isset($options['metadata'])
            ? $options['metadata']
            : [];

        $this->stream = $stream;
        $meta = stream_get_meta_data($this->stream);
        $this->seekable = $meta['seekable'];
        $this->readable = isset(self::$readWriteHash['read'][$meta['mode']]);
        $this->writable = isset(self::$readWriteHash['write'][$meta['mode']]);
        $this->uri = $this->getMetadata('uri');

    }

    public function __get($name)
    {
        if ($name == 'stream') {
            throw new \RuntimeException('The stream is detached');
        }

        throw new \BadMethodCallException('No value for ' . $name);
    }

    /**
     * Closes the stream when the destructed
     */
    public function __destruct()
    {
        $this->close();
    }

    public function __toString()
    {
        try {
            $this->seek(0);
            return (string) stream_get_contents($this->stream);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function close()
    {
        if (isset($this->stream)) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }
            $this->detach();
        }
    }

    public function detach()
    {
        if (!isset($this->stream)) {
            return null;
        }

        $result = $this->stream;
        unset($this->stream);
        $this->size = $this->uri = null;
        $this->readable = $this->writable = $this->seekable = false;

        return $result;
    }

    public function getSize()
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if (!isset($this->stream)) {
            return null;
        }

        // Clear the stat cache if the stream has a URI
        if ($this->uri) {
            clearstatcache(true, $this->uri);
        }

        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];
            return $this->size;
        }

        return null;
    }

    public function tell()
    {
        $result = ftell($this->stream);

        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    public function eof()
    {
        return !$this->stream || feof($this->stream);
    }

    public function isSeekable()
    {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable');
        } elseif (fseek($this->stream, $offset, $whence) === -1) {
            throw new \RuntimeException('Unable to seek to stream position '
                . $offset . ' with whence ' . var_export($whence, true));
        }
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function write($string)
    {
        if (!$this->writable) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }

        // We can't know the size after writing anything
        $this->size = null;
        $result = fwrite($this->stream, $string);

        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }

        return $result;

    }

    public function isReadable()
    {
        return $this->readable;
    }

    public function read($length)
    {
        if (!$this->readable) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }

        return fread($this->stream, $length);
    }

    public function getContents()
    {
        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    public function getMetadata($key = null)
    {
        if (!isset($this->stream)) {
            return $key ? null : [];
        } elseif (!$key) {
            return $this->customMetadata + stream_get_meta_data($this->stream);
        } elseif (isset($this->customMetadata[$key])) {
            return $this->customMetadata[$key];
        }

        $meta = stream_get_meta_data($this->stream);

        return isset($meta[$key]) ? $meta[$key] : null;
    }
}

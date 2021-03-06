<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 10/28/16
 * Time: 2:26 PM.
 */

namespace PhMessage;

use Psr\Http\Message\StreamInterface;

/**
 * @property callable _fn___toString
 * @property callable _fn_close
 * @property callable _fn_detach
 * @property callable _fn_rewind
 * @property callable _fn_getSize
 * @property callable _fn_tell
 * @property callable _fn_eof
 * @property callable _fn_isSeekable
 * @property callable _fn_seek
 * @property callable _fn_isWritable
 * @property callable _fn_write
 * @property callable _fn_isReadable
 * @property callable _fn_read
 * @property callable _fn_getContents
 * @property callable _fn_getMetadata
 */
class FnStream implements StreamInterface
{
    protected $methods;

    protected static $slots = [
        '__toString', 'close', 'detach', 'rewind',
        'getSize', 'tell', 'eof', 'isSeekable', 'seek', 'isWritable', 'write',
        'isReadable', 'read', 'getContents', 'getMetadata',
    ];

    public function __construct(array $methods)
    {
        $this->methods = $methods;

        foreach ($methods as $name => $fn) {
            $this->{'_fn_'.$name} = $fn;
        }
    }

    public function __get($name)
    {
        throw new \BadMethodCallException(str_replace('_fn_', '', $name)
            .'() is not implemented in the FnStream');
    }

    public function __destruct()
    {
        if (isset($this->_fn_close)) {
            call_user_func($this->_fn_close);
        }
    }

    public static function decorate(StreamInterface $stream, array $methods)
    {
        foreach (array_diff(self::$slots, array_keys($methods)) as $diff) {
            $methods[$diff] = [$stream, $diff];
        }

        return new self($methods);
    }

    public function __toString()
    {
        return call_user_func($this->_fn___toString);
    }

    public function close()
    {
        return call_user_func($this->_fn_close);
    }

    public function detach()
    {
        return call_user_func($this->_fn_detach);
    }

    public function getSize()
    {
        return call_user_func($this->_fn_getSize);
    }

    public function tell()
    {
        return call_user_func($this->_fn_tell);
    }

    public function eof()
    {
        return call_user_func($this->_fn_eof);
    }

    public function isSeekable()
    {
        return call_user_func($this->_fn_isSeekable);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        return call_user_func($this->_fn_seek, $offset, $whence);
    }

    public function rewind()
    {
        return call_user_func($this->_fn_rewind);
    }

    public function isWritable()
    {
        return call_user_func($this->_fn_isWritable);
    }

    public function write($string)
    {
        return call_user_func($this->_fn_write, $string);
    }

    public function isReadable()
    {
        return call_user_func($this->_fn_isReadable);
    }

    public function read($length)
    {
        return call_user_func($this->_fn_read, $length);
    }

    public function getContents()
    {
        return call_user_func($this->_fn_getContents);
    }

    public function getMetadata($key = null)
    {
        return call_user_func($this->_fn_getMetadata, $key);
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 10/27/16
 * Time: 4:21 PM.
 */

namespace PhMessage;

use Psr\Http\Message\StreamInterface;

class LazyOpenStream implements StreamInterface
{
    use handleStreamDecorator;

    protected $filename;

    protected $mode;

    /**
     * @param string $filename File to lazily open
     * @param string $mode     fopen mode to use when opening the stream
     */
    public function __construct($filename, $mode)
    {
        $this->filename = $filename;
        $this->mode = $mode;
    }

    protected function createStream()
    {
        return stream_for(try_fopen($this->filename, $this->mode));
    }
}

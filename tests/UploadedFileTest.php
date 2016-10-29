<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-29
 * Time: 上午10:42
 */

namespace Tests\PhMessage;


use PhMessage\Stream;
use PhMessage\UploadedFile;
use PHPUnit\Framework\TestCase;

class UploadedFileTest extends TestCase
{
    protected $cleanup;

    public function setup(){
        $this->cleanup = [];
    }

    public function tearDown()
    {
        foreach($this->cleanup as $file) {
            if (is_scalar($file) && file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function invalidStreams()
    {
        return [
            'null' => [null],
            'false' => [false],
            'true' => [true],
            'int'          => [1],
            'float'        => [1.1],
            'array' => [['filename']],
            'object'=> [(object)['filename']]
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidStreams
     * @param $streamOrFile
     */
    public function testRaisesExceptionOnInvalidStreamOrFile($streamOrFile)
    {
        new UploadedFile($streamOrFile, 0, UPLOAD_ERR_OK);
    }

    public function invalidSizes()
    {
        return [
            'null' => [null],
            'float'  => [1.1],
            'array'  => [[1]],
            'object' => [(object) [1]],
        ];
    }

    /**
     * @param $size
     * @dataProvider invalidSizes
     * @expectedException \InvalidArgumentException
     */
    public function testRaisesExceptionOnInvalidSize($size)
    {
        new UploadedFile(fopen('php://temp', 'wb+'), $size, UPLOAD_ERR_OK);
    }

    public function invalidErrorStatuses()
    {
        return [
            'null'     => [null],
            'true'     => [true],
            'false'    => [false],
            'float'    => [1.1],
            'string'   => ['1'],
            'array'    => [[1]],
            'object'   => [(object) [1]],
            'negative' => [-1],
            'too-big'  => [9],
        ];
    }

    /**
     * @param $status
     * @dataProvider invalidErrorStatuses
     * @expectedException \InvalidArgumentException
     */
    public function testRaisesExceptionOnInvalidErrorStatus($status)
    {
        new UploadedFile(fopen('php://temp', 'wb+'), 0, $status);
    }

    public function invalidFilenamesAndMediaTypes()
    {
        return [
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'array'  => [['string']],
            'object' => [(object) ['string']],
        ];
    }

    /**
     * @param $filename
     * @dataProvider invalidFilenamesAndMediaTypes
     * @expectedException \InvalidArgumentException
     */
    public function testRaisesExceptionOnInvalidClientFilename($filename)
    {
        new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, $filename);
    }

    /**
     * @param $mediaType
     * @dataProvider invalidFilenamesAndMediaTypes
     * @expectedException \InvalidArgumentException
     */
    public function testRaisesExceptionOnInvalidClientMediaType($mediaType)
    {
        new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, 'foobar.baz', $mediaType);
    }


    public function testGetStreamReturnsOriginalStreamObject()
    {
        $stream = new Stream(fopen('php://temp', 'r'));
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->assertSame($stream, $upload->getStream());
    }

    public function testGetStreamReturnsWrappedPhpStream()
    {
        $stream = fopen('php://temp', 'wb+');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream()->detach();

        $this->assertSame($stream, $uploadStream);
    }

    public function testGetStreamReturnsStreamForFile()
    {
        $this->cleanup[] = $stream = tempnam(sys_get_temp_dir(), 'stream_file');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream();
        $r = new \ReflectionProperty($uploadStream, 'filename');
        $r->setAccessible(true);

        $this->assertSame($stream, $r->getValue($uploadStream));
    }

    public function testSuccessful()
    {
        $stream = \PhMessage\stream_for('foo bar!');

        $upload = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK, 'filename.txt', 'text/plain');

        $this->assertEquals($stream->getSize(), $upload->getSize());
        $this->assertEquals('filename.txt', $upload->getClientFilename());
        $this->assertEquals('text/plain', $upload->getClientMediaType());

        $this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'successful');
        $upload->moveTo($to);
        $this->assertFileExists($to);
        $this->assertEquals($stream->__toString(), file_get_contents($to));
    }

    public function invalidMovePaths()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'empty'  => [''],
            'array'  => [['filename']],
            'object' => [(object) ['filename']],
        ];
    }

    /**
     * @param $path
     * @dataProvider invalidMovePaths
     * @expectedException \InvalidArgumentException
     */
    public function testMoveRaisesExceptionForInvalidPath($path)
    {
        $stream = \PhMessage\stream_for('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->cleanup[] = $path;

        $upload->moveTo($path);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMoveCannotBeCalledMoreThanOnce()
    {
        $stream = \PhMessage\stream_for('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'diac');
        $upload->moveTo($to);
        $this->assertTrue(file_exists($to));

        $upload->moveTo($to);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCannotRetrieveStreamAfterMove()
    {
        $stream = \PhMessage\stream_for('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'diac');
        $upload->moveTo($to);
        $this->assertFileExists($to);

        $upload->getStream();
    }

    public function nonOkErrorStatus()
    {
        return [
            'UPLOAD_ERR_INI_SIZE'   => [ UPLOAD_ERR_INI_SIZE ],
            'UPLOAD_ERR_FORM_SIZE'  => [ UPLOAD_ERR_FORM_SIZE ],
            'UPLOAD_ERR_PARTIAL'    => [ UPLOAD_ERR_PARTIAL ],
            'UPLOAD_ERR_NO_FILE'    => [ UPLOAD_ERR_NO_FILE ],
            'UPLOAD_ERR_NO_TMP_DIR' => [ UPLOAD_ERR_NO_TMP_DIR ],
            'UPLOAD_ERR_CANT_WRITE' => [ UPLOAD_ERR_CANT_WRITE ],
            'UPLOAD_ERR_EXTENSION'  => [ UPLOAD_ERR_EXTENSION ],
        ];
    }

    /**
     * @param $status
     * @dataProvider nonOkErrorStatus
     */
    public function testConstructorDoesNotRaiseExceptionForInvalidStreamWhenErrorStatusPresent($status)
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $this->assertSame($status, $uploadedFile->getError());
    }

    /**
     * @expectedException \RuntimeException
     * @dataProvider nonOkErrorStatus
     * @param $status
     */
    public function testMoveToRaisesExceptionWhenErrorStatusPresent($status)
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $uploadedFile->moveTo(__DIR__ . '/' . uniqid());
    }

    /**
     * @dataProvider nonOkErrorStatus
     * @param $status
     * @expectedException \RuntimeException
     */
    public function testGetStreamRaisesExceptionWhenErrorStatusPresent($status)
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $uploadedFile->getStream();
    }

    public function testMoveToCreatesStreamIfOnlyAFilenameWasProvided()
    {
        $this->cleanup[] = $from = tempnam(sys_get_temp_dir(), 'copy_from');
        $this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'copy_to');

        copy(__FILE__, $from);

        $uploadedFile = new UploadedFile($from, 100, UPLOAD_ERR_OK, basename($from), 'text/plain');
        $uploadedFile->moveTo($to);

        $this->assertFileEquals(__FILE__, $to);
    }
}

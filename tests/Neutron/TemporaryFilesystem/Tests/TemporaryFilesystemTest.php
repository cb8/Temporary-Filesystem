<?php

namespace Neutron\TemporaryFilesystem\Tests;

use Neutron\TemporaryFilesystem\TemporaryFilesystem;

require_once __DIR__ . '/../../../../src/Neutron/TemporaryFilesystem/TemporaryFilesystem.php';

class TemporaryFilesystemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string $workspace
     */
    private $workspace = null;
    private $filesystem;

    public function setUp()
    {
        parent::setUp();

        $this->workspace = sys_get_temp_dir().DIRECTORY_SEPARATOR.time().rand(0, 1000);
        mkdir($this->workspace, 0777, true);
        $this->workspace = realpath($this->workspace);
        $this->filesystem = new TemporaryFilesystem;
    }

    public function tearDown()
    {
        $this->clean($this->workspace);
    }

    /**
     * @param string $file
     */
    private function clean($file)
    {
        if (is_dir($file) && !is_link($file)) {
            $dir = new \FilesystemIterator($file);
            foreach ($dir as $childFile) {
                $this->clean($childFile);
            }

            rmdir($file);
        } else {
            unlink($file);
        }
    }

    /**
     * @dataProvider getFilesToCreate
     */
    public function testCreateEmptyFile($prefix, $suffix, $extension, $maxTry, $pattern)
    {
        $createDir = $this->workspace . DIRECTORY_SEPARATOR . 'book-dir';
        mkdir($createDir);

        $file = $this->filesystem->createEmptyFile($createDir, $prefix, $suffix, $extension, $maxTry);
        $this->assertTrue(file_exists($file));
        $this->assertEquals($createDir, dirname($file));
        $this->assertEquals(0, filesize($file));
        $this->assertRegExp($pattern, basename($file));
        unlink($file);
    }

    public function getFilesToCreate()
    {
        return array(
            array(null, null, null, 10, '/\w{5}/'),
            array('romain', null, null, 10, '/romain\w{5}/'),
            array(null, 'neutron', null, 10, '/\w{5}neutron/'),
            array(null, null, 'io', 10, '/\w{5}\.io/'),
            array('romain', null, 'io', 10, '/romain\w{5}\.io/'),
            array(null, 'neutron', 'io', 10, '/\w{5}neutron\.io/'),
            array('romain', 'neutron', 'io', 10, '/romain\w{5}neutron\.io/'),
        );
    }

    /**
     * @expectedException Symfony\Component\Filesystem\Exception\IOException
     */
    public function testCreateEmptyFileInvalidDir()
    {
        $createDir = $this->workspace . DIRECTORY_SEPARATOR . 'invalid-book-dir';

        $this->filesystem->createEmptyFile($createDir);
    }

    /**
     * @expectedException Symfony\Component\Filesystem\Exception\IOException
     */
    public function testCreateEmptyFileInvalidDirSecondMethod()
    {
        $createDir = $this->workspace . DIRECTORY_SEPARATOR . 'invalid-book-dir';

        $this->filesystem->createEmptyFile($createDir, 'romain', 'neutron');
    }

    /**
     * @expectedException Symfony\Component\Filesystem\Exception\IOException
     */
    public function testCreateEmptyFileFails()
    {
        $createDir = $this->workspace . DIRECTORY_SEPARATOR . 'book-dir';
        mkdir($createDir);

        $this->filesystem->createEmptyFile($createDir, 'romain', 'neutron', null, 0);
    }

    /**
     * @expectedException Symfony\Component\Filesystem\Exception\IOException
     */
    public function testCreateEmptyFileOnFile()
    {
        $createDir = $this->workspace . DIRECTORY_SEPARATOR . 'book-dir';
        touch($createDir);

        $this->filesystem->createEmptyFile($createDir, null, null, null);
    }

    /**
     * @expectedException Symfony\Component\Filesystem\Exception\IOException
     */
    public function testCreateEmptyFileOnFileSecondMethod()
    {
        $createDir = $this->workspace . DIRECTORY_SEPARATOR . 'book-dir';
        touch($createDir);

        $this->filesystem->createEmptyFile($createDir, 'romain', 'neutron', 'io');
    }

    /**
     * @dataProvider getFilesToCreate
     */
    public function testTemporaryFiles($prefix, $suffix, $extension, $maxTry, $pattern)
    {
        $files = $this->filesystem->createTemporaryFiles(3, $prefix, $suffix, $extension, $maxTry);
        $this->assertEquals(3, count($files));

        foreach ($files as $file) {
            $this->assertTrue(file_exists($file));
            $this->assertEquals(realpath(sys_get_temp_dir()), realpath(dirname($file)));
            $this->assertEquals(0, filesize($file));
            $this->assertRegExp($pattern, basename($file));
        }
    }

    /**
     * @expectedException Symfony\Component\Filesystem\Exception\IOException
     */
    public function testTemporaryFilesFails()
    {
        $this->filesystem->createTemporaryFiles(3, 'prefix', 'suffix', null, 0);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTemporaryFilesInvalidQuantity()
    {
        $this->filesystem->createTemporaryFiles(0);
    }
}

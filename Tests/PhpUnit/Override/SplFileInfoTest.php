<?php
/**
 * MIT License
 *
 * Copyright (c) 2017, Pentagonal
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace Pentagonal\Modular\Test\PhpUnit\Override;

use Pentagonal\Modular\FileType;
use Pentagonal\Modular\Override\SplFileInfo;
use PHPUnit\Framework\TestCase;

/**
 * Class SplFileInfoTest
 * @package Pentagonal\Modular\Test\PhpUnit\Override
 */
class SplFileInfoTest extends TestCase
{
    public function testBaseFileSplFileInfo()
    {
        $spl = new SplFileInfo(__FILE__);
        $this->assertTrue(
            $spl->isFile()
        );
        $this->assertEquals(
            $spl->getRealPath(),
            __FILE__
        );

        $this->assertEquals(
            $spl->getType(),
            FileType::TYPE_FILE
        );

        $spl2 = new SplFileInfo(__DIR__);
        $this->assertTrue(
            $spl2->isDir()
        );

        $this->assertEquals(
            $spl2->getRealPath(),
            __DIR__
        );

        $this->assertEquals(
            $spl2->getType(),
            FileType::TYPE_DIR
        );
    }

    public function testGetReturnedContentFromFile()
    {
        $spl = new SplFileInfo(__FILE__);
        $fileContent = file_get_contents(__FILE__);

        $this->assertEquals(
            $spl->getContents(),
            $fileContent
        );
    }

    public function testBufferGetContentFromFile()
    {
        $spl = new SplFileInfo(__FILE__);
        ob_start();
        $spl->getContentOutputBuffer();
        $buffer = ob_get_clean();
        $this->assertEquals(
            $spl->getContents(),
            $buffer
        );
    }

    public function testUnknownTypeOrInvalidPath()
    {
        $spl = new SplFileInfo(__DIR__ . '/invalid-file-not-exists');
        $this->assertEquals(
            $spl->getType(),
            FileType::TYPE_UNKNOWN
        );
    }

    public function testExceptionOnSplArgumentAndLogic()
    {
        $spl = new SplFileInfo(__DIR__);
        try {
            // directory is not a valid
            $spl->getContents();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                \RuntimeException::class,
                $e
            );
        }

        $file = __DIR__ .'/../Assets/TestForUnReadableFile.file';
        $originalPermission = fileperms($file);

        // change permission to don't allow to read
        chmod($file, 1111);
        $spl = new SplFileInfo($file);
        try {
            // directory is not a valid
            $spl->getContents();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                \RuntimeException::class,
                $e
            );
        }
        // take back to original
        chmod($file, $originalPermission);
    }
}

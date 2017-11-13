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

namespace Pentagonal\Modular\PhpUnit;

use Pentagonal\ArrayStore\StorageArray;
use Pentagonal\Modular\FileTree;
use Pentagonal\Modular\Override\SplFileInfo;
use PHPUnit\Framework\TestCase;

/**
 * Class FileTreeTest
 * @package Pentagonal\Modular\PhpUnit
 */
class FileTreeTest extends TestCase
{
    public function testThrowable()
    {
        try {
            new FileTree(['array is invalid']);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                \InvalidArgumentException::class,
                $e
            );
        }
    }
    public function testGetSplMethod()
    {
        $fileTreeString = new FileTree(__DIR__);
        $fileTreeSpl    = new FileTree(new SplFileInfo(__DIR__));
        $this->assertInstanceOf(
            SplFileInfo::class,
            $fileTreeString->getSpl()
        );
        $this->assertInstanceOf(
            SplFileInfo::class,
            $fileTreeSpl->getSpl()
        );
    }

    public function testMagicMethod()
    {
        $fileTree = new FileTree(__DIR__);

        $this->assertInstanceOf(
            StorageArray::class,
            $fileTree->all()
        );
        // test cached
        $this->assertInstanceOf(
            StorageArray::class,
            $fileTree->all()
        );

        // test get inner
        $this->assertInstanceOf(
            StorageArray::class,
            $fileTree->get()
        );

        $this->assertTrue(
            isset($fileTree['Override'])
        );

        $this->assertFalse(
            isset($fileTree['notExists'])
        );

        $this->assertInstanceOf(
            StorageArray::class,
            $fileTree['Override']
        );

        // ignored offsetSet
        $fileTree['Override'] = 'Ignored';
        $this->assertInstanceOf(
            StorageArray::class,
            $fileTree['Override']
        );
        // ignored offsetUnset
        unset($fileTree['Override']);
        $this->assertInstanceOf(
            StorageArray::class,
            $fileTree['Override']
        );

        // magic method __call(string $arg, array $args)
        $this->assertEquals(
            $fileTree->getSpl()->getRealPath(),
            $fileTree->getRealPath()
        );
        // magic method __toString()
        $this->assertEquals(
            $fileTree->getRealPath(),
            (string) $fileTree
        );
    }
}

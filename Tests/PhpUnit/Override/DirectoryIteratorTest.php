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

namespace Pentagonal\Modular\PhpUnit\Override;

use Pentagonal\Modular\Override\DirectoryIterator;
use Pentagonal\Modular\Override\SplFileInfo;
use PHPUnit\Framework\TestCase;

/**
 * Class DirectoryIteratorTest
 * @package Pentagonal\Modular\PhpUnit\Override
 */
class DirectoryIteratorTest extends TestCase
{
    public function testIteratorLoop()
    {
        foreach (new DirectoryIterator(__DIR__) as $iterator) {
            $this->assertInstanceOf(
                \DirectoryIterator::class,
                $iterator
            );
            if ($iterator->isDot()) {
                continue;
            }
            $this->assertStringStartsWith(
                __DIR__,
                $iterator->getRealPath()
            );
        }
    }

    public function testInstanceOfGetFileInfo()
    {
        $iterator = new DirectoryIterator(__DIR__);
        $this->assertInstanceOf(
            SplFileInfo::class,
            $iterator->getFileInfo()
        );
        $this->assertInstanceOf(
            SplFileInfo::class,
            $iterator->getFileInfo(SplFileInfo::class)
        );
        $this->assertInstanceOf(
            SplFileInfo::class,
            $iterator->getPathInfo()
        );
        $this->assertInstanceOf(
            SplFileInfo::class,
            $iterator->getPathInfo(SplFileInfo::class)
        );
    }

    public function testPathInfo()
    {
        $iterator_1 = new DirectoryIterator(__DIR__);
        $iterator_2 = new \DirectoryIterator(__DIR__);
        $this->assertEquals(
            $iterator_1->getPathInfo()->getRealPath(),
            $iterator_2->getPathInfo()->getRealPath()
        );
        $this->assertTrue(
            $iterator_1->exist()
        );
    }
}

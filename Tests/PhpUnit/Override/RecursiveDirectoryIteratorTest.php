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

use Pentagonal\Modular\Override\RecursiveDirectoryIterator;
use Pentagonal\Modular\Override\SplFileInfo;
use PHPUnit\Framework\TestCase;

/**
 * Class DirectoryIteratorTest
 * @package Pentagonal\Modular\Test\PhpUnit\Override
 */
class RecursiveDirectoryIteratorTest extends TestCase
{
    public function testIteratorLoop()
    {
        $recursive = new RecursiveDirectoryIterator(__DIR__);
        foreach ($recursive as $iterator) {
            $this->assertInstanceOf(
                \SplFileInfo::class,
                $iterator
            );
            $this->assertInstanceOf(
                SplFileInfo::class,
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
        // after loop end wit will be thrown errpr
        try {
            $recursive->current();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                \RuntimeException::class,
                $e
            );
        }
        // rewind
        $recursive->rewind();
        $this->assertInstanceOf(
            SplFileInfo::class,
            $recursive->current()
        );
    }
}

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

namespace Pentagonal\Modular\Test\PhpUnit;

use Pentagonal\Modular\Exceptions\ModuleNotFoundException;
use Pentagonal\Modular\Exceptions\ModulePathException;
use Pentagonal\Modular\Module;
use Pentagonal\Modular\Parser;
use Pentagonal\Modular\Reader;
use PHPUnit\Framework\TestCase;

/**
 * Class ReaderTest
 * @package Pentagonal\Modular\Test\PhpUnit
 */
class ReaderTest extends TestCase
{
    public function testConstructorAndMethods()
    {
        $reader = new Reader(
            __DIR__ .'/../ModulesExampleDirectory'
        );
        $this->assertFalse(
            $reader->isConfigured()
        );
        $this->assertInstanceOf(
            Reader::class,
            $reader->configure()
        );
        $this->assertTrue(
            $reader->isConfigured()
        );
        $this->assertInstanceOf(
            Reader::class,
            $reader->configure()
        );
        $this->assertTrue(
            $reader->isConfigured()
        );
        $this->assertNotEmpty(
            $reader->getSelectors()
        );
        $invalidModules = $reader->getInvalidModules();
        $this->assertNotEmpty(
            $invalidModules
        );
        $this->assertInstanceOf(
            Parser::class,
            reset($invalidModules)
        );
        $validModules = $reader->getValidModules();
        $this->assertNotEmpty(
            $validModules
        );
        $validModule = reset($validModules);
        $validModuleKey = key($validModules);
        $this->assertInstanceOf(
            Module::class,
            $validModule
        );
        $this->assertInstanceOf(
            Module::class,
            $reader->getModule($validModuleKey)
        );
        $this->assertInstanceOf(
            Module::class,
            $reader->getModuleBySelector(
                $validModule->finalGetInfo()[$validModule::KEY_SELECTOR]
            )
        );

        // test exceptions
    }

    public function testException()
    {
        $file = __DIR__ .'/Assets/TestForUnReadableFile.file';
        try {
            new Reader($file);
        } catch (\Throwable $e) {
            /**
             * @var ModulePathException $e
             */
            $this->assertInstanceOf(
                ModulePathException::class,
                $e
            );
            $this->assertEquals(
                $e->getPath(),
                $file
            );
        }
        $reader = new Reader(
            __DIR__ .'/../ModulesExampleDirectory'
        );

        try {
            $selector = 'Invalid Module Name';
            $reader->getModule($selector);
        } catch (\Throwable $e) {
            /**
             * @var ModuleNotFoundException $e
             */
            $this->assertInstanceOf(
                ModuleNotFoundException::class,
                $e
            );
            $this->assertEquals(
                $selector,
                $e->getModuleName()
            );
        }
        try {
            $selector = 'invalid_selector';
            $reader->getModuleBySelector($selector);
        } catch (\Throwable $e) {
            /**
             * @var ModuleNotFoundException $e
             */
            $this->assertInstanceOf(
                ModuleNotFoundException::class,
                $e
            );
            $this->assertEquals(
                $selector,
                $e->getModuleName()
            );
        }
    }
}

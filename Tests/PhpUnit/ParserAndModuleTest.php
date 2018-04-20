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

use Pentagonal\ArrayStore\StorageArray;
use Pentagonal\Modular\Exceptions\ModuleException;
use Pentagonal\Modular\Exceptions\ModuleNotFoundException;
use Pentagonal\Modular\Exceptions\ModulePathException;
use Pentagonal\Modular\Module;
use Pentagonal\Modular\Override\DirectoryIterator;
use Pentagonal\Modular\Override\SplFileInfo;
use Pentagonal\Modular\Parser;
use Pentagonal\Modular\Test\ModuleExampleDirectory\ModuleValid;
use Pentagonal\Modular\Test\ModuleExampleDirectory\ValidModuleFile;
use PHPUnit\Framework\TestCase;

/**
 * Class ParserAndModuleTest
 * @package Pentagonal\Modular\Test\PhpUnit
 */
class ParserAndModuleTest extends TestCase
{
    public function testInstanceException()
    {
        try {
            foreach (new DirectoryIterator(__DIR__ .'/../ModulesExampleDirectory/InvalidModule') as $iterator) {
                Parser::create($iterator);
            }
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                ModulePathException::class,
                $e
            );
        }
    }

    public function testValidInstance()
    {
        $diInvalid = new SplFileInfo(__DIR__ .'/../ModulesExampleDirectory/InvalidModule');
        $parser = Parser::create($diInvalid);
        $this->assertInstanceOf(
            SplFileInfo::class,
            $parser->getSpl()
        );
        $this->assertFalse(
            $parser->isHasParsed()
        );
        $this->assertEquals(
            $parser->getSelector(),
            sha1($parser->getSpl()->getBasename())
        );

        $this->assertEquals(
            'php',
            $parser->getFileExtension()
        );

        $this->assertInstanceOf(
            Parser::class,
            $parser->parse()
        );

        $this->assertTrue(
            $parser->isHasParsed()
        );
        $this->assertFalse(
            $parser->isValid()
        );
        $this->assertInstanceOf(
            ModuleNotFoundException::class,
            $parser->getException()
        );

        try {
            $parser->getModuleInstance();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                ModuleNotFoundException::class,
                $e
            );
            $this->assertEquals(
                $e,
                $parser->getException()
            );
        }

        $this->assertNull(
            $parser->getModuleClassName()
        );
        $this->assertNull(
            $parser->getModuleParentClass()
        );
        $this->assertInstanceOf(
            StorageArray::class,
            $parser->getCheckedFilesMessage()
        );
        $this->assertEmpty(
            $parser->getCheckedFilesMessage()->toArray()
        );
    }

    public function testInvalidModule()
    {
        $diInvalid = new SplFileInfo(__DIR__ .'/../ModulesExampleDirectory/InvalidModuleExample');
        $parser = Parser::create($diInvalid);
        $this->assertInstanceOf(
            Parser::class,
            $parser->parse()
        );

        $this->assertInstanceOf(
            ModuleException::class,
            $parser->getException()
        );

        $this->assertNotEmpty(
            $parser->getCheckedFilesMessage()->toArray()
        );
        // clear
        $parser->clearMessage();
        $this->assertEmpty(
            $parser->getCheckedFilesMessage()->toArray()
        );

        try {
            $parser->newConstruct();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                ModuleException::class,
                $e
            );
        }

        $diInvalid = new SplFileInfo(__DIR__ .'/../ModulesExampleDirectory/InvalidModule2');
        $parser = Parser::create($diInvalid);
        $parser->parse();
        try {
            $parser->newConstruct();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                ModuleException::class,
                $e
            );
        }
        try {
            Parser::create(
                new \SplFileInfo(__DIR__ .'/../ModulesExampleDirectory/ValidModule/ModuleValid.php')
            );
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                ModulePathException::class,
                $e
            );
        }
        foreach (new DirectoryIterator(__DIR__ .'/../ModulesExampleDirectory/..') as $i) {
            if ($i->getBasename() === '..') {
                try {
                    Parser::create($i->getFileInfo());
                } catch (\Throwable $e) {
                    $this->assertInstanceOf(
                        ModulePathException::class,
                        $e
                    );
                }
                break;
            }
        }
    }

    public function testValidModule()
    {
        $diValid = new SplFileInfo(__DIR__ .'/../ModulesExampleDirectory/ValidModule');
        $parser = Parser::create($diValid);
        $this->assertInstanceOf(
            Parser::class,
            $parser->parse()
        );
        try {
            $this->assertNull(
                $parser->getException()
            );
        } catch (\Exception $e) {
            // pass
        }
    }

    public function testModule()
    {
        $diValid = new SplFileInfo(__DIR__ .'/../ModulesExampleDirectory/ValidModule');
        $parser = Parser::create($diValid)->parse();
        try {
            $module = $parser->newConstruct();
            $this->assertInstanceOf(
                Module::class,
                $module
            );
        } catch (\Throwable $e) {
            // pass
        }
        $this->assertArrayHasKey(
            Module::KEY_NAME,
            $module->finalGetInfo()
        );
        $this->assertArrayHasKey(
            Module::KEY_DESCRIPTION,
            $module->finalGetInfo()
        );
        $name        = $module->getName();
        $description = $module->getDescription();
        $baseClassName = basename($diValid->getRealPath());
        $this->assertNotEmpty($name);
        $this->assertEquals(
            $baseClassName,
            $name
        );
        $this->assertEmpty($description);
        $this->assertFalse(
            $module->isHasInit()
        );
        $this->assertInstanceOf(
            Module::class,
            $module->finalInitOnce()
        );
        $this->assertTrue(
            $module->isHasInit()
        );
        $this->assertNotEquals(
            $name,
            $module->getName()
        );
        $this->assertNotEquals(
            $description,
            $module->getDescription()
        );
        $module->finalInitOnce();
        try {
            $module->__construct($parser);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                \RuntimeException::class,
                $e
            );
        }
    }

    public function testInvalidSplInstanceParserModule()
    {
        $diInvalid = new SplFileInfo(__DIR__ .'/../ModulesExampleDirectory/InvalidModuleExample');
        require_once __DIR__ .'/../ModulesExampleDirectory/ValidModule/ModuleValid.php';
        $parser = Parser::create($diInvalid);
        try {
            new ModuleValid($parser);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                \InvalidArgumentException::class,
                $e
            );
        }
    }
}

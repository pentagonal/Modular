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

namespace Pentagonal\Modular;

use Pentagonal\ArrayStore\StorageArray;
use Pentagonal\Modular\Override\DirectoryIterator;
use Pentagonal\Modular\Override\SplFileInfo;

/**
 * Class Parser
 * @package Pentagonal\Modular
 *
 * Reader that parse base per module
 *
 * @todo completion
 */
class Parser
{
    /**
     * @var SplFileInfo
     */
    protected $splFileInfo;

    /**
     * @var bool
     */
    protected $hasParsed = false;

    /**
     * Unique selector of module
     *
     * @var string
     */
    protected $selector;

    /**
     * Class Name that extends to @uses Module
     *
     * @var string
     */
    protected $className;

    /**
     * The full path file loaded
     *
     * @var string
     */
    protected $fileIndexed = '';

    /**
     * @var StorageArray
     */
    protected $checkedFiles;

    /**
     * @var StorageArray|SplFileInfo
     */
    protected $fileLists;

    /**
     * Parser constructor.
     * @final
     * @access internal|private
     */
    final private function __construct()
    {
        $this->fileIndexed  = '';
        $this->className    = '';
        $this->selector     = '';
        $this->hasParsed    = false;
        $this->splFileInfo  = null;
        $this->checkedFiles = new StorageArray();
        $this->fileLists    = new StorageArray();
        // pass
    }

    /**
     * Create instance of Parser
     *
     * @param DirectoryIterator $di
     *
     * @final
     * @return Parser
     */
    final public static function create(DirectoryIterator $di) : Parser
    {
        $object              = new static();
        if (! $di->isDir() || $di->isDot()) {
            throw new \RuntimeException(
                sprintf(
                    '%1$s is not a valid directory. Path type is %2$s',
                    $di->getPathname(),
                    $di->getType()
                ),
                E_NOTICE,
                $di->getRealPath() ?: $di->getPathname()
            );
        }

        $object->splFileInfo = $di->getFileInfo();

        return $object;
    }

    /**
     * Get SplFileInfo
     *
     * @return SplFileInfo
     */
    public function getSplFileInfo() : SplFileInfo
    {
        return $this->splFileInfo;
    }

    /**
     * Check if has been parse process
     *
     * @return bool
     */
    public function isHasParsed() : bool
    {
        return $this->hasParsed;
    }

    /**
     * Get Unique Module Selector
     *
     * @return string
     */
    public function getSelector() : string
    {
        return $this->parse()->selector;
    }

    /**
     * @return string
     */
    public function getClassName() : string
    {
        return $this->parse()->className;
    }

    /**
     * @return string
     */
    public function getFileIndexed() : string
    {
        return $this->parse()->fileIndexed;
    }

    /**
     * Check if module valid
     *
     * @return bool
     */
    public function isValid() : bool
    {
        try {
            $className = $this->parse()->getClassName();
            return $className
                   && class_exists($className)
                   && is_subclass_of($className, Module::class);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Doing parsing process
     *
     * @return Parser
     */
    public function parse() : Parser
    {
        if ($this->hasParsed === true) {
            return $this;
        }

        $this->hasParsed = true;
        foreach (new DirectoryIterator($this->getSplFileInfo()->getPathname()) as $value) {
            if ($value->isDot()) {
                continue;
            }

            $name = $value->getFilename();
            $this->fileLists[$name] = $value->getFileInfo();

            /*
             * if file size less than 74 chars so it will be ignored
             * below is example very minimum requirements
             * 74 characters:
             *
             * <?php class A extends M{function initialize(){}function getInfo():array{}}
             */
            if ($value->getSize() < 74
                // file extension must be php
                || $value->getExtension() !== 'php'
                // do not allow hidden files
                || strpos($name, '.') === 0
                // if can not read do not process
                || ! $value->isReadable()
            ) {
                continue;
            }

            if ($this->validateFileModule($value) === true) {
                break;
            }
        }

        // @todo logic parsing process
        return $this;
    }

    protected function validateFileModule(\SplFileInfo $spl) : bool
    {
        $spl = $spl->getFileInfo();
        $this->checkedFiles[$spl->getFilename()] = microtime(true);
        // @todo parsing process
        return false;
    }
}

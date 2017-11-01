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
    protected $splFilePath;

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
    protected $fileIndexed;

    /**
     * @var \Throwable
     */
    protected $exceptions;

    /**
     * Parser constructor.
     * @final
     * @access private
     */
    final private function __construct()
    {
        // pass
    }

    /**
     * Create instance of Parser
     * @param string $directoryPathOfModule
     *
     * @return Parser
     */
    public static function create(string $directoryPathOfModule) : Parser
    {
        $object = new static();
        $object->splFilePath = new SplFileInfo($directoryPathOfModule);
        if (! $object->splFilePath->isDir()) {
            throw new \RuntimeException(
                sprintf(
                    '%s is not a valid directory',
                    $object->splFilePath->getPathname()
                ),
                E_NOTICE
            );
        }

        return $object;
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

        // @todo logic parsing process
        return $this;
    }

    /**
     * Check if module valid
     *
     * @return bool
     */
    public function isValid() : bool
    {
        return (bool) ! $this->parse()->exceptions;
    }

    /**
     * Get SplFileInfo
     *
     * @return SplFileInfo
     */
    public function getSplFilePath() : SplFileInfo
    {
        return $this->splFilePath;
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
     * Get Error Exceptions
     *
     * @return \Throwable|null
     */
    public function getExceptions()
    {
        return $this->parse()->exceptions;
    }
}

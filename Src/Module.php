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
use Pentagonal\Modular\Exceptions\ModuleException;

/**
 * Class Module
 * @package Pentagonal\Modular
 *
 * base Module abstraction layer to implement as self Module
 *
 * @uses Module::finalInitOnce() to full load the modules
 */
abstract class Module
{
    const KEY_NAME        = 'name';
    const KEY_FILE        = 'file';
    const KEY_SELECTOR    = 'selector';
    const KEY_DESCRIPTION = 'description';

    /**
     * @var bool
     */
    private $reservedHasCallInit = false;

    /**
     * Module name
     *
     * @var string
     */
    protected $name          = null;

    /**
     * Module Description
     *
     * @var string
     */
    protected $description   = null;

    /**
     * Store constructor arguments
     *
     * @var StorageArray
     * @access private
     */
    private $reservedConstructorArguments;

    /**
     * @var Parser
     */
    private $reservedConstructorParser;

    /**
     * @var bool
     */
    private $reservedConstructorIsCalled = false;

    /**
     * @var array
     */
    private $reservedBaseInfo = [];

    /**
     * Module constructor.
     *
     * @param Parser $parser the module selector
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    final public function __construct(Parser $parser)
    {
        if ($this->reservedConstructorIsCalled) {
            throw new \RuntimeException(
                sprintf(
                    '%s constructor only allow called once',
                    get_class($this)
                )
            );
        }

        $reflection = new \ReflectionClass($this);
        $fileIndexed = $parser->getSplFileIndexed();
        if (!$fileIndexed || $fileIndexed->getRealPath() !== $reflection->getFileName()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'object %s must be pointing to class %s file',
                    get_class($parser),
                    __CLASS__
                )
            );
        }

        $this->reservedConstructorIsCalled = true;
        $this->reservedConstructorParser   = $parser;
        $args                              = func_get_args();
        array_shift($args);
        $this->reservedConstructorArguments = new StorageArray($args);
        if (!is_string($this->name)) {
            $this->name = $parser->getBasename();
        }

        if (!is_string($this->description)) {
            $this->description = '';
        }

        $this->reservedBaseInfo = [
            self::KEY_FILE          => $parser->getRealPath(),
            self::KEY_SELECTOR      => $parser->getSelector(),
            self::KEY_NAME          => $this->name,
            self::KEY_DESCRIPTION   => $this->description
        ];
    }

    /**
     * @return StorageArray
     * @access protected
     */
    final protected function finalGetConstructorArguments() : StorageArray
    {
        return $this->reservedConstructorArguments;
    }

    /**
     * Get current Module Parser
     *
     * @return Parser
     */
    final protected function finalGetConstructorParser() : Parser
    {
        return $this->reservedConstructorParser;
    }

    /**
     * Get Base Info
     * the value can be overide via
     *
     * @return array
     */
    final public function finalGetInfo() : array
    {
        return $this->reservedBaseInfo;
    }

    /**
     * Helper to call @uses Module::initialize() once
     *
     * @return Module
     */
    final public function finalInitOnce() : Module
    {
        if ($this->reservedHasCallInit) {
            return $this;
        }

        $this->reservedHasCallInit = true;
        $this->initialize();

        return $this;
    }

    /**
     * Initialize Module
     * Better to add has init to prevent multiple call init
     *
     * @return mixed
     */
    abstract protected function initialize();

    /**
     * Module Info
     *
     * @return array
     */
    abstract public function getInfo() : array;

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Get module Description
     *
     * @return string
     */
    public function getDescription() : string
    {
        return $this->description;
    }
}

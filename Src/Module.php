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
 */
abstract class Module
{
    /**
     * Store constructor arguments
     *
     * @var StorageArray
     * @access private
     */
    private $reserved_construct_arguments;

    /**
     * @var Parser
     */
    private $reserved_construct_parser;

    /**
     * @var bool
     */
    private $reserved_constructor_is_called = false;

    /**
     * Module constructor.
     *
     * @param Parser $parser the module selector
     */
    final public function __construct(Parser $parser)
    {
        if ($this->reserved_constructor_is_called) {
            throw new ModuleException(
                sprintf(
                    '%s constructor only allow called once',
                    get_class($this)
                )
            );
        }

        $reflection = new \ReflectionClass($this);
        if ($parser->getSplFileIndexed()->getRealPath() !== $reflection->getFileName()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'object %s must be pointing to class %s file',
                    get_class($parser),
                    __CLASS__
                )
            );
        }

        $this->reserved_constructor_is_called = true;
        $this->reserved_construct_parser = $parser;
        $args                            = func_get_args();
        array_shift($args);
        $this->reserved_construct_arguments = new StorageArray($args);
        if (!is_string($this->name)) {
            $this->name = $parser->getBasename();
        }
    }

    /**
     * @return StorageArray
     * @access protected
     */
    final protected function getConstructorArguments() : StorageArray
    {
        return $this->reserved_construct_arguments;
    }

    /**
     * Get current Module Parser
     *
     * @return Parser
     */
    final protected function getConstructorParser() : Parser
    {
        return $this->reserved_construct_parser;
    }

    /**
     * Get Selector Module
     *
     * @return string
     */
    final public function getModuleSelector() : string
    {
        return $this->getConstructorParser()->getSelector();
    }

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
    protected $description   = '';

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

    /**
     * Initialize Module
     *
     * @return mixed
     */
    abstract public function initialize();

    /**
     * Module Info
     *
     * @return array
     */
    abstract public function getInfo() : array;
}

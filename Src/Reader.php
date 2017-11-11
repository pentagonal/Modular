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
use Pentagonal\ArrayStore\StorageArrayObject;
use Pentagonal\ArrayStore\StorageInterface;
use Pentagonal\Modular\Exceptions\ModuleNotFoundException;
use Pentagonal\Modular\Exceptions\ModulePathException;
use Pentagonal\Modular\Interfaces\ParseGetterInterface;
use Pentagonal\Modular\Override\DirectoryIterator;
use Pentagonal\Modular\Override\SplFileInfo;

/**
 * Class Reader
 * @package Pentagonal\Modular
 */
class Reader
{
    /**
     * @var SplFileInfo
     */
    protected $spl;

    /**
     * @var ParserGetter
     */
    protected $parserGetter;

    /**
     * @var StorageInterface
     */
    protected $invalidDirectories;

    /**
     * @var StorageInterface|Parser[]
     */
    private $listsModules;

    /**
     * @var string[]|StorageArrayObject
     */
    private $invalidModules;

    /**
     * @var \closure[]|Module[]
     */
    private $validModules = [];

    /**
     * @var string[]|StorageArray
     */
    private $selectors;

    /**
     * @var bool
     */
    private $processed = false;

    /**
     * Reader constructor.
     *
     * @param string $directory
     * @param ParseGetterInterface|null $parserGetter
     */
    public function __construct(
        string $directory,
        ParseGetterInterface $parserGetter = null
    ) {
        $directory = realpath($directory) ?: $directory;
        if (!is_dir($directory)) {
            throw new ModulePathException(
                sprintf(
                    'Path %s is not a directory',
                    $directory
                ),
                E_WARNING,
                $directory
            );
        }

        $this->spl          = new SplFileInfo($directory);
        $this->parserGetter = $parserGetter?: new ParserGetter();
        $this->resetProperties();
    }

    /**
     * Reset Object Properties
     */
    private function resetProperties()
    {
        $this->processed          = false;
        $this->invalidModules     = new StorageArrayObject();
        $this->validModules       = [];
        $this->selectors          = new StorageArray();
        $this->listsModules       = new StorageArrayObject();
        $this->invalidDirectories = new StorageArray();
    }

    /**
     * Begin process
     *
     * @return Reader
     */
    final public function process() : Reader
    {
        if ($this->processed) {
            return $this;
        }

        // call reset properties
        $this->resetProperties();
        $this->processed = true;
        /**
         * @var DirectoryIterator $directoryIterator
         */
        foreach (new DirectoryIterator($this->spl->getRealPath()) as $key => $directoryIterator) {
            if ($directoryIterator->isDot()) {
                continue;
            }

            $name = $directoryIterator->getBasename();
            if ($directoryIterator->getType() === FileType::TYPE_DIR) {
                $parser = $this
                    ->parserGetter
                    ->getParserInstance($directoryIterator)
                    ->parse();
                $this->listsModules[$name] =& $parser;
                $this->selectors[$name] = $parser->getSelector();
                if ($parser->isValid()) {
                    $this->validModules[$name] = (function ($moduleName) {
                        /**
                         * @var Parser $this
                         */
                        return $this->getParserFor($moduleName)->newInit();
                    });

                    continue;
                }

                $this->invalidModules[] = $name;
                continue;
            }

            $this->invalidDirectories[$name] = $directoryIterator->getFileInfo(SplFileInfo::class);
        }

        return $this;
    }

    /**
     * @return bool
     */
    final public function isProcessed() : bool
    {
        return $this->processed;
    }

    /**
     * @param string $name
     *
     * @return mixed|Parser
     */
    public function getParserFor(string $name)
    {
        return $this->process()->listsModules[$name];
    }

    /**
     * @param string $name
     *
     * @return mixed|null|string
     */
    public function getSelectorFor(string $name)
    {
        return $this->selectors[$name];
    }

    /**
     * @param string $name
     *
     * @return false|int|mixed|string
     */
    public function getModuleNameFor(string $name)
    {
        return $this->selectors->indexOf($name);
    }

    /**
     * @return string[]
     */
    public function getSelectors(): array
    {
        return $this->selectors;
    }

    /**
     * @return StorageArrayObject|string[]
     */
    public function getInvalidModules() : StorageArrayObject
    {
        return $this->invalidModules;
    }

    /**
     * @return \closure[]|Module[]
     */
    public function getValidModules() : array
    {
        foreach ($this->process()->validModules as $key => $v) {
            if ($v instanceof \Closure) {
                $this->validModules[$key] = $v->call($this, $key);
            }
        }

        return $this->validModules;
    }

    /**
     * @param string $name
     *
     * @return Module
     * @throws ModuleNotFoundException
     */
    public function getModuleFromName(string $name) : Module
    {
        $module =& $this->process()->validModules[$name];
        if (!$module) {
            throw new ModuleNotFoundException(
                sprintf(
                    'Module for %s has not found',
                    $name
                )
            );
        }

        if ($module instanceof \Closure) {
            $this->validModules[$name] = $module->call($this);
        }

        return $this->validModules[$name];
    }

    /**
     * @param string $selector
     *
     * @return Module
     * @throws ModuleNotFoundException
     */
    public function getModuleFromSelector(string $selector) : Module
    {
        $moduleName = $this->process()->getModuleNameFor($selector);
        if (!$moduleName) {
            throw new ModuleNotFoundException(
                sprintf(
                    'Module for %s has not found',
                    $selector
                )
            );
        }

        return $this->getModuleFromName($moduleName);
    }

    /**
     * @param string $selectorOrName
     *
     * @return Module
     * @throws ModuleNotFoundException
     */
    public function get(string $selectorOrName) : Module
    {
        if (isset($this->process()->validModules[$selectorOrName])) {
            return $this->getModuleFromName($selectorOrName);
        }

        return $this->getModuleFromSelector($selectorOrName);
    }
}

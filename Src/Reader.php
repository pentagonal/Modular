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
     * @var FileTree[]
     */
    protected $invalidPaths;

    /**
     * @var Parser[]
     */
    private $invalidModules;

    /**
     * @var \closure[]|Module[]
     */
    private $validModules = [];

    /**
     * @var bool
     */
    private $configured = false;

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
        $this->configured     = false;
        $this->validModules   = [];
        $this->invalidModules = [];
        $this->invalidPaths   = [];
    }

    /**
     * Begin process
     *
     * @return Reader
     */
    final public function configure() : Reader
    {
        if ($this->configured) {
            return $this;
        }

        // call reset properties
        $this->resetProperties();
        $this->configured = true;

        /**
         * @var DirectoryIterator $directoryIterator
         */
        foreach (new DirectoryIterator($this->spl->getRealPath()) as $directoryIterator) {
            $this->reConfigureModule($directoryIterator);
        }

        return $this;
    }

    /**
     * Reconfigure
     *
     * @param DirectoryIterator $directoryIterator
     */
    private function reConfigureModule(
        DirectoryIterator $directoryIterator
    ) {
        $name = $directoryIterator->getBasename();
        if (($isDot = $directoryIterator->isDot())
            || $directoryIterator->getType() !== FileType::TYPE_DIR
        ) {
            ! $isDot && $this->invalidPaths[$name] = new FileTree($directoryIterator);
            return;
        }

        $parser = $this->parserGetter->getParserInstance($directoryIterator);
        if ($parser->isValid()) {
            $this->validModules[$name] =& $parser->getModuleInstance();
            return;
        }

        $this->invalidModules[$name] = $parser;
    }

    /**
     * @return bool
     */
    final public function isConfigured() : bool
    {
        return $this->configured;
    }

    /**
     * Get Invalid Modules returning instance @uses Parser
     *
     * @return Parser[]
     */
    public function getInvalidModules()
    {
        return $this->invalidModules;
    }

    /**
     * @return Module[]
     */
    public function getValidModules() : array
    {
        return $this->validModules;
    }
}

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
use Pentagonal\Modular\Exceptions\ModulePathException;
use Pentagonal\Modular\Interfaces\ParseGetterInterface;
use Pentagonal\Modular\Override\DirectoryIterator;
use Pentagonal\Modular\Override\SplFileInfo;

/**
 * Class Reader
 * @package Pentagonal\Modular
 * @todo completion
 */
class Reader
{
    /**
     * @var DirectoryIterator[]
     */
    protected $spl;

    /**
     * @var ParserGetter
     */
    protected $parserGetter;

    /**
     * @var StorageInterface
     */
    protected $notDirectories;

    /**
     * @var StorageInterface
     */
    protected $listsModules;

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
    }

    /**
     * Begin process
     *
     * @return Reader
     */
    public function process() : Reader
    {
        if ($this->listsModules instanceof StorageArrayObject) {
            return $this;
        }

        $this->listsModules   = new StorageArrayObject();
        $this->notDirectories = new StorageArray();
        /**
         * @var DirectoryIterator $directoryIterator
         */
        foreach (new DirectoryIterator($this->spl->getRealPath()) as $key => $directoryIterator) {
            if ($directoryIterator->isDot()) {
                continue;
            }

            $name = $directoryIterator->getBasename();
            if ($directoryIterator->getType() === FileType::TYPE_DIR) {
                $this->listsModules[$name] = $this
                    ->parserGetter
                    ->getParserInstance($directoryIterator)
                    ->parse();
                continue;
            }

            $this->notDirectories[$name] = $directoryIterator->getFileInfo(SplFileInfo::class);
        }
        print_r($this);
        exit;
        return $this;
    }
}

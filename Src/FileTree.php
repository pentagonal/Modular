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
 * Class FileTree
 * @package Pentagonal\Modular
 * @mixin SplFileInfo|DirectoryIterator
 */
final class FileTree implements \ArrayAccess
{
    /**
     * @var SplFileInfo
     */
    protected $spl;

    /**
     * @var StorageArray
     */
    private $storage;

    /**
     * FileTree constructor.
     *
     * @param $path
     */
    public function __construct($path)
    {
        if (!is_string($path) && ! $path instanceof \SplFileInfo) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Path must be as a string or instance of %s',
                    \SplFileInfo::class
                )
            );
        }

        if (is_string($path)) {
            $this->spl = new SplFileInfo($path);
        } else {
            $this->spl = $path->getFileInfo(SplFileInfo::class);
        }
    }

    /**
     * @return SplFileInfo|\SplFileInfo
     */
    public function getSpl() : SplFileInfo
    {
        return $this->spl;
    }


    /**
     * @param SplFileInfo|DirectoryIterator|\SplFileInfo $spl
     *
     * @return StorageArray|\SplFileInfo|SplFileInfo|SplFileInfo[]
     */
    private function readRecursive(\SplFileInfo $spl)
    {
        if (! $spl->getType() !== FileType::TYPE_DIR) {
            return $spl->getFileInfo(SplFileInfo::class);
        }

        $spl  = new DirectoryIterator($spl->getRealPath());
        $data = new StorageArray();
        foreach ($spl as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            $data[$fileInfo->getBasename()] = $this->readRecursive($fileInfo);
        }

        return $data;
    }

    /**
     * @return StorageArray
     */
    public function all() : StorageArray
    {
        if ($this->storage) {
            return $this->storage;
        }
        $this->storage = new StorageArray([
            $this->getBasename() => $this->readRecursive($this->spl)
        ]);
        return $this->storage;
    }

    /**
     * @return FileTree[]|FileTree
     */
    public function get()
    {
        return $this->all()->get($this->getBasename());
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this->spl, $name], $arguments);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return $this->storage->offsetExists($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->storage->offsetGet($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->storage->offsetSet($offset, $value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        $this->storage->offsetUnset($offset);
    }

    public function __toString()
    {
        return (string) $this->getRealPath();
    }
}

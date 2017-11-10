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

use Pentagonal\ArrayStore\StorageArrayObject as StorageArray;
use Pentagonal\Modular\Exceptions\ModuleException;
use Pentagonal\Modular\Override\DirectoryIterator;
use Pentagonal\Modular\Override\SplFileInfo;

/**
 * Class Parser
 * @package Pentagonal\Modular
 *
 * Reader that parse base per module
 */
class Parser
{
    const IGNORE_INDEX = 'index.php';

    /**
     * @var string
     */
    private $fileExtension = 'php';

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
     * The @uses Module instance
     *
     * @var string
     */
    protected $classExtends;

    /**
     * The full path file loaded
     *
     * @var SplFileInfo
     */
    protected $splFileIndexed;

    /**
     * @var StorageArray|null[]|string
     */
    protected $checkedFilesMessage;

    /**
     * @var \Throwable
     */
    protected $exception;

    /**
     * Parser constructor.
     * @final
     * @access internal|private
     */
    final private function __construct()
    {
        $this->splFileIndexed      = null;
        $this->className           = null;
        $this->classExtends        = null;
        $this->selector            = null;
        $this->hasParsed           = false;
        $this->splFileInfo         = null;
        $this->checkedFilesMessage = new StorageArray();
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
     * @return string|null
     */
    public function getSelector()
    {
        return $this->parse()->selector;
    }

    /**
     * @return string|null
     */
    public function getClassName()
    {
        return $this->parse()->className;
    }

    /**
     * @return null|SplFileInfo
     */
    public function getSplFileIndexed()
    {
        return $this->parse()->splFileIndexed;
    }

    /**
     * @return string
     */
    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    /**
     * @return string|null
     */
    public function getClassExtends()
    {
        return $this->parse()->classExtends;
    }

    /**
     * @return StorageArray|null[]|string[]
     */
    public function getCheckedFilesMessage() : StorageArray
    {
        return $this->checkedFilesMessage;
    }

    /**
     * @return \Throwable|null
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Check if module valid
     *
     * @return bool
     */
    public function isValid() : bool
    {
        $className = $this->parse()->getClassName();
        return $className
               && class_exists($className)
               && is_subclass_of($className, Module::class);
    }

    /**
     * @param SplFileInfo|DirectoryIterator|\SplFileInfo $spl
     *
     * @return StorageArray|\SplFileInfo|SplFileInfo|SplFileInfo[]
     */
    protected function listRecursive(\SplFileInfo $spl)
    {
        if (!$spl->isDir() || $spl->isLink() || !$spl->exist()) {
            return $spl->getFileInfo(SplFileInfo::class);
        }

        $spl = new DirectoryIterator($spl->getRealPath());
        $data = new StorageArray();
        foreach ($spl as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }
            $data[$fileInfo->getBasename()] = $this->listRecursive($fileInfo);
        }
        return $data;
    }

    /**
     * Doing parsing process
     *
     * @return Parser
     */
    public function parse() : Parser
    {
        if ($this->isHasParsed() === true) {
            return $this;
        }

        $this->selector = $this->createSelectorBySPL($this->splFileInfo);
        $this->splFileIndexed = null;

        // normalize
        $this->fileExtension = ltrim($this->fileExtension, '.');
        $this->hasParsed = true;

        $path = $this->getSplFileInfo()->getRealPath();
        $moduleBaseName = $this->getSplFileInfo()->getBasename();
        $baseFileName = $moduleBaseName . $this->fileExtension;
        $indexed = $path . DIRECTORY_SEPARATOR . '.' . $baseFileName;
        $validBaseName = ($fileName = pathinfo($moduleBaseName, PATHINFO_FILENAME))
            // only allow valid class name file
            && preg_match('~^[_a-z]([a-z0-9_]+)?$~i', $fileName)
            ? $moduleBaseName
            : null;

        $validBaseName
            && file_exists($indexed)
            && ($spl = new SplFileInfo($indexed))
            && $spl->isFile()
            && $spl->isReadable()
            && $this->validateFileModule($spl)
            && $this->splFileIndexed = $spl;
        // prop
        $toCheck = [];
        $foundMatch = false;
        foreach (new DirectoryIterator($path) as $iterator) {
            $baseName = $iterator->getBasename();
            if ($iterator->isDot()) {
                continue;
            }

            // set to FileTree Object to prevent nested files behavior
            // $this->fileLists[$baseName] = new FileTree($iterator);
            // list files
            if ($this->splFileIndexed           # if have validation before
                || $baseName === $baseFileName  # ignore previously checked
                || $baseName[0] === '.'         # if hidden file
                || ! $iterator->isFile()        # if not a file
                /*
                 * if file size less than 74 chars so it will be ignored
                 * below is example very minimum requirements
                 * 74 characters:
                 *
                 * <?php class A extends M{function initialize(){}function getInfo():array{}}
                 */
                || $iterator->getSize() < 74    # if size less than 74 characters
                # if extension is php / given extension
                || $iterator->getExtension() !== $this->fileExtension
                // if can not read do not process
                || ! $iterator->isReadable()
                || !($fileName = pathinfo($baseName, PATHINFO_FILENAME))
                // only allow
                || ! preg_match('~^[a-z]([a-z0-9_]+)?$~i', $fileName)
            ) {
                continue;
            }

            if ($validBaseName && strtolower($baseName) ===  strtolower($moduleBaseName)) {
                /**
                 * @var SplFileInfo $foundMatch
                 */
                $foundMatch = $iterator->getFileInfo(SplFileInfo::class);
                continue;
            }

            // process validation
            $toCheck[] = $iterator->getFileInfo(SplFileInfo::class);
        }
        /**
         * @var SplFileInfo[] $toCheck
         */
        if (! $this->splFileIndexed && $foundMatch && true === $this->validateFileModule($foundMatch)) {
            $this->splFileIndexed = $foundMatch;
        } elseif (!empty($toCheck)) {
            foreach ($toCheck as $key => $iterator) {
                unset($toCheck[$key]);
                if ($this->validateFileModule($iterator) === true) {
                    $this->splFileIndexed = $iterator;
                    break;
                }
            }
        }

        unset($toCheck, $foundMatch);
        if (!$this->splFileIndexed) {
            $this->exception = new ModuleException(
                sprintf(
                    'Module for %s has not found',
                    $moduleBaseName
                ),
                E_NOTICE
            );
            return $this;
        }

        try {
            /** @noinspection PhpIncludeInspection */
            require_once $this->splFileIndexed->getRealPath();
            $reflection = new \ReflectionClass($this->getClassName());
            $this->className = $reflection->getName();
            if ($reflection->isAbstract()) {
                throw new ModuleException(
                    sprintf(
                        'Class %s is an abstract class',
                        $this->className
                    ),
                    E_NOTICE
                );
            }
        } catch (\Throwable $e) {
            $this->splFileIndexed = null;
            $this->className    = null;
            $this->classExtends = null;
            $this->exception    = $e;
        }

        return $this;
    }

    /**
     * Use direct check
     *
     * @param SplFileInfo $spl
     *
     * @return SplFileInfo|bool
     */
    protected function validateFileModule(SplFileInfo $spl) : bool
    {
        $baseName                             = $spl->getBasename();
        $fullPath                             = $spl->getRealPath();
        $fileName                             = pathinfo($baseName, PATHINFO_FILENAME);
        $this->checkedFilesMessage[$fullPath] = null;

        if (!preg_match('~^[_a-z]([a-z0-9_]+)?$~i', $fileName)) {
            $this->checkedFilesMessage[$fullPath] = new ModuleException(
                sprintf('Module file name %s invalid context', $baseName)
            );
            return false;
        }

        $evaluator = PhpFileEvaluator::create($spl);
        if (!$evaluator->isValid()) {
            $this->checkedFilesMessage[$fullPath] = $evaluator->getException();
            return false;
        }

        $content = php_strip_whitespace($spl->getRealPath());
        // validate regex
        preg_match_all(
            '~
              class\s+([_a-z](?:[a-z0-9_]+)?)
              | extends\s+
                (?:\\\)?                                # name space none
                (
                    (?:[_a-z](?:[a-z0-9_]+)?)           # base extends
                    (?:(?:\\\[_a-z][a-z0-9_]+){1,})?
                )
               | namespace(
                    (?:
                        \s+\\\?(?:[^;]+);   # named namespace
                    )|\s*\{                 # empty namespace
               )
               | function\s+(initialize\((?:[^\)]+)?\))           # base method initialize()
               | function\s+(getInfo\((?:[^\)]+)?\))\s*\:\s*array # base method getInfo()
             ~mixs',
            // strip the white space
            $content,
            $match,
            PREG_PATTERN_ORDER
        );

        if (empty($match[1]) || empty($match[2])
            || empty(array_filter($match[1]))
            || empty(array_filter($match[2]))
        ) {
            return false;
        }

        $nameSpace = null;
        if (!empty(array_filter($match[3]))) {
            $nameSpace = reset($match[3]);
            if (trim($nameSpace) === '{') {
                $nameSpace = trim($nameSpace, ' {');
            } else {
                $nameSpace = trim(substr($nameSpace, 0, -1));
            }
            $classPos = 1;
        } else {
            $classPos = 0;
        }

        if (empty($match[1][$classPos]) || empty($match[2][$classPos+1])) {
            return false;
        }

        $className = $match[1][$classPos];
        $extends = $match[2][$classPos+1];
        if ($nameSpace) {
            if (!preg_match('/(?:[_a-z](?:[a-z0-9_]+)?)(?:(?:\\\[_a-z][a-z0-9_]+){1,})?$/i', $nameSpace)) {
                return false;
            }
            $className = "{$nameSpace}\\{$className}";
        }

        /**
         * Test if extendable Module object class
         */
        if (!class_exists($extends) || is_subclass_of($extends, Module::class)) {
            $this->checkedFilesMessage[$fullPath] = new ModuleException(sprintf(
                'Extends module for %1$s is not sub class of %2$s',
                $className,
                Module::class
            ));

            return false;
        }

        $initialize    = array_filter($match[4]);
        $initialize    = reset($initialize);
        $getInfo       = array_filter($match[5]);
        $getInfo       = reset($getInfo);
        $reflectionExtends = new \ReflectionClass($extends);

        if (!$initialize || ! $getInfo) {
            foreach (['getInfo', 'initialize'] as $method) {
                if (empty($$method)) {
                    continue;
                }
                try {
                    $refMethod = $reflectionExtends->getMethod($method);
                    if ($refMethod->isAbstract()) {
                        throw new ModuleException(
                            sprintf(
                                '%1$s does not implement method %2$s()',
                                $className,
                                $method
                            )
                        );
                    }
                    if (!$refMethod->isPublic()) {
                        throw new ModuleException(
                            sprintf(
                                '%1$s::%2$s() has invalid visibility',
                                $className,
                                $method
                            )
                        );
                    }
                } catch (\Throwable $e) {
                    $this->checkedFilesMessage[$fullPath] = $e;
                    return false;
                }
            }
        } else {
            foreach (['getInfo', 'initialize'] as $method) {
                unset($matchParams);
                preg_match("/{$method}\(\s*(.+)\s*\)/smix", $$method, $matchParams);
                if (!empty($matchInitParams[1])) {
                    preg_match_all(
                        '/(?P<name>\$[_a-z]+)(?:\=(?P<optional>(?:[^,]+)?))?/i',
                        preg_replace('~\s*\=\s*~', '=', $matchInitParams[1]),
                        $matchParams
                    );
                    foreach ($matchParams['name'] as $key => $value) {
                        if (empty($matchParams['optional'][$key])) {
                            $this->checkedFilesMessage[$fullPath] = new ModuleException(
                                sprintf(
                                    'Method %1$s::%2$s() contains required parameters.',
                                    $className,
                                    $method
                                )
                            );
                            return false;
                        }
                    }
                }
            }
        }

        // found
        $this->className    = $className;
        $this->classExtends = $reflectionExtends->getName();
        return true;
    }


    /**
     * @param \SplFileInfo $spl
     *
     * @return string
     */
    protected function createSelectorBySPL(\SplFileInfo $spl) : string
    {
        return sha1($spl->getRealPath());
    }
}

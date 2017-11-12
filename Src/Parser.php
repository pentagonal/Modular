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
use Pentagonal\Modular\Exceptions\ModuleNotFoundException;
use Pentagonal\Modular\Exceptions\ModulePathException;
use Pentagonal\Modular\Override\DirectoryIterator;
use Pentagonal\Modular\Override\SplFileInfo;

/**
 * Class Parser
 * @package Pentagonal\Modular
 *
 * Reader that parse base per module
 * @mixin SplFileInfo
 */
class Parser
{
    const CLASS_NAME_REGEX = '~^([_a-zA-Z](?:[a-zA-Z0-9_]+)?)$~';

    /**
     * Recommended to use 83 chars
     *
     * if file size less than 83 chars so it will be ignored
     * below is example very minimum requirements
     * 83 characters:
     *
     * <?php class A extends M{function initialize(){}function getInfo():array{return$a;}}
     */
    const MIN_FILE_SIZE = 83;

    /**
     * @var bool
     */
    protected $hasParsed = false;

    /**
     * List ignored files
     *
     * @var array
     */
    protected $ignoredFiles = [
        'index.php'
    ];

    /**
     * @var string
     */
    private $fileExtension = 'php';

    /**
     * @var SplFileInfo
     */
    protected $spl;

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
    protected $parentClass;

    /**
     * Instance Module Object
     *
     * @var Module|null
     */
    protected $moduleInstance;

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
     * @var array[]
     * key value as full path and as className lower
     * @access internal
     */
    protected static $cachedLoadedClasses = [];

    /**
     * Parser constructor.
     * @final
     * @access internal|private
     */
    final private function __construct()
    {
        $this->splFileIndexed      = null;
        $this->moduleInstance      = null;
        $this->className           = null;
        $this->parentClass         = null;
        $this->selector            = null;
        $this->hasParsed           = false;
        $this->spl                 = null;
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
            throw new ModulePathException(
                sprintf(
                    '%1$s is not a valid directory. Path type is %2$s',
                    $di->getPathname(),
                    !$di->isDot() ? $di->getType() : 'Dot selector directory'
                ),
                E_NOTICE,
                $di->getRealPath() ?: $di->getPathname()
            );
        }

        $object->spl = $di->getFileInfo();

        return $object;
    }

    /**
     * Get SplFileInfo
     *
     * @return SplFileInfo
     */
    public function getSpl() : SplFileInfo
    {
        return $this->spl;
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
     * this must be returning consistent hash
     *
     * @return string
     */
    public function getSelector() : string
    {
        return $this->parse()->selector;
    }

    /**
     * @return Module
     * @throws \Throwable
     */
    public function getModuleInstance() : Module
    {
        if (! $this->parse()->isValid()) {
            throw $this->exception;
        }

        return $this->moduleInstance;
    }

    /**
     * @return string|null
     */
    public function getModuleClassName()
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
    public function getParentClass()
    {
        return $this->parse()->parentClass;
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
    final public function isValid() : bool
    {
        $object = $this->parse()->moduleInstance;
        return $object && $object instanceof Module;
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

        $this->selector = $this->createSelectorBySPL($this->spl);
        $this->splFileIndexed = null;

        // normalize
        $this->fileExtension = ltrim($this->fileExtension, '.');
        $this->hasParsed = true;

        $path = $this->getSpl()->getRealPath();
        $moduleBaseName = $this->getBaseName();

        $baseFileName = $moduleBaseName . $this->fileExtension;
        $indexed = $path . DIRECTORY_SEPARATOR . '.' . $baseFileName;
        $validBaseName = ($fileName = pathinfo($moduleBaseName, PATHINFO_FILENAME))
            // only allow valid class name file
            && preg_match(self::CLASS_NAME_REGEX, $fileName)
            ? $moduleBaseName
            : null;
        if ($validBaseName
            && file_exists($indexed)
            && ($spl = new SplFileInfo($indexed))
            && $spl->isFile()
            && $spl->isReadable()
        ) {
            if (!empty(self::$cachedLoadedClasses[$spl->getRealPath()])) {
                $classes              = self::$cachedLoadedClasses[$spl->getRealPath()];
                $this->className = array_shift($classes);
                $this->parentClass    = array_shift($classes);
                $this->splFileIndexed = $spl;
            } elseif ($this->validateFileModule($spl)) {
                $this->splFileIndexed = $spl;
            }
        } else {
            // prop
            $toCheck    = [];
            foreach (new DirectoryIterator($path) as $iterator) {
                if ($iterator->isDot()) {
                    continue;
                }

                if (!empty(self::$cachedLoadedClasses[$iterator->getRealPath()])) {
                    $this->splFileIndexed = $iterator->getFileInfo(SplFileInfo::class);
                    $this->className = self::$cachedLoadedClasses[$iterator->getRealPath()];
                    break;
                }

                $baseName = $iterator->getBasename();
                # if not a file
                if (! $iterator->isFile()
                    # if in ignored files
                    || in_array($baseName, $this->ignoredFiles)
                    # if size less than MIN_FILE_SIZE
                    || $iterator->getSize() < static::MIN_FILE_SIZE
                    # if extension is php / given extension
                    || $iterator->getExtension() !== $this->fileExtension
                    // if can not read do not process
                    || ! $iterator->isReadable()
                    // only allow valid class Name Regex
                    || ! ($fileName = pathinfo($baseName, PATHINFO_FILENAME))
                    || ! preg_match(self::CLASS_NAME_REGEX, $fileName)
                ) {
                    continue;
                }

                // file info to process validation
                $toCheck[] = $iterator->getFileInfo(SplFileInfo::class);
            }

            /**
             * @var SplFileInfo[] $toCheck
             */
            if (! empty($toCheck)) {
                foreach ($toCheck as $key => $iterator) {
                    unset($toCheck[$key]);
                    if ($this->validateFileModule($iterator) === true) {
                        $this->splFileIndexed = $iterator;
                        break;
                    }
                }
            }

            if (! $this->splFileIndexed) {
                $this->exception = new ModuleNotFoundException(
                    sprintf(
                        'Module for %s has not found',
                        $moduleBaseName
                    ),
                    E_NOTICE,
                    $moduleBaseName
                );

                return $this;
            }
        }

        // load if has only loaded once
        if (empty(self::$cachedLoadedClasses[$this->splFileIndexed->getRealPath()])) {
            $level = ob_get_level();
            // handle error
            set_error_handler(function ($code, $message) {
                throw new ModuleException(
                    $message,
                    $code
                );
            });
            // start buffer
            ob_start();
            try {
                // call mutable bind to null, prevent override variable
                $includeFile = (function ($file) {
                    /** @noinspection PhpIncludeInspection */
                    include_once $file;
                })->bindTo(null);
                $realPath = $this->splFileIndexed->getRealPath();
                // by pass include
                $includeFile($realPath);

                $reflection           = new \ReflectionClass($this->getModuleClassName());
                $this->className      = $reflection->getName();
                if ($reflection->isAbstract()) {
                    throw new ModuleException(
                        sprintf(
                            'Class %s is an abstract class',
                            $this->className
                        ),
                        E_NOTICE
                    );
                }

                $this->parentClass    = $reflection->getParentClass()->getName();
                $this->moduleInstance = $this->newConstruct();
                self::$cachedLoadedClasses[$realPath] = [
                    $this->className,
                    $this->parentClass
                ];
            } catch (\Throwable $e) {
                $this->splFileIndexed = null;
                $this->className      = null;
                $this->moduleInstance = null;
                $this->parentClass    = null;
                $this->exception      = $e;
            }

            // restore error
            restore_error_handler();
            // clean buffer
            while ($level < ob_get_level()) {
                ob_end_clean();
            }
        }

        return $this;
    }

    /**
     * Use direct check
     *
     * @param SplFileInfo $spl
     *
     * @return bool
     */
    protected function validateFileModule(SplFileInfo $spl) : bool
    {
        $baseName           = $spl->getBasename();
        $fullPath           = $spl->getRealPath();
        $fileName           = pathinfo($baseName, PATHINFO_FILENAME);
        $pathName           = pathinfo($spl->getPath(), PATHINFO_FILENAME);
        $validClassName     = [strtolower($fileName) => $fileName];
        $modulePublicMethod = ['getInfo', 'initialize'];

        if (!preg_match(self::CLASS_NAME_REGEX, $fileName, $match) || empty($match[1])) {
            $this->checkedFilesMessage[$fullPath] = new ModuleException(
                sprintf('Module file name %s invalid context', $baseName)
            );

            return false;
        }

        if (preg_match(self::CLASS_NAME_REGEX, $pathName)) {
            $validClassName[strtolower($pathName)] = $pathName;
        }

        // get files content with php_strip_whitespace to remove
        // unwanted content
        $content = php_strip_whitespace($spl->getRealPath());

        // pass if content length less than minimum
        if (strlen($content) < static::MIN_FILE_SIZE) {
            return false;
        }

        /**
         * Getting match content
         */
        preg_match_all(
            '~
            namespace(
                    (?:
                        \s+\\\?(?:[^;]+);   # named namespace
                    )|\s*\{                 # empty namespace
                )
                | \bclass\s+([_a-z](?:[a-z0-9_]+)?)
                | extends\s+
                    (?:\\\)?                                # name space none
                    (
                        (?:[_a-z](?:[a-z0-9_]+)?)           # base extends
                        (?:(?:\\\[_a-z][a-z0-9_]+){1,})?
                    )
                | 
                # Final method
                function\s+(
                    __construct
                    | finalGetConstructorArguments
                    | finalGetConstructorParser
                    # public
                    | finalGetInfo
                    | finalInitOnce
                )\s*\(
                | function\s+(initialize\s*\((?:[^\)]+)?\))           # base method initialize()
                | function\s+(getInfo\s*\((?:[^\)]+)?\))\s*\:\s*array # base method getInfo()
                \s*\{(.*?)\}
             ~mixs',
            // strip the white space
            $content,
            $match,
            PREG_PATTERN_ORDER
        );

        /**
         * Offset Selector
         */
        $offsetNameSpace    = 1;
        $offsetClass        = 2;
        $offsetExtends      = 3;
        $offsetMethodFinal  = 4;
        $offsetMethodInit   = 5;
        $offsetMethodInfo   = 6;
        $offsetMethodInfoParenthesis = 7;

        // Check for module class content
        if (empty($match[$offsetClass])        # class
            || empty($match[$offsetExtends])   # extends
            || empty(array_filter($match[$offsetClass]))
            || empty(array_filter($match[$offsetExtends]))
        ) {
            $this->checkedFilesMessage[$fullPath] = new ModuleException(
                sprintf(
                    '%s Invalid Module file.',
                    $baseName
                )
            );

            return false;
        }

        // null is maybe does not have name space
        // or just create namespace { code... }
        $nameSpace = null;
        if (!empty($match[$offsetNameSpace][0])) {
            $nameSpace = $match[$offsetNameSpace][0];
            if (trim($nameSpace) === '{') {
                $nameSpace = trim($nameSpace, ' {');
            } else {
                $nameSpace = trim(substr($nameSpace, 0, -1));
            }
            $classPos = 1;
        } else {
            $classPos = 0;
        }

        // class name
        $className = !empty($match[$offsetClass][$classPos]) ? $match[$offsetClass][$classPos] : null;
        // parent class
        $parentClass   = !empty($match[$offsetExtends][$classPos+1]) ? $match[$offsetExtends][$classPos+1] : null;
        // check of existences class and parent class
        // or if invalid name space
        if (! $className    # does not contains class
            || ! $parentClass # does not contains extends module
            # is contains name space but invalid
            || $nameSpace && ! preg_match(
                '~^\\\?(?:[_a-zA-Z](?:[a-zA-Z0-9_]+)?)(?:(?:\\\[_a-zA-Z][a-zA-Z0-9_]+){1,})?$~',
                $nameSpace
            )
        ) {
            // if invalid Name Space Name
            $nameSpace &&
                $this->checkedFilesMessage[$fullPath] = new ModuleException(
                    sprintf(
                        '%s contains invalid namespace',
                        $baseName
                    )
                );

            return false;
        }

        // check if class matching criteria
        if (!isset($validClassName[strtolower($className)])) {
            $this->checkedFilesMessage[$fullPath] = new ModuleException(
                sprintf(
                    'Base class of %1$s not matching criteria. Class name must be one of : %2$s',
                    $className,
                    implode(', ', $validClassName)
                )
            );

            return false;
        }

        $className = "{$nameSpace}\\{$className}";
        if (!$nameSpace) {
            $className = substr($className, 1);
        }

        // check that contains override final method
        $match[$offsetMethodFinal] = array_map('strtolower', array_filter($match[$offsetMethodFinal]));
        if (!empty($match[$offsetMethodFinal])
            && (
                count($match[$offsetClass]) === 1
                || key($match[$offsetMethodFinal]) < ($offsetMethodInfoParenthesis-1)
            )
        ) {
            $this->checkedFilesMessage[$fullPath] = new ModuleException(
                sprintf(
                    'Class %s contains override final method',
                    $className
                )
            );

            return false;
        }

        /**
         * Test if extendable Module object class
         */
        if (strtolower(Module::class) !== strtolower($parentClass)
            && (class_exists($parentClass) || is_subclass_of($parentClass, Module::class))
        ) {
            $this->checkedFilesMessage[$fullPath] = new ModuleException(sprintf(
                'Extends module for %1$s is not sub class of %2$s',
                $className,
                Module::class
            ));

            return false;
        }

        $initialize    = array_filter($match[$offsetMethodInit]);
        $initialize    = array_shift($initialize);
        $getInfo       = array_filter($match[$offsetMethodInfo]);
        $getInfo       = array_shift($getInfo);
        $getInfoInner  = array_filter($match[$offsetMethodInfoParenthesis]);
        $getInfoInner  = array_shift($getInfoInner);
        if ($getInfo) {
            // append bracket
            $getInfoInner = "{{$getInfoInner}";
            if (!$getInfoInner
                // must have return values
                || !preg_match('~[\s\{\};]return.+~i', $getInfoInner)
            ) {
                $this->checkedFilesMessage[$fullPath] = new ModuleException(
                    sprintf(
                        '%1$s does not have valid return values',
                        $className
                    )
                );

                return false;
            }
        }

        $reflectionExtends = new \ReflectionClass($parentClass);
        if (! $initialize || ! $getInfo) {
            foreach ($modulePublicMethod as $method) {
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
            foreach ($modulePublicMethod as $method) {
                unset($matchParams);
                preg_match("~{$method}\(\s*(.+)\s*\)~smix", $$method, $matchParams);
                if (!empty($matchInitParams[1])) {
                    preg_match_all(
                        '~(\$[_a-z-A-Z]+)(?:\=((?:[^,]+)?))?~',
                        preg_replace('~\s*\=\s*~', '=', $matchInitParams[1]),
                        $matchParams
                    );
                    foreach ($matchParams[1] as $key => $value) {
                        if (empty($matchParams[2][$key])) {
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

        $evaluator = PhpContentEvaluator::create($spl);
        if (!$evaluator->isValid()) {
            $this->checkedFilesMessage[$fullPath] = $evaluator->getException();
            return false;
        }

        // found
        $this->className   = $className;
        $this->parentClass = $reflectionExtends->getName();
        return true;
    }


    /**
     * Create selector
     * The selector must be consistent unique string
     *
     * @param \SplFileInfo $spl
     *
     * @return string
     */
    protected function createSelectorBySPL(\SplFileInfo $spl = null) : string
    {
        $spl = $spl?: $this;
        return sha1($spl->getBasename());
    }

    /**
     * Create new construct module
     *
     * @return Module
     * @throws \Throwable
     */
    public function newConstruct() : Module
    {
        if (!$this->className || ! is_subclass_of($this->className, Module::class)) {
            throw ($this->exception?: new ModuleException(sprintf(
                'Invalid module %s',
                $this->spl->getBasename()
            ))
            );
        }

        $object = new $this->className($this);
        return $object;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    final public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this->getSpl(), $name], $arguments);
    }
}

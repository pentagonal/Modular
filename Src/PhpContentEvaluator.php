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

namespace Pentagonal\Modular;

use Pentagonal\Modular\Override\SplFileInfo;
use Pentagonal\PhpEvaluator\BadSyntaxExceptions;
use Pentagonal\PhpEvaluator\Evaluator;

/**
 * Class PhpContentEvaluator
 * @package Pentagonal\Modular
 * @final
 */
final class PhpContentEvaluator
{
    /**
     * @var SplFileInfo
     */
    protected $spl;
    protected $content;
    protected $file = '';

    const PENDING    = 0;
    const OK         = true;
    const E_TAG      = Evaluator::E_TAG;
    const E_BUFFER   = Evaluator::E_BUFFER;
    const E_PARSE    = Evaluator::E_PARSE;
    const E_ERROR    = Evaluator::E_ERROR;

    /**
     * @var int|bool
     */
    protected $status = self::PENDING;

    /**
     * @var \Exception
     */
    protected $errorExceptions;

    /**
     * PhpContentEvaluator constructor.
     * @param $splOrString
     * @param string $fileName
     * @throws \Throwable
     */
    public function __construct($splOrString, string $fileName = '')
    {
        if (!is_string($splOrString) && ! $splOrString instanceof \SplFileInfo) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Argument 1 must be as string or instance of %s',
                    \SplFileInfo::class
                )
            );
        }

        /**
         * @var SplFileInfo|string $splOrString
         */
        $this->content = is_string($splOrString)
            ? $splOrString
            : $splOrString->getFileInfo(SplFileInfo::class)->getContents();
        $this->file = $splOrString instanceof \SplFileInfo
            ? $splOrString->getRealPath()
            : (is_string($fileName) ? $fileName : '');
    }

    /**
     * @return bool|int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return \Exception
     * @throws \Throwable
     */
    public function getException()
    {
        return $this->validate()->errorExceptions;
    }

    /**
     * @return PhpContentEvaluator
     * @throws \Throwable
     */
    public function validate() : PhpContentEvaluator
    {
        if ($this->status !== self::PENDING) {
            return $this;
        }

        try {
            Evaluator::check(
                $this->content,
                $this->file
            );
            $this->status = self::OK;
        } catch (\Throwable $e) {
            $this->errorExceptions = $e;
            $this->status = $e instanceof BadSyntaxExceptions ? $e->getCode() : self::E_ERROR;
            $this->errorExceptions = $e;
            return $this;
        }

        return $this;
    }

    /**
     * @return bool
     * @throws \Throwable
     */
    public function isValid() : bool
    {
        return $this->validate()->getStatus() === self::OK;
    }

    /**
     * Create checker from file path
     *
     * @param string $filePath
     * @return PhpContentEvaluator
     * @throws \Throwable
     */
    public static function fromFile(string $filePath) : PhpContentEvaluator
    {
        return self::create(new SplFileInfo($filePath));
    }

    /**
     * @param $info
     * @return PhpContentEvaluator
     * @throws \Throwable
     */
    public static function create($info) : PhpContentEvaluator
    {
        return new self($info);
    }
}

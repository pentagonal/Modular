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

use Pentagonal\PhpEvaluator\BadSyntaxExceptions;
use Pentagonal\PhpEvaluator\Evaluator;

/**
 * Class PhpFileEvaluator
 * @package Pentagonal\Modular
 */
final class PhpFileEvaluator
{
    protected $spl;

    const PENDING    = 0;
    const OK         = true;
    const E_TAG      = Evaluator::E_TAG;
    const E_BUFFER   = Evaluator::E_BUFFER;
    const E_PARSE    = Evaluator::E_PARSE;
    const E_ERROR    = Evaluator::E_ERROR;

    /**
     * @var string
     */
    protected $status = self::PENDING;

    /**
     * @var \Exception
     */
    protected $errorExceptions;

    /**
     * PhpFileEvaluator constructor.
     *
     * @param SplFileInfo $es
     */
    public function __construct(SplFileInfo $es)
    {
        $this->spl = $es;
    }

    /**
     * @return string
     */
    public function getStatus() : string
    {
        return $this->validate()->status;
    }

    /**
     * @return \Exception|null returning null if has no error
     */
    public function getException()
    {
        return $this->validate()->errorExceptions;
    }

    /**
     * @return PhpFileEvaluator
     * @throws \Throwable
     */
    public function validate() : PhpFileEvaluator
    {
        if ($this->status !== self::PENDING) {
            return $this;
        }

        try {
            $this->status = self::OK;
            Evaluator::check(
                $this->spl->getContents(),
                $this->spl->getRealPath()
            );
        } catch (BadSyntaxExceptions $e) {
            $this->errorExceptions = $e;
            $this->status = $e->getCode();
        } catch (\Throwable $e) {
            $this->status = self::E_ERROR;
            $this->errorExceptions = $e;
            return $this;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isValid() : bool
    {
        return $this->getStatus() === self::OK;
    }

    /**
     * Create checker from file path
     *
     * @param string $filePath
     *
     * @return PhpFileEvaluator
     */
    public static function fromFile(string $filePath) : PhpFileEvaluator
    {
        return self::create(new SplFileInfo($filePath));
    }

    /**
     * Create instance
     *
     * @param SplFileInfo $info
     *
     * @return PhpFileEvaluator
     */
    public static function create(SplFileInfo $info) : PhpFileEvaluator
    {
        return new self($info);
    }
}

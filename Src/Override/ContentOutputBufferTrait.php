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

namespace Pentagonal\Modular\Override;

/**
 * Trait ContentOutputBufferTrait
 * @package Pentagonal\Modular\Override
 */
trait ContentOutputBufferTrait
{
    /**
     * Get content with output buffer
     *
     * @return void because use printing data into stdout
     */
    public function getContentsOutputBuffer()
    {
        if (!$this instanceof \SplFileInfo) {
            throw new \RuntimeException(
                sprintf(
                    'Trait %1$s must be used on %2$s instance',
                    __TRAIT__,
                    \SplFileInfo::class
                )
            );
        }

        /**
         * @var \SplFileInfo $this
         */
        if (!$this->isFile()) {
            throw new \RuntimeException(
                sprintf(
                    'Can not read %1$s because the type is not a file',
                    $this->getPathname()
                ),
                E_WARNING
            );
        }

        if (!$this->isReadable()) {
            throw new \RuntimeException(
                sprintf(
                    'File %1$s is not readable',
                    $this->getPathname()
                ),
                E_WARNING
            );
        }

        $open = $this->openFile('r');
        while (!$open->eof()) {
            echo $open->fread(1024);
        }
    }

    /**
     * Get contents for certain file
     *
     * @return string
     * @throws \Throwable
     */
    public function getContents() : string
    {
        $level = ob_get_level();
        try {
            // using start buffer
            ob_start();
            $this->getContentsOutputBuffer();
            return ob_get_clean();
        } catch (\Throwable $e) {
            // clean if has buffer level higher than original
            // this causes on call getContentsOutputBuffer()
            ($level < ob_get_level()) && ob_end_clean();
            throw $e;
        }
    }
}

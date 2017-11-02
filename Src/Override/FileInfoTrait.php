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

use Pentagonal\Modular\FileType;

/**
 * Trait FileInfoTrait
 * @package Pentagonal\Modular\Override
 */
trait FileInfoTrait
{
    /**
     * @return \SplFileInfo
     */
    protected function getObjectParentSplFileInfo()
    {
        $args     = func_get_args();
        $function = array_shift($args);
        if (!is_callable("parent::{$function}")) {
            throw new \BadMethodCallException(
                sprintf(
                    'Method parent::%s is not exists',
                    "{$function}"
                ),
                E_WARNING
            );
        }

        $current = call_user_func_array(
            "parent::{$function}",
            $args
        );

        if (is_object($current)
            && get_class($current) === \SplFileInfo::class
            && count($args) === 0
        ) {
            $current = new SplFileInfo($current->getPathname());
        }

        return $current;
    }

    /**
     * {@inheritdoc}
     *
     * @return SplFileInfo|\SplFileInfo
     */
    public function getFileInfo($class_name = null)
    {
        return call_user_func_array(
            [$this, 'getObjectParentSplFileInfo'],
            array_merge([__FUNCTION__], func_get_args())
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return SplFileInfo|\SplFileInfo
     */
    public function getPathInfo($class_name = null)
    {
        return call_user_func_array(
            [$this, 'getObjectParentSplFileInfo'],
            array_merge([__FUNCTION__], func_get_args())
        );
    }

    /**
     * Handle invalid type and forward to @uses SplFileInfo::TYPE_UNKNOWN
     *
     * @return string
     */
    public function getType() : string
    {
        if (!is_callable("parent::getType")) {
            throw new \BadMethodCallException(
                'Method parent::getType is not exists',
                E_WARNING
            );
        }

        try {
            // get types from parent
            return parent::getType();
        } catch (\Throwable $e) {
            return FileType::TYPE_UNKNOWN;
        }
    }
}

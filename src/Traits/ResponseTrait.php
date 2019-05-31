<?php

namespace Littlebug\Traits;

/**
 * Trait ResponseTrait Response返回Trait
 *
 * @package Littlebug\Traits
 */
trait ResponseTrait
{
    /**
     * 成功返回
     *
     * @param array  $data
     * @param string $success_msg
     *
     * @return array
     */
    public function success($data = [], $success_msg = 'ok')
    {
        return [true, $success_msg, $data];
    }

    /**
     * 失败返回
     *
     * @param string $error_msg
     * @param array  $data
     *
     * @return array
     */
    public function error($error_msg = 'error', $data = [])
    {
        return [false, $error_msg, $data];
    }
}
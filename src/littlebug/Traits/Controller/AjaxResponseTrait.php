<?php

/**
 *
 * AjaxOutputTrait.php
 * Create: 16-9-6 16:01
 * Editor: created by PhpStorm
 */

namespace LittleBug\Traits\Controller;

trait AjaxResponseTrait
{
    public function send($return_data)
    {
        list($ok, $msg, $data) = $return_data;
        return $ok ? $this->sendSuccess($data, $msg) : $this->sendError($msg, $data);
    }

    /****
     *
     * 操作成功返回
     *
     * @param        $data
     * @param string $success_msg
     * @param array  $extra
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendSuccess($data, $success_msg = '操作成功', $extra = [])
    {
        return response()->json(
            array_merge(
                [
                    'code' => 200,
                    'data' => $data,
                    'msg'  => $success_msg,
                    'type' => 'reload'
                ], (array)$extra
            )
        );
    }

    /*****
     *
     *
     * 操作失败返回
     *
     * @param       $error_msg
     * @param int   $error_code
     * @param array $data
     * @param array $extra
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendError($error_msg, $error_code = 400, $data = [], $extra = [])
    {
        if (request()->ajax() || request()->pjax()) {
            return response()->json(
                array_merge(
                    [
                        'code' => $error_code,
                        'data' => $data,
                        'msg'  => $error_msg,
                        'type' => 'modal_alert',
                    ], (array)$extra
                )
            );
        } else {
            return view('errors.error', ['code' => $error_code, 'msg' => $error_msg]);
        }
    }
}
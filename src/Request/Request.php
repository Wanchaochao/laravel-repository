<?php

namespace Littlebug\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

abstract class Request extends FormRequest
{

    public function response(array $errors)
    {
        // 指定上传文件的要返回json格式
        if ($this->expectsJson() && $this->ajax() && $this->wantsJson()) {
            return response()->json($errors);
        } else {
            return response()->view('error.error', ['msg' => $errors['msg']]);
        }
    }


    /**
     * 自定义错误格式
     *
     * @param Validator $validator
     *
     * @return array
     */
    protected function formatErrors(Validator $validator)
    {
        $error = $validator->errors()->first();
        $data   = [
            'code' => 201,
            'msg'  => $error,
        ];

        if (request()->isMethod('get')) {
            $data['type'] = 'ajax_alert';
        }

        return $data;
    }

    public function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->first();
        $data   = [
            'code' => 201,
            'msg'  => $errors,
            'type' => 'reload'
        ];

        if (request()->ajax()) {
            $data['type'] = 'ajax_alert';
        }

        exit(json_encode($data));

    }

}

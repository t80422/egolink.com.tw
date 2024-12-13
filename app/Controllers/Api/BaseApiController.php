<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Helpers\ApiResponse;

class BaseApiController extends ResourceController
{
    // 提供RESTful API 回應功能
    use ResponseTrait;
    
    /**
     * 成功回應
     *
     * @param string $message
     * @param mixed $data
     * @return void
     */
    protected function successResponse(string $message, $data = null)
    {
        return $this->respond(
            ApiResponse::success($message, $data)
        );
    }

    /**
     * 錯誤回應
     * @param string $message 錯誤訊息
     * @param string|null $errors 錯誤內容
     * @param int|null $code 狀態碼
     * @return mixed
     */
    protected function errorResponse(string $message, $errors = null)
    {
        return $this->respond(ApiResponse::error($message, $errors));
    }
}

<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Helpers\ApiResponse;
use Exception;

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
    protected function successResponse(string $message = 'Success', $data = null)
    {
        return $this->respond(
            ApiResponse::success($message, $data)
        );
    }

    /**
     * 錯誤回應
     * @param string $message 錯誤訊息
     * @param string|null $errors 錯誤內容
     * @return mixed
     */
    protected function errorResponse(string $message, ?Exception $e = null)
    {
        if ($e) {
            $location = str_replace(ROOTPATH, '', $e->getFile()) . '(Line: ' . $e->getLine() . ')';
            log_message('error', '{message}' . PHP_EOL . 'Location: {location}', [
                'message' => $e->getMessage(),
                'location' => $location
            ]);
        }

        return $this->respond(ApiResponse::error($message));
    }
}

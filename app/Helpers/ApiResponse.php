<?php

namespace App\Helpers;

use Exception;

class ApiResponse
{
    /**
     * 成功回應
     *
     * @param string $message
     * @param [type] $data
     * @return array
     */
    public static function success(string $message = 'Success', $data = null): array
    {
        if ($message === '') {
            $message = 'Success';
        }

        return [
            'status' => true,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * 錯誤回應
     */
    public static function error(string $message = 'Error'): array
    {
        return [
            'status' => false,
            'message' => $message
        ];
    }
}

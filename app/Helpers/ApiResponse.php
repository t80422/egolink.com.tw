<?php

namespace App\Helpers;

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
        if($message==='')
        {
            $message='Success';
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
    public static function error(string $message = 'Error', $errors = null, string $logContext = 'API Error'): array
    {
        if ($errors) {
            log_message('error', '[{context}] {message} | Details: {errors}', [
                'context' => $logContext,
                'message' => $message,
                'errors' => print_r($errors, true)
            ]);
        }

        $response = [
            'status' => false,
            'message' => $message
        ];

        return $response;
    }
}

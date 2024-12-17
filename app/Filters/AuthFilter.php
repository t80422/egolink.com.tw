<?php

namespace App\Filters;

use App\Libraries\JWTService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class AuthFilter implements FilterInterface
{
    protected $jwtService;
    protected $authService;

    public function __construct()
    {
        $this->jwtService = new JWTService();
        $this->authService=Services::auth();
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if (empty($header)) {
            return Services::response()
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'code'=>'NO_TOKEN',
                    'message' => '未提供驗證令牌',
                    'data' => null
                ]);
        }

        // 檢查 token 格式是否正確（是否包含 "Bearer "）
        if (!preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            return Services::response()
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'message' => '無效的令牌格式',
                    'data' => null
                ]);
        }

        // 獲取實際的 token
        $token = $matches[1];

        try {
            // 驗證 token 並獲取用戶信息
            $user = $this->jwtService->validateToken($token);

            // 將用戶信息添加到請求對象中，這樣控制器就能使用它
            $this->authService->setUser($user);

            return $request;
        } catch (\Exception $e) {
            return Services::response()
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'message' => $e->getMessage(),
                    'data' => null
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}

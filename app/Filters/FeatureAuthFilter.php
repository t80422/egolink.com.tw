<?php

namespace App\Filters;

use App\Models\FeatureModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Exception;

class FeatureAuthFilter implements FilterInterface
{
    protected $featureModel;
    protected $authSer;

    public function __construct()
    {
        $this->featureModel = new FeatureModel();
        $this->authSer = Services::auth();
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        try {
            // 從當前用戶取得角色
            $user = $this->authSer->getUser();

            if (!$user) {
                return $this->handleError('未授權的訪問', 401);
            }

            // 從請求路徑獲取功能代碼
            $uri = $request->getUri();
            $path = $uri->getPath();
            $featureCode = $this->getFeatureCodeFromPath($path);

            if (!$featureCode) {
                // 如果找不到對應的功能代碼,允許訪問
                return $request;
            }

            // 檢查權限
            if (!$this->featureModel->hasPermission($user->role, $featureCode)) {
                return $this->handleError('無權限訪問此功能', 403);
            }

            return $request;
        } catch (Exception $e) {
            log_message('error', '{message}', ['message' => $e->getMessage()]);
            return $this->handleError('權限驗證過程發生錯誤', 500);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    /**
     * 從請求路徑中取得功能代碼
     * 如:/api/admin/users -> users
     * @param string $path
     * @return string|null
     */
    private function getFeatureCodeFromPath(string $path): ?string
    {
        // 移除 API 前綴
        $path = preg_replace('/^\/api\//', '', $path);

        // 分割路徑
        $segments = explode('/', $path);

        // 根據路徑格式提取功能代碼
        if (count($segments) >= 2) {
            return $segments[1];
        }

        return null;
    }

    private function handleError(string $message, int $statusCode = 403): ResponseInterface
    {
        $response = Services::response();

        return $response->setStatusCode($statusCode)
            ->setJSON([
                'status' => false,
                'message' => $message
            ]);
    }
}

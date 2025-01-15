<?php

namespace App\Filters;

use App\Libraries\AuthService;
use App\Libraries\JWTService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Exception;

class AuthFilter implements FilterInterface
{
    protected $jwtService;
    protected $authService;

    public function __construct()
    {
        $this->jwtService = new JWTService();
        $this->authService = Services::auth();
    }

    public function before(RequestInterface $request, $arguments = null)
    {

        $token = $this->extractToken($request);

        if (empty($token)) {
            return $this->handleError('未提供認證令牌', 401);
        }

        try {
            $user = $this->authService->validateToken($token);

            $this->authService->setUser($user);

            return $request;
        } catch (Exception $e) {
            return $this->handleError($e->getMessage(), 401);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function extractToken(RequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');

        if (empty($header)) {
            return null;
        }

        if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {

            return $matches[1];
        }

        return null;
    }

    private function handleError(string $message, int $statusCode = 401): ResponseInterface
    {
        $response = Services::response();

        return $response->setStatusCode($statusCode)
            ->setJSON([
                'status' => false,
                'message' => $message
            ]);
    }
}

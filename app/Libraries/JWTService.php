<?php

namespace App\Libraries;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTService
{
    private $key;

    // Token 過期時間
    private  $expiration = 1 * 60 * 60;

    public function __construct()
    {
        $this->key = getenv('JWT_KEY');

        if (empty($this->key)) {
            throw new Exception('JWT 密鑰未設置');
        }
    }

    /**
     * 產生 JWT Token
     *
     * @param array $userData
     * @return string
     */
    public function createToken(array $userData): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->expiration;
        $payload = [
            'iat' => $issuedAt, // token 產生時間
            'exp' => $expire, // token 過期時間
            'user' => [
                'id' => $userData['id'],
                'role' => $userData['roleId']
            ]
        ];

        return JWT::encode($payload, $this->key, 'HS256');
    }

    public function validateToken(string $token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->key, 'HS256'));
            return $decoded->user;
        } catch (\Exception $e) {
            if ($e instanceof \Firebase\JWT\ExpiredException) {
                throw new Exception('令牌已過期: ' . $e->getMessage());
            } elseif ($e instanceof \Firebase\JWT\SignatureInvalidException) {
                throw new Exception('令牌簽名無效: ' . $e->getMessage());
            } else {
                throw new Exception('無效的身分驗證: ' . $e->getMessage());
            }
        }
    }

    public function refreshToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->key, 'HS256'));
            $userData = [
                'u_Id' => $decoded->user->id,
                'u_r_Id' => $decoded->user->role
            ];
            return $this->createToken($userData);
        } catch (\Exception $e) {
            throw new Exception('刷新令牌失敗: ' . $e->getMessage());
        }
    }
}

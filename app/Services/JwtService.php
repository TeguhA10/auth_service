<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Support\Str;

class JwtService
{
    /**
     * Generate Access Token (JWT)
     */
    public function generateAccessToken(int $userId): string
    {
        $now = time();

        $payload = [
            'jti' => (string) Str::uuid(),
            'sub' => (string) $userId,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + (15 * 60), // 15 menit
        ];

        return JWT::encode(
            $payload,
            config('app.jwt_access_secret'),
            'HS256'
        );
    }

    /**
     * Generate Refresh Token (random string)
     */
    public function generateRefreshToken(): string
    {
        return Str::random(64);
    }

    /**
     * Hash Refresh Token (SHA-256)
     */
    public function hashRefreshToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Decode & Verify Access Token
     */
    public function decodeAccessToken(string $jwt): object
    {
        return JWT::decode(
            $jwt,
            new Key(
                config('app.jwt_access_secret'),
                'HS256'
            )
        );
    }

    /**
     * Ambil JTI dari JWT
     */
    public function getJti(string $jwt): ?string
    {
        $payload = $this->decodeAccessToken($jwt);

        return $payload->jti ?? null;
    }

    /**
     * Ambil User ID dari JWT
     */
    public function getUserId(string $jwt): ?int
    {
        $payload = $this->decodeAccessToken($jwt);

        return isset($payload->sub)
            ? (int) $payload->sub
            : null;
    }

    public function makeAccessCookie(string $token): Cookie
    {
        return cookie(
            config('auth.cookie.access_name'),
            $token,
            config('auth.cookie.access_minutes'),
            config('auth.cookie.path'),
            config('auth.cookie.domain'),
            config('auth.cookie.secure'),
            config('auth.cookie.http_only'),
            false,
            config('auth.cookie.same_site'),
        );
    }

    public function makeRefreshCookie(string $token): Cookie
    {
        return cookie(
            config('auth.cookie.refresh_name'),
            $token,
            config('auth.cookie.refresh_minutes'),
            config('auth.cookie.path'),
            config('auth.cookie.domain'),
            config('auth.cookie.secure'),
            config('auth.cookie.http_only'),
            false,
            config('auth.cookie.same_site'),
        );
    }

    public function forgetAccessCookie(): Cookie
    {
        return cookie()->forget(
            config('auth.cookie.access_name')
        );
    }

    public function forgetRefreshCookie(): Cookie
    {
        return cookie()->forget(
            config('auth.cookie.refresh_name')
        );
    }
}

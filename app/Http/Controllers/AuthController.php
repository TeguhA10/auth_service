<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RefreshToken;
use App\Models\TokenBlacklist;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(
        protected JwtService $jwtService
    ) {}

    public function login(Request $request): JsonResponse
    {
        // Validasi input
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Cari user berdasarkan email
        $user = User::where('email', $validated['email'])->first();

        // Cek user & password
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        // Cek apakah akun aktif
        if (! $user->is_active) {
            return response()->json([
                'message' => 'Account is inactive.',
            ], 403);
        }

        // Generate Access Token (JWT)
        $accessToken = $this->jwtService->generateAccessToken($user->id);

        // Generate Refresh Token (random string)
        $refreshToken = $this->jwtService->generateRefreshToken();

        // Simpan HASH refresh token ke database
        RefreshToken::create([
            'user_id'     => $user->id,
            'token_hash'  => $this->jwtService->hashRefreshToken($refreshToken),
            'is_revoked'  => false,
            'expires_at'  => now()->addDays(30),
        ]);

        // Response
        return response()->json([
            'message' => 'Login successful.'
        ])->withCookie($this->jwtService->makeAccessCookie($accessToken))
            ->withCookie($this->jwtService->makeRefreshCookie($refreshToken));
    }

    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie('refresh_token');

        if (empty($refreshToken)) {
            return response()->json([
                'message' => 'Refresh token is required.',
            ], 401);
        }

        $tokenHash = $this->jwtService->hashRefreshToken($refreshToken);

        $storedToken = RefreshToken::where(
            'token_hash',
            $tokenHash
        )->first();

        if (
            ! $storedToken ||
            $storedToken->is_revoked ||
            $storedToken->expires_at->isPast()
        ) {
            return response()->json([
                'message' => 'Invalid refresh token.',
            ], 401);
        }

        $user = User::find($storedToken->user_id);

        if (! $user || ! $user->is_active) {
            return response()->json([
                'message' => 'User is inactive.',
            ], 401);
        }

        // Refresh Token Rotation
        $storedToken->update([
            'is_revoked' => true,
        ]);

        $newRefreshToken = $this->jwtService->generateRefreshToken();

        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => $this->jwtService->hashRefreshToken($newRefreshToken),
            'is_revoked' => false,
            'expires_at' => now()->addDays(30),
        ]);

        $newAccessToken = $this->jwtService->generateAccessToken($user->id);

        return response()
            ->json([
                'message' => 'Token refreshed successfully.',
            ])
            ->withCookie(
                $this->jwtService->makeAccessCookie($newAccessToken)
            )
            ->withCookie(
                $this->jwtService->makeRefreshCookie($newRefreshToken)
            );
    }

    public function logout(Request $request): JsonResponse
    {
        // Revoke refresh token
        $refreshToken = $request->cookie('refresh_token');

        if (! empty($refreshToken)) {
            RefreshToken::where(
                'token_hash',
                $this->jwtService->hashRefreshToken($refreshToken)
            )->update([
                'is_revoked' => true,
            ]);
        }

        // Blacklist access token
        $accessToken = $request->cookie('access_token');

        if (! empty($accessToken)) {
            try {
                $payload = $this->jwtService->decodeAccessToken($accessToken);

                TokenBlacklist::firstOrCreate(
                    [
                        'jti' => $payload->jti,
                    ],
                    [
                        'expires_at' => date(
                            'Y-m-d H:i:s',
                            $payload->exp
                        ),
                    ]
                );
            } catch (\Throwable $e) {
                // Abaikan jika token sudah invalid atau expired
            }
        }

        return response()
            ->json([
                'message' => 'Logout successful.',
            ])
            ->withCookie(
                $this->jwtService->forgetAccessCookie()
            )
            ->withCookie(
                $this->jwtService->forgetRefreshCookie()
            );
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\TokenBlacklist;
use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuth
{
    public function __construct(
        protected JwtService $jwtService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('access_token');

        if (! $token) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            $payload = $this->jwtService->decodeAccessToken($token);

            $blacklisted = TokenBlacklist::where('jti', $payload->jti)->exists();

            if ($blacklisted) {
                return response()->json([
                    'message' => 'Token has been revoked.',
                ], 401);
            }

            $user = User::find($payload->sub);

            if (! $user || ! $user->is_active) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Simpan user ke request
            $request->attributes->set('auth_user', $user);

            return $next($request);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Invalid or expired token.',
            ], 401);
        }
    }
}
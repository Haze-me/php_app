<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

class CheckBearerTokenValidity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if($token === null) {
            abort(401, 'No bearer token provided.');
        }
        // Find the PersonalAccessToken associated with the provided token.
        $personalAccessToken = PersonalAccessToken::where('token', hash('sha256', $token))->first();

        if (!$personalAccessToken || $this->isTokenExpired($personalAccessToken)) {
            return response()->json(['error' => 'Bearer token is invalid or expired.'], 401);
        }
        return $next($request);
    }

    /**
     * Check if the token is valid.
     *
     * @param string $token
     * @return bool
     */
    private function isTokenExpired(PersonalAccessToken $token)
    {
        $expirationDuration = config('sanctum.expiration', 0); // Get the expiration duration from your Sanctum config.
        $createdAt = $this->$token->created_at;
        $expirationTime = $createdAt->addMinutes($expirationDuration);

        return now()->gt($expirationTime);
    }
}

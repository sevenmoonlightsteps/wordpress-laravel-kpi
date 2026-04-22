<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Exchange a WordPress Application Password for a Sanctum token.
     *
     * The WP credentials are verified against the WP REST API.
     * If valid, a Laravel user is found-or-created and a Sanctum token is issued.
     * The WP Application Password is never stored.
     */
    public function wpTokenExchange(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'wp_site_url'    => 'required|url',
                'wp_username'    => 'required|string|max:255',
                'wp_app_password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $wpSiteUrl      = rtrim($validated['wp_site_url'], '/');
        $wpUsername     = $validated['wp_username'];
        $wpAppPassword  = $validated['wp_app_password'];

        $wpResponse = Http::withBasicAuth($wpUsername, $wpAppPassword)
            ->timeout(10)
            ->get("{$wpSiteUrl}/wp-json/wp/v2/users/me");

        if ($wpResponse->status() === 401 || $wpResponse->status() === 403) {
            return response()->json([
                'success' => false,
                'error'   => 'WordPress credentials are invalid.',
            ], 401);
        }

        if ($wpResponse->failed()) {
            return response()->json([
                'success' => false,
                'error'   => 'Could not reach the WordPress site.',
            ], 502);
        }

        $wpUser = $wpResponse->json();
        $wpUserId = $wpUser['id'] ?? null;

        if (!$wpUserId) {
            return response()->json([
                'success' => false,
                'error'   => 'WordPress returned an unexpected response.',
            ], 502);
        }

        $host = parse_url($wpSiteUrl, PHP_URL_HOST) ?? $wpSiteUrl;
        $email = "{$wpUsername}@{$host}";
        $displayName = $wpUser['name'] ?? $wpUsername;

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $displayName,
                'password' => bcrypt(Str::random(32)),
            ]
        );

        // Revoke any existing WP-exchange tokens for this user before issuing a new one.
        $user->tokens()->where('name', 'wp-exchange')->delete();

        $token = $user->createToken('wp-exchange');

        return response()->json([
            'success' => true,
            'data'    => [
                'token' => $token->plainTextToken,
            ],
        ], 201);
    }
}

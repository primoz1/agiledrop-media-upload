<?php namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Clients;
use App\Models\User;
use Illuminate\Support\Facades\App;

class TokenController extends Controller
{
    /**
     * Development-only helper endpoint.
     *
     * Generates a Laravel Sanctum API token for local testing.
     * This endpoint exists solely to simplify development and testing
     * of the API and must never be enabled in non-local environments.
     */
    public function __invoke()
    {
        abort_if(!App::isLocal(), 404);

        $client = Clients::firstOrCreate(
            ['slug' => 'shop'],
            ['name' => 'Example App']
        );

        $user = User::firstOrCreate(
            ['email' => 'api@example.com'],
            [
                'client_id' => $client->id,
                'name'      => 'API User',
                'password'  => bcrypt('password'),
            ]
        );

        // Revoke existing tokens to avoid token accumulation during development
        $user->tokens()->delete();

        return response()->json([
                                    'token' => $user->createToken('media-api')->plainTextToken,
                                ]);
    }
}

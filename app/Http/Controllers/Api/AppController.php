<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppRequest;
use App\Http\Requests\UpdateAppRequest;
use App\Reverb\FileApplicationProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class AppController extends Controller
{
    public function __construct(
        protected FileApplicationProvider $provider
    ) {}

    /**
     * List all applications.
     */
    public function index(): JsonResponse
    {
        $apps = $this->provider->getRawApplications()->map(function (array $app) {
            return $this->formatAppResponse($app);
        });

        return response()->json([
            'data' => $apps->values(),
        ]);
    }

    /**
     * Get a specific application.
     */
    public function show(string $app): JsonResponse
    {
        $application = $this->findApp($app);

        if (! $application) {
            return response()->json([
                'error' => 'Application not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $this->formatAppResponse($application),
        ]);
    }

    /**
     * Create a new application.
     */
    public function store(StoreAppRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $app = [
            'id' => $validated['id'] ?? (string) Str::uuid(),
            'key' => $validated['key'] ?? Str::random(20),
            'secret' => $validated['secret'] ?? Str::random(40),
            'name' => $validated['name'],
            'allowed_origins' => $validated['allowed_origins'] ?? ['*'],
            'enable_client_messages' => $validated['enable_client_messages'] ?? false,
            'max_connections' => $validated['max_connections'] ?? null,
            'max_message_size' => $validated['max_message_size'] ?? 10000,
            'options' => array_merge([
                'host' => config('reverb.servers.reverb.hostname', 'localhost'),
                'port' => (int) config('reverb.servers.reverb.port', 8080),
                'scheme' => 'http',
                'useTLS' => false,
                'ping_interval' => 60,
                'activity_timeout' => 30,
            ], $validated['options'] ?? []),
        ];

        $this->provider->addApp($app);

        return response()->json([
            'data' => $this->formatAppResponse($app),
            'message' => 'Application created successfully',
        ], Response::HTTP_CREATED);
    }

    /**
     * Update an existing application.
     */
    public function update(UpdateAppRequest $request, string $app): JsonResponse
    {
        $existing = $this->findApp($app);

        if (! $existing) {
            return response()->json([
                'error' => 'Application not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validated();

        $updated = array_merge($existing, $validated);

        if (isset($validated['options'])) {
            $updated['options'] = array_merge($existing['options'] ?? [], $validated['options']);
        }

        try {
            $this->provider->updateApp($app, $updated);
        } catch (RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $this->formatAppResponse($updated),
            'message' => 'Application updated successfully',
        ]);
    }

    /**
     * Delete an application.
     */
    public function destroy(string $app): JsonResponse
    {
        if (! $this->provider->exists($app)) {
            return response()->json([
                'error' => 'Application not found',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->provider->deleteApp($app);
        } catch (RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Application deleted successfully',
        ]);
    }

    /**
     * Regenerate the secret for an application.
     */
    public function regenerateSecret(string $app): JsonResponse
    {
        $existing = $this->findApp($app);

        if (! $existing) {
            return response()->json([
                'error' => 'Application not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $newSecret = Str::random(40);
        $updated = array_merge($existing, ['secret' => $newSecret]);

        try {
            $this->provider->updateApp($app, $updated);
        } catch (RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'data' => [
                'id' => $app,
                'secret' => $newSecret,
            ],
            'message' => 'Secret regenerated successfully',
        ]);
    }

    /**
     * Force reload the application configuration.
     */
    public function reload(): JsonResponse
    {
        $this->provider->reload();

        return response()->json([
            'message' => 'Configuration reloaded successfully',
        ]);
    }

    /**
     * Find an application by ID.
     *
     * @return array<string, mixed>|null
     */
    protected function findApp(string $id): ?array
    {
        return $this->provider->getRawApplications()->firstWhere('id', $id);
    }

    /**
     * Format application data for response (hide sensitive data).
     *
     * @param  array<string, mixed>  $app
     * @return array<string, mixed>
     */
    protected function formatAppResponse(array $app): array
    {
        return [
            'id' => $app['id'],
            'key' => $app['key'],
            'name' => $app['name'] ?? null,
            'allowed_origins' => $app['allowed_origins'] ?? ['*'],
            'enable_client_messages' => $app['enable_client_messages'] ?? false,
            'max_connections' => $app['max_connections'] ?? null,
            'max_message_size' => $app['max_message_size'] ?? 10000,
            'options' => $app['options'] ?? [],
        ];
    }
}

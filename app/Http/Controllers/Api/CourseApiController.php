<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCourseRequest;
use App\Http\Requests\Api\UpdateCourseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use JsonException;

class CourseApiController extends Controller
{
    private const BASE_PATH = 'courses';

    public function index(): JsonResponse
    {
        $courses = collect(Storage::disk('local')->directories(self::BASE_PATH))
            ->map(function (string $path): ?array {
                $slug = basename($path);
                $metadataPath = $this->metadataPath($slug);

                if (! Storage::disk('local')->exists($metadataPath)) {
                    return null;
                }

                $payload = $this->readJson($metadataPath);

                return [
                    'slug' => $slug,
                    'title' => $payload['title'] ?? null,
                    'description' => $payload['description'] ?? null,
                ];
            })
            ->filter()
            ->sortBy('slug')
            ->values()
            ->all();

        return response()->json([
            'data' => $courses,
        ]);
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $slug = $data['slug'];

        $this->assertSafeSegment($slug);

        $metadataPath = $this->metadataPath($slug);
        if (Storage::disk('local')->exists($metadataPath)) {
            return response()->json([
                'message' => 'Course already exists.',
            ], 409);
        }

        Storage::disk('local')->makeDirectory($this->coursePath($slug));

        $payload = [
            'id' => $slug,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'chapters' => $data['chapters'] ?? [],
        ];

        $this->writeJson($metadataPath, $payload);

        return response()->json([
            'data' => array_filter([
                'slug' => $slug,
                'title' => $payload['title'],
                'description' => $payload['description'],
                'chapters' => $payload['chapters'],
            ], static fn (mixed $value): bool => $value !== null),
        ], 201);
    }

    public function show(string $course): JsonResponse
    {
        $this->assertSafeSegment($course);

        $metadataPath = $this->metadataPath($course);
        if (! Storage::disk('local')->exists($metadataPath)) {
            return response()->json([
                'message' => 'Course not found.',
            ], 404);
        }

        $payload = $this->readJson($metadataPath);
        $payload['slug'] = $course;

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function update(UpdateCourseRequest $request, string $course): JsonResponse
    {
        $this->assertSafeSegment($course);

        $metadataPath = $this->metadataPath($course);
        if (! Storage::disk('local')->exists($metadataPath)) {
            return response()->json([
                'message' => 'Course not found.',
            ], 404);
        }

        $payload = $this->readJson($metadataPath);
        $data = $request->validated();

        if (array_key_exists('title', $data)) {
            $payload['title'] = $data['title'];
        }

        if (array_key_exists('description', $data)) {
            $payload['description'] = $data['description'];
        }

        if (array_key_exists('chapters', $data)) {
            $payload['chapters'] = $data['chapters'] ?? [];
        }

        $payload['id'] = $payload['id'] ?? $course;

        $this->writeJson($metadataPath, $payload);

        $payload['slug'] = $course;

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function destroy(string $course): JsonResponse
    {
        $this->assertSafeSegment($course);

        $coursePath = $this->coursePath($course);
        if (! Storage::disk('local')->exists($coursePath)) {
            return response()->json([
                'message' => 'Course not found.',
            ], 404);
        }

        Storage::disk('local')->deleteDirectory($coursePath);

        return response()->json([
            'deleted' => true,
        ]);
    }

    private function coursePath(string $slug): string
    {
        return self::BASE_PATH.'/'.$slug;
    }

    private function metadataPath(string $slug): string
    {
        return $this->coursePath($slug).'/chapters.json';
    }

    private function assertSafeSegment(string $segment): void
    {
        if ($segment === '' || str_contains($segment, '..')) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readJson(string $path): array
    {
        try {
            return json_decode(Storage::disk('local')->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeJson(string $path, array $payload): void
    {
        Storage::disk('local')->put(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }
}

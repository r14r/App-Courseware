<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreQuizRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use JsonException;

class QuizApiController extends Controller
{
    private const BASE_PATH = 'courses';

    public function show(string $course, string $chapter): JsonResponse
    {
        $this->assertSafeSegment($course);
        $this->assertSafeSegment($chapter);

        $quizPath = $this->quizPath($course, $chapter);
        if (! Storage::disk('local')->exists($quizPath)) {
            return response()->json([
                'message' => 'Quiz not found.',
            ], 404);
        }

        $payload = $this->readJson($quizPath);

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function update(StoreQuizRequest $request, string $course, string $chapter): JsonResponse
    {
        $this->assertSafeSegment($course);
        $this->assertSafeSegment($chapter);

        $payload = [
            'title' => $request->validated('title'),
            'questions' => $request->validated('questions'),
        ];

        Storage::disk('local')->makeDirectory($this->chapterPath($course, $chapter));
        $this->writeJson($this->quizPath($course, $chapter), $payload);

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function destroy(string $course, string $chapter): JsonResponse
    {
        $this->assertSafeSegment($course);
        $this->assertSafeSegment($chapter);

        $quizPath = $this->quizPath($course, $chapter);
        if (! Storage::disk('local')->exists($quizPath)) {
            return response()->json([
                'message' => 'Quiz not found.',
            ], 404);
        }

        Storage::disk('local')->delete($quizPath);

        return response()->json([
            'deleted' => true,
        ]);
    }

    private function quizPath(string $course, string $chapter): string
    {
        return $this->chapterPath($course, $chapter).'/quiz.json';
    }

    private function chapterPath(string $course, string $chapter): string
    {
        return self::BASE_PATH.'/'.$course.'/'.$chapter;
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

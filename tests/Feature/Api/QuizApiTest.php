<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('requires authentication to access quizzes', function () {
    $response = $this->getJson('/api/course/demo/chapters/intro/quiz');

    $response->assertUnauthorized();
});

it('stores and returns quizzes', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $payload = [
        'title' => 'Quiz',
        'questions' => [
            [
                'id' => 'q1',
                'type' => 'single',
                'question' => 'What is 1+1?',
                'options' => ['1', '2'],
                'correctIndex' => 1,
                'explanation' => 'Basic math.',
            ],
        ],
    ];

    $store = $this->actingAs($user)->putJson('/api/course/demo/chapters/intro/quiz', $payload);

    $store->assertSuccessful();
    expect(Storage::disk('local')->exists('courses/demo/intro/quiz.json'))->toBeTrue();

    $fetch = $this->actingAs($user)->getJson('/api/course/demo/chapters/intro/quiz');

    $fetch->assertSuccessful();
    $fetch->assertJson([
        'data' => $payload,
    ]);
});

it('deletes quizzes', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    Storage::disk('local')->makeDirectory('courses/demo/intro');
    Storage::disk('local')->put('courses/demo/intro/quiz.json', json_encode(['title' => 'Quiz'], JSON_THROW_ON_ERROR));

    $response = $this->actingAs($user)->deleteJson('/api/course/demo/chapters/intro/quiz');

    $response->assertSuccessful();
    expect(Storage::disk('local')->exists('courses/demo/intro/quiz.json'))->toBeFalse();
});

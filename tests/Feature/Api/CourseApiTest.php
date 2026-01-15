<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('requires authentication to list courses', function () {
    $response = $this->getJson('/api/course');

    $response->assertUnauthorized();
});

it('lists courses', function () {
    Storage::fake('local');

    Storage::disk('local')->makeDirectory('courses/course-1');
    Storage::disk('local')->put('courses/course-1/chapters.json', json_encode([
        'title' => 'Course 1',
        'description' => 'Intro',
    ], JSON_THROW_ON_ERROR));

    Storage::disk('local')->makeDirectory('courses/course-2');
    Storage::disk('local')->put('courses/course-2/chapters.json', json_encode([
        'title' => 'Course 2',
    ], JSON_THROW_ON_ERROR));

    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/course');

    $response->assertSuccessful();
    $response->assertJsonFragment([
        'slug' => 'course-1',
        'title' => 'Course 1',
        'description' => 'Intro',
    ]);
    $response->assertJsonFragment([
        'slug' => 'course-2',
        'title' => 'Course 2',
        'description' => null,
    ]);
});

it('creates and updates a course', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $create = $this->actingAs($user)->postJson('/api/course', [
        'slug' => 'demo-course',
        'title' => 'Demo Course',
        'description' => 'Testing',
        'chapters' => [
            [
                'id' => '001-intro',
                'title' => 'Intro',
            ],
        ],
    ]);

    $create->assertCreated();
    expect(Storage::disk('local')->exists('courses/demo-course/chapters.json'))->toBeTrue();

    $update = $this->actingAs($user)->putJson('/api/course/demo-course', [
        'title' => 'Updated Course',
    ]);

    $update->assertSuccessful();
    $payload = json_decode(Storage::disk('local')->get('courses/demo-course/chapters.json'), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['title'])->toBe('Updated Course')
        ->and($payload['chapters'][0]['id'])->toBe('001-intro');
});

it('deletes a course', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    Storage::disk('local')->makeDirectory('courses/demo-course');
    Storage::disk('local')->put('courses/demo-course/chapters.json', json_encode(['title' => 'Demo'], JSON_THROW_ON_ERROR));

    $response = $this->actingAs($user)->deleteJson('/api/course/demo-course');

    $response->assertSuccessful();
    expect(Storage::disk('local')->exists('courses/demo-course'))->toBeFalse();
});

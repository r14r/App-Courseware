<?php

use App\Models\Course;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects guests to login', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect();
});

it('shows user courses and scores', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create([
        'title' => 'Intro to Laravel',
    ]);

    $user->courses()->attach($course->id, [
        'score' => 4,
        'total_answers' => 5,
        'correct_answers' => 4,
        'final_score' => 80,
        'completed_chapters' => json_encode(['001-intro']),
        'completed_topics' => json_encode(['001-intro/001-topic.json', '001-intro/002-topic.json']),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('courses.0.title', 'Intro to Laravel')
        ->where('courses.0.total_answers', 5)
        ->where('courses.0.correct_answers', 4)
        ->where('courses.0.final_score', 80)
        ->where('courses.0.completed_chapters', 1)
        ->where('courses.0.completed_topics', 2));
});

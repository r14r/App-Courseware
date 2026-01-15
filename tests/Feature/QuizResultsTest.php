<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders the quiz results page', function () {
    $response = $this->get(route('quiz.results', [
        'slug' => 'demo-course',
        'chapter' => 'demo-chapter',
    ]));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Quiz/Results')
        ->where('slug', 'demo-course')
        ->where('chapter', 'demo-chapter'));
});

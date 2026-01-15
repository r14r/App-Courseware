<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders the quiz page', function () {
    $response = $this->get(route('quiz.show', [
        'slug' => 'demo-course',
        'chapter' => 'demo-chapter',
    ]));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Quiz/Show')
        ->where('slug', 'demo-course')
        ->where('chapter', 'demo-chapter'));
});

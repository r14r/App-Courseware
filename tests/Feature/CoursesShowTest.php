<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders the course show page', function () {
    $response = $this->get(route('courses.show', [
        'slug' => 'demo-course',
    ]));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Courses/Show')
        ->where('slug', 'demo-course'));
});

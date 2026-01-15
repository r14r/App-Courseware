<?php

use App\Http\Controllers\Api\CourseApiController;
use App\Http\Controllers\Api\QuizApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/course', [CourseApiController::class, 'index'])->name('api.course.index');
    Route::post('/course', [CourseApiController::class, 'store'])->name('api.course.store');
    Route::get('/course/{course}', [CourseApiController::class, 'show'])->name('api.course.show');
    Route::put('/course/{course}', [CourseApiController::class, 'update'])->name('api.course.update');
    Route::delete('/course/{course}', [CourseApiController::class, 'destroy'])->name('api.course.destroy');

    Route::get('/course/{course}/chapters/{chapter}/quiz', [QuizApiController::class, 'show'])
        ->name('api.course.quiz.show');
    Route::put('/course/{course}/chapters/{chapter}/quiz', [QuizApiController::class, 'update'])
        ->name('api.course.quiz.update');
    Route::delete('/course/{course}/chapters/{chapter}/quiz', [QuizApiController::class, 'destroy'])
        ->name('api.course.quiz.destroy');
});

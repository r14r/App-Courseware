<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_user', function (Blueprint $table) {
            $table->unsignedSmallInteger('total_answers')->nullable()->after('score');
            $table->unsignedSmallInteger('correct_answers')->nullable()->after('total_answers');
            $table->json('completed_chapters')->nullable()->after('correct_answers');
            $table->json('completed_topics')->nullable()->after('completed_chapters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_user', function (Blueprint $table) {
            $table->dropColumn([
                'total_answers',
                'correct_answers',
                'completed_chapters',
                'completed_topics',
            ]);
        });
    }
};

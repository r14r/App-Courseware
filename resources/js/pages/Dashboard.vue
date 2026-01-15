<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type CourseSummary = {
    id: number;
    slug: string;
    title: string;
    description: string | null;
    score: number | null;
    total_answers: number | null;
    correct_answers: number | null;
    final_score: number | null;
    chapters: number;
    completed_chapters: number;
    completed_topics: number;
};

defineProps<{
    courses: CourseSummary[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <section>
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.35em] text-muted-foreground">Your Courses</p>
                        <h1 class="mt-2 text-2xl font-semibold text-foreground">Progress Overview</h1>
                    </div>
                    <Link
                        href="/"
                        class="rounded-full border border-border px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-foreground"
                    >
                        Browse Courses
                    </Link>
                </div>
            </section>

            <section v-if="courses.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="course in courses"
                    :key="course.id"
                    class="rounded-2xl border border-border bg-card p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-foreground">{{ course.title }}</h2>
                            <p class="mt-2 text-sm text-muted-foreground">
                                {{ course.description || 'No description yet.' }}
                            </p>
                        </div>
                        <span class="rounded-full bg-muted px-3 py-1 text-xs uppercase tracking-[0.3em] text-muted-foreground">
                            {{ course.chapters }} chapters
                        </span>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                        <span>Score</span>
                        <span class="font-semibold text-foreground">
                            <template v-if="course.total_answers !== null && course.correct_answers !== null">
                                {{ course.correct_answers }} / {{ course.total_answers }}
                            </template>
                            <template v-else>
                                {{ course.score ?? '—' }}
                            </template>
                        </span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-sm text-muted-foreground">
                        <span>Final Score</span>
                        <span class="font-semibold text-foreground">
                            <template v-if="course.final_score !== null">{{ course.final_score }}%</template>
                            <template v-else>—</template>
                        </span>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                        <span class="rounded-full bg-muted px-3 py-1 uppercase tracking-[0.3em] text-muted-foreground">
                            {{ course.completed_chapters }} / {{ course.chapters }} chapters
                        </span>
                        <span class="rounded-full bg-muted px-3 py-1 uppercase tracking-[0.3em] text-muted-foreground">
                            {{ course.completed_topics }} topics done
                        </span>
                    </div>
                    <div class="mt-4">
                        <Link
                            :href="`/courses/${encodeURIComponent(course.slug)}`"
                            class="text-xs font-semibold uppercase tracking-[0.3em] text-primary"
                        >
                            Open Course
                        </Link>
                    </div>
                </article>
            </section>

            <section v-else class="rounded-2xl border border-dashed border-border bg-card p-8 text-center">
                <p class="text-sm text-muted-foreground">No courses assigned yet.</p>
            </section>
        </div>
    </AppLayout>
</template>

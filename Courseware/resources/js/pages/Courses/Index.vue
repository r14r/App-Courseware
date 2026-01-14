<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type CourseSummary = {
    slug: string;
    title: string;
    description: string;
    id: string;
};

const courses = ref<CourseSummary[]>([]);
const isLoading = ref(true);

async function fetchJson<T>(path: string): Promise<T | null> {
    try {
        const response = await fetch(path, {
            headers: {
                Accept: 'application/json',
            },
        });
        if (!response.ok) {
            return null;
        }
        return (await response.json()) as T;
    } catch {
        return null;
    }
}

function titleFromSlug(slug: string): string {
    return slug
        .replace(/^[0-9]+-/, '')
        .replace(/-/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function handleImageError(event: Event, title: string): void {
    const target = event.target as HTMLImageElement | null;
    if (!target) {
        return;
    }
    target.onerror = null;
    target.src = `https://placehold.co/600x350?text=${encodeURIComponent(title)}`;
}

async function loadCourses(): Promise<void> {
    isLoading.value = true;
    const index = await fetchJson<Array<string | { slug?: string }>>('/data/courses/index.json');

    if (index) {
        console.log('Files loaded:', index);
    }

    if (!index || !Array.isArray(index)) {
        courses.value = [];
        isLoading.value = false;
        return;
    }

    const list: CourseSummary[] = [];

    for (const entry of index) {
        const slug = typeof entry === 'string' ? entry : entry.slug || '';
        if (!slug) {
            continue;
        }
        const course = await fetchJson<{ title?: string; description?: string; id?: string }>(
            `/data/courses/${slug}/course.json`,
        );
        if (!course) {
            continue;
        }
        list.push({
            slug,
            title: course.title || titleFromSlug(slug),
            description: course.description || '',
            id: course.id || slug,
        });
    }

    courses.value = list;
    console.log('Courses found:', list);
    isLoading.value = false;
}

onMounted(() => {
    loadCourses();
});
</script>

<template>
    <Head title="Course Library" />

    <div class="min-h-screen bg-gradient-to-br from-amber-50 via-white to-sky-50">
        <header class="border-b border-black/10 bg-white/80 backdrop-blur">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                <Link href="/" class="text-lg font-semibold tracking-tight text-slate-900">
                    Courseware
                </Link>
                <div class="text-xs uppercase tracking-[0.25em] text-slate-500">Static to Laravel</div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-6 py-12">
            <section class="mb-10">
                <p class="text-sm font-medium uppercase tracking-[0.4em] text-slate-500">Course Library</p>
                <div class="mt-3 flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 md:text-5xl">
                            Learn at your pace.
                        </h1>
                        <p class="mt-3 max-w-2xl text-base text-slate-600">
                            Explore curated learning paths, open a course, and dive straight into hands-on lessons.
                        </p>
                    </div>
                    <div class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-500">
                        {{ courses.length }} Courses
                    </div>
                </div>
            </section>

            <section v-if="isLoading" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="n in 6" :key="n" class="h-72 animate-pulse rounded-3xl bg-white/60"></div>
            </section>

            <section v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="course in courses"
                    :key="course.slug"
                    class="group relative overflow-hidden rounded-3xl border border-white/80 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >
                    <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-black/60"></div>
                    <img
                        class="h-44 w-full object-cover"
                        :src="`/assets/${course.slug}.png`"
                        :alt="course.title"
                        @error="handleImageError($event, course.title)"
                    />
                    <div class="relative flex h-44 flex-col justify-between p-5">
                        <div>
                            <p class="text-xs uppercase tracking-[0.3em] text-white/70">ID {{ course.id }}</p>
                            <h2 class="mt-2 text-xl font-semibold text-white">
                                {{ course.title }}
                            </h2>
                            <p class="mt-2 text-sm text-white/80">
                                {{ course.description }}
                            </p>
                        </div>
                        <div class="flex items-center justify-between">
                            <Link
                                :href="`/courses/${encodeURIComponent(course.slug)}`"
                                class="rounded-full bg-white/90 px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-slate-900 transition hover:bg-white"
                            >
                                Open
                            </Link>
                            <span class="text-xs text-white/70">Updated</span>
                        </div>
                    </div>
                </article>
            </section>
        </main>
    </div>
</template>

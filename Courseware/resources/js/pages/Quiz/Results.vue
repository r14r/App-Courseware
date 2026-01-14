<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

type QuizResult = {
    id: string;
    question: string;
    options: string[];
    selectedIndex: number;
    correctIndex: number;
    explanation?: string;
};

type QuizPayload = {
    title: string;
    slug: string;
    chapter: string;
    total: number;
    score: number;
    results: QuizResult[];
};

const props = defineProps<{ slug: string; chapter: string }>();

const payload = ref<QuizPayload | null>(null);

const title = computed(() => payload.value?.title || 'Quiz Results');
const courseLink = computed(() => `/courses/${encodeURIComponent(props.slug)}`);
const quizLink = computed(
    () => `/courses/${encodeURIComponent(props.slug)}/chapters/${encodeURIComponent(props.chapter)}/quiz`,
);

const progressPercent = computed(() => {
    if (!payload.value?.total) {
        return 0;
    }
    return Math.round((payload.value.score / payload.value.total) * 100);
});

function isPerfect(): boolean {
    return Boolean(payload.value && payload.value.score === payload.value.total);
}

function isCorrect(result: QuizResult): boolean {
    return Number(result.selectedIndex) === Number(result.correctIndex);
}

function selectedLabel(result: QuizResult): string {
    return result.options?.[result.selectedIndex] || '—';
}

function correctLabel(result: QuizResult): string {
    return result.options?.[result.correctIndex] || '—';
}

onMounted(() => {
    const key = `quizResults:${props.slug}:${props.chapter}`;
    const stored = sessionStorage.getItem(key);
    if (!stored) {
        payload.value = null;
        return;
    }
    try {
        payload.value = JSON.parse(stored) as QuizPayload;
    } catch {
        payload.value = null;
    }
});
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-slate-950 text-slate-100">
        <header class="border-b border-white/10 bg-slate-950/90 backdrop-blur">
            <div class="mx-auto flex w-full max-w-4xl flex-wrap items-center justify-between gap-4 px-6 py-5">
                <div>
                    <p class="text-xs uppercase tracking-[0.35em] text-slate-500">Results</p>
                    <h1 class="mt-2 text-2xl font-semibold text-white">{{ title }}</h1>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="courseLink"
                        class="rounded-full border border-white/20 px-4 py-2 text-xs uppercase tracking-[0.3em] text-white/70"
                    >
                        Back to course
                    </Link>
                    <Link
                        :href="quizLink"
                        class="rounded-full bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-slate-900"
                    >
                        Retake quiz
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-4xl px-6 py-10">
            <div v-if="!payload" class="rounded-3xl border border-white/10 bg-slate-900/70 p-6">
                <p class="text-sm text-slate-300">No quiz results found.</p>
                <Link
                    :href="quizLink"
                    class="mt-4 inline-flex rounded-full bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-slate-900"
                >
                    Go to quiz
                </Link>
            </div>

            <div v-else class="space-y-6">
                <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-slate-900/70 p-6">
                    <div v-if="isPerfect()" class="confetti" aria-hidden="true"></div>
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.35em] text-slate-500">Score</p>
                            <div class="mt-2 text-3xl font-semibold text-white">
                                {{ payload.score }} / {{ payload.total }}
                            </div>
                        </div>
                        <div class="w-full max-w-xs">
                            <div class="h-3 w-full rounded-full bg-white/10">
                                <div
                                    class="h-3 rounded-full bg-gradient-to-r from-amber-400 via-orange-400 to-rose-400"
                                    :style="{ width: `${progressPercent}%` }"
                                ></div>
                            </div>
                            <p class="mt-2 text-xs uppercase tracking-[0.3em] text-slate-400">
                                {{ progressPercent }}% complete
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <article
                        v-for="(result, index) in payload.results"
                        :key="result.id"
                        class="rounded-3xl border border-white/10 bg-slate-900/70 p-5"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Question {{ index + 1 }}</p>
                                <h2 class="mt-2 text-base font-semibold text-white">
                                    {{ result.question }}
                                </h2>
                            </div>
                            <span
                                class="rounded-full px-3 py-1 text-xs uppercase tracking-[0.3em]"
                                :class="isCorrect(result) ? 'bg-emerald-400/20 text-emerald-200' : 'bg-rose-400/20 text-rose-200'"
                            >
                                {{ isCorrect(result) ? 'Correct' : 'Incorrect' }}
                            </span>
                        </div>
                        <div class="mt-4 text-sm text-slate-300">
                            <p>
                                Selected: <span class="font-semibold text-white">{{ selectedLabel(result) }}</span>
                            </p>
                            <p>
                                Correct: <span class="font-semibold text-emerald-200">{{ correctLabel(result) }}</span>
                            </p>
                            <p v-if="result.explanation" class="mt-2 text-slate-400">
                                {{ result.explanation }}
                            </p>
                        </div>
                    </article>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.confetti {
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(circle at 20% 20%, rgba(251, 191, 36, 0.4), transparent 35%),
        radial-gradient(circle at 80% 30%, rgba(244, 114, 182, 0.4), transparent 40%),
        radial-gradient(circle at 50% 70%, rgba(56, 189, 248, 0.35), transparent 45%);
    animation: float 3s ease-in-out infinite alternate;
    pointer-events: none;
}

@keyframes float {
    from {
        transform: translateY(0);
    }
    to {
        transform: translateY(-6px);
    }
}
</style>

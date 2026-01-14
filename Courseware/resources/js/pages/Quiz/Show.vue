<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';

type QuizQuestion = {
    id: string;
    type?: string;
    question: string;
    options: string[];
    correctIndex: number;
    explanation?: string;
};

type Quiz = {
    title: string;
    questions: QuizQuestion[];
};

type RawQuiz = {
    title?: string;
    questions?: QuizQuestion[];
    quiz?: {
        title?: string;
        questions?: Array<{
            id: string;
            type?: string;
            question: string;
            options?: string[];
            choices?: string[];
            correctIndex?: number;
            answerIndex?: number;
            explanation?: string;
        }>;
    };
};

const props = defineProps<{ slug: string; chapter: string }>();

const quiz = ref<Quiz | null>(null);
const title = computed(() => quiz.value?.title || 'Quiz');
const answers = reactive<Record<string, number>>({});

const courseLink = computed(() => `/courses/${encodeURIComponent(props.slug)}`);
const resultsLink = computed(
    () =>
        `/courses/${encodeURIComponent(props.slug)}/chapters/${encodeURIComponent(props.chapter)}/results`,
);

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

function normalizeQuiz(raw: RawQuiz | null): Quiz | null {
    if (!raw) {
        return null;
    }
    if (Array.isArray(raw.questions)) {
        return {
            title: raw.title || 'Quiz',
            questions: raw.questions.map((question) => ({
                ...question,
                options: question.options || [],
                correctIndex: question.correctIndex ?? 0,
            })),
        };
    }
    if (raw.quiz && Array.isArray(raw.quiz.questions)) {
        return {
            title: raw.title || raw.quiz.title || 'Quiz',
            questions: raw.quiz.questions.map((question) => ({
                id: question.id,
                type: question.type || 'single',
                question: question.question,
                options: question.options || question.choices || [],
                correctIndex: question.correctIndex ?? question.answerIndex ?? 0,
                explanation: question.explanation || '',
            })),
        };
    }
    return null;
}

function submitQuiz(): void {
    if (!quiz.value) {
        return;
    }
    const unanswered = quiz.value.questions.some((question) => answers[question.id] === undefined);
    if (unanswered) {
        window.alert('Please answer all questions');
        return;
    }

    let score = 0;
    const results = quiz.value.questions.map((question) => {
        const selectedIndex = Number(answers[question.id]);
        const correctIndex = Number(question.correctIndex);
        if (question.type === 'single' && selectedIndex === correctIndex) {
            score += 1;
        }
        return {
            id: question.id,
            question: question.question,
            options: question.options,
            selectedIndex,
            correctIndex,
            explanation: question.explanation || '',
        };
    });

    const payload = {
        title: quiz.value.title,
        slug: props.slug,
        chapter: props.chapter,
        total: quiz.value.questions.length,
        score,
        results,
    };

    const key = `quizResults:${props.slug}:${props.chapter}`;
    sessionStorage.setItem(key, JSON.stringify(payload));
    router.visit(resultsLink.value);
}

onMounted(async () => {
    let payload = await fetchJson<RawQuiz>(`/data/courses/${props.slug}/${props.chapter}/quiz.json`);
    if (!payload) {
        payload = await fetchJson<RawQuiz>(`/data/courses/${props.slug}/chapters/${props.chapter}/quiz.json`);
    }
    quiz.value = normalizeQuiz(payload);
});
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-slate-100 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-5">
                <div>
                    <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Quiz</p>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ title }}</h1>
                </div>
                <Link :href="courseLink" class="text-xs uppercase tracking-[0.35em] text-slate-500">
                    Back to course
                </Link>
            </div>
        </header>

        <main class="mx-auto w-full max-w-4xl px-6 py-10">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div v-if="!quiz" class="text-sm text-slate-500">Loading quiz...</div>

                <div v-else>
                    <form class="space-y-6">
                        <fieldset v-for="(question, qIndex) in quiz.questions" :key="question.id" class="space-y-3">
                            <legend class="text-base font-semibold text-slate-900">
                                {{ qIndex + 1 }}. {{ question.question }}
                            </legend>
                            <div class="space-y-2">
                                <label
                                    v-for="(option, optionIndex) in question.options"
                                    :key="optionIndex"
                                    class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-600 transition hover:border-slate-300"
                                >
                                    <input
                                        class="mt-1"
                                        type="radio"
                                        :name="question.id"
                                        :value="optionIndex"
                                        v-model.number="answers[question.id]"
                                    />
                                    <span>{{ option }}</span>
                                </label>
                            </div>
                        </fieldset>
                    </form>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <button
                            type="button"
                            class="rounded-full bg-slate-900 px-6 py-3 text-xs font-semibold uppercase tracking-[0.3em] text-white"
                            @click="submitQuiz"
                        >
                            Submit quiz
                        </button>
                        <Link
                            :href="courseLink"
                            class="rounded-full border border-slate-300 px-6 py-3 text-xs font-semibold uppercase tracking-[0.3em] text-slate-600"
                        >
                            Back to course
                        </Link>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

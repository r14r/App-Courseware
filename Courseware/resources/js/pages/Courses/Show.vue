<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';

type TopicEntry = {
    file: string;
    title: string;
};

type Chapter = {
    id: string;
    title: string;
    topics?: TopicEntry[];
};

type Course = {
    id?: string;
    title?: string;
    description?: string;
    chapters?: Chapter[];
};

type TopicContent = {
    title?: string;
    content?: string[];
    contentHtml?: string;
};

type Quiz = {
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

const props = defineProps<{ slug: string }>();

const course = ref<Course | null>(null);
const chapters = ref<Chapter[]>([]);
const topics = ref<TopicEntry[]>([]);
const selectedChapterIndex = ref(0);
const selectedTopicIndex = ref(0);
const chapterContentHtml = ref('');
const chapterTitle = ref('');
const topicCache = ref<Record<string, TopicContent | Quiz>>({});
const quizAvailable = ref(false);
const quizTitle = ref('Quiz');
const isLoading = ref(true);

const currentChapter = computed(() => chapters.value[selectedChapterIndex.value]);
const quizLink = computed(() => {
    if (!currentChapter.value) {
        return '';
    }
    return `/courses/${encodeURIComponent(props.slug)}/chapters/${encodeURIComponent(currentChapter.value.id)}/quiz`;
});

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

function titleFromFile(file: string): string {
    return file
        .replace(/^\d+-/, '')
        .replace(/\.json$/g, '')
        .replace(/-/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function normalizeTopics(entries: Array<string | { file?: string; title?: string }>): TopicEntry[] {
    return entries.map((entry) => {
        if (typeof entry === 'string') {
            return { file: entry, title: titleFromFile(entry) };
        }
        const file = entry.file || String(entry);
        const title = entry.title || titleFromFile(file);
        return { file, title };
    });
}

function renderContent(data: TopicContent | null): string {
    if (!data) {
        return '<p>No content.</p>';
    }
    if (Array.isArray(data.content)) {
        return data.content.filter(Boolean).join('\n\n') || '<p>No content.</p>';
    }
    if (data.contentHtml) {
        return data.contentHtml;
    }
    return '<p>No content.</p>';
}

async function loadCourse(): Promise<void> {
    const payload = await fetchJson<Course>(`/data/courses/${props.slug}/course.json`);
    if (!payload) {
        course.value = { title: 'Course not found', description: '' };
        chapters.value = [];
        return;
    }

    course.value = payload;
    console.log('Course loaded:', payload);

    const rawChapters = payload.chapters || [];
    const normalized: Chapter[] = [];

    for (const chapter of rawChapters) {
        const chapterCopy: Chapter = { ...chapter };
        let topicsIndex = await fetchJson<Array<string | { file?: string; title?: string }>>(
            `/data/courses/${props.slug}/${chapter.id}/topics.json`,
        );
        if (!topicsIndex) {
            topicsIndex = await fetchJson<Array<string | { file?: string; title?: string }>>(
                `/data/courses/${props.slug}/chapters/${chapter.id}/topics.json`,
            );
        }

        if (topicsIndex && Array.isArray(topicsIndex)) {
            const normalizedTopics = normalizeTopics(topicsIndex);
            const quizEntry = normalizedTopics.find((topic) => topic.file.toLowerCase().endsWith('quiz.json'));
            chapterCopy.topics = normalizedTopics.filter((topic) => !topic.file.toLowerCase().endsWith('quiz.json'));
            if (quizEntry) {
                chapterCopy.topics.push({
                    file: quizEntry.file,
                    title: quizEntry.title || 'Quiz',
                });
            }
            console.log('Topics found:', {
                chapter: chapterCopy.id,
                topics: chapterCopy.topics,
            });
        } else {
            chapterCopy.topics = [];
        }

        normalized.push(chapterCopy);
    }

    chapters.value = normalized;
}

async function loadChapter(index: number): Promise<void> {
    if (index < 0 || index >= chapters.value.length) {
        return;
    }
    selectedChapterIndex.value = index;
    const chapter = chapters.value[index];

    let topicsIndex = await fetchJson<Array<string | { file?: string; title?: string }>>(
        `/data/courses/${props.slug}/${chapter.id}/topics.json`,
    );
    if (!topicsIndex) {
        topicsIndex = await fetchJson<Array<string | { file?: string; title?: string }>>(
            `/data/courses/${props.slug}/chapters/${chapter.id}/topics.json`,
        );
    }

    if (topicsIndex && Array.isArray(topicsIndex) && topicsIndex.length) {
        topics.value = normalizeTopics(topicsIndex);
        selectedTopicIndex.value = 0;
        await loadTopic(selectedTopicIndex.value);
    } else {
        topics.value = [];
        selectedTopicIndex.value = 0;
        let content = await fetchJson<TopicContent>(`/data/courses/${props.slug}/${chapter.id}/content.json`);
        if (!content) {
            content = await fetchJson<TopicContent>(`/data/courses/${props.slug}/chapters/${chapter.id}/content.json`);
        }
        chapterContentHtml.value = renderContent(content);
        chapterTitle.value = chapter.title || '';
        await nextTick();
    }

    let quiz = await fetchJson<Quiz>(`/data/courses/${props.slug}/${chapter.id}/quiz.json`);
    if (!quiz) {
        quiz = await fetchJson<Quiz>(`/data/courses/${props.slug}/chapters/${chapter.id}/quiz.json`);
    }
    quizAvailable.value = Boolean(quiz && quiz.questions);
    quizTitle.value = quiz?.title || 'Quiz';
}

async function loadTopic(index: number): Promise<void> {
    const chapter = currentChapter.value;
    if (!chapter || index < 0 || index >= topics.value.length) {
        return;
    }
    selectedTopicIndex.value = index;
    const topicEntry = topics.value[index];
    const filename = topicEntry?.file;

    if (!filename) {
        return;
    }

    if (filename.toLowerCase().endsWith('quiz.json')) {
        goToQuiz();
        return;
    }

    if (topicCache.value[filename]) {
        const cached = topicCache.value[filename] as TopicContent;
        chapterContentHtml.value = renderContent(cached);
        chapterTitle.value = cached.title || topicEntry.title || chapter.title || '';
        await nextTick();
        return;
    }

    let data = await fetchJson<TopicContent>(`/data/courses/${props.slug}/${chapter.id}/${filename}`);
    if (!data) {
        data = await fetchJson<TopicContent>(`/data/courses/${props.slug}/chapters/${chapter.id}/${filename}`);
    }

    if (data) {
        topicCache.value[filename] = data;
        chapterContentHtml.value = renderContent(data);
        chapterTitle.value = data.title || topicEntry.title || chapter.title || '';
        await nextTick();
        return;
    }

    chapterContentHtml.value = '<p>No content.</p>';
    chapterTitle.value = chapter.title || '';
}

function nextChapter(): void {
    if (topics.value.length && selectedTopicIndex.value < topics.value.length - 1) {
        loadTopic(selectedTopicIndex.value + 1);
        return;
    }
    if (selectedChapterIndex.value < chapters.value.length - 1) {
        loadChapter(selectedChapterIndex.value + 1);
    }
}

function prevChapter(): void {
    if (topics.value.length && selectedTopicIndex.value > 0) {
        loadTopic(selectedTopicIndex.value - 1);
        return;
    }
    if (selectedChapterIndex.value > 0) {
        const prevIndex = selectedChapterIndex.value - 1;
        loadChapter(prevIndex);
        if (topics.value.length) {
            loadTopic(topics.value.length - 1);
        }
    }
}

function goToQuiz(): void {
    if (!quizLink.value) {
        return;
    }
    router.visit(quizLink.value);
}

onMounted(async () => {
    isLoading.value = true;
    await loadCourse();
    if (chapters.value.length) {
        await loadChapter(0);
    }
    isLoading.value = false;
});
</script>

<template>
    <Head :title="course?.title || 'Course'" />

    <div class="min-h-screen bg-slate-950 text-slate-100">
        <header class="border-b border-white/10 bg-slate-950/90 backdrop-blur">
            <div class="mx-auto flex w-full max-w-6xl flex-wrap items-center gap-4 px-6 py-6">
                <Link href="/" class="text-xs uppercase tracking-[0.35em] text-slate-400">All Courses</Link>
                <div class="flex flex-1 flex-wrap items-center gap-4">
                    <img
                        class="h-20 w-20 rounded-2xl border border-white/10 object-cover"
                        :src="`/assets/${props.slug}.png`"
                        :alt="course?.title || 'Course'"
                    />
                    <div>
                        <h1 class="text-xl font-semibold tracking-tight text-white">
                            {{ course?.title || 'Course' }}
                        </h1>
                        <p class="text-sm text-slate-400">
                            {{ course?.description }}
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-6 py-10">
            <div class="grid gap-8 lg:grid-cols-[280px_1fr]">
                <aside class="space-y-4">
                    <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-5">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-400">Chapters</h2>
                        <div class="mt-4 space-y-3">
                            <details
                                v-for="(chapter, index) in chapters"
                                :key="chapter.id"
                                class="group rounded-2xl border border-white/5 bg-slate-950/60 p-4"
                                :open="index === selectedChapterIndex"
                            >
                                <summary
                                    class="cursor-pointer list-none text-sm font-medium text-slate-200"
                                    @click="loadChapter(index)"
                                >
                                    {{ index + 1 }}. {{ chapter.title }}
                                </summary>
                                <ul v-if="chapter.topics?.length" class="mt-3 space-y-2 text-sm">
                                    <li v-for="(topic, tIndex) in chapter.topics" :key="topic.file">
                                        <button
                                            type="button"
                                            class="w-full rounded-full px-3 py-1 text-left transition"
                                            :class="
                                                selectedChapterIndex === index && selectedTopicIndex === tIndex
                                                    ? 'bg-white/10 text-white'
                                                    : 'text-slate-400 hover:text-white'
                                            "
                                            @click="loadChapter(index).then(() => loadTopic(tIndex))"
                                        >
                                            {{ topic.title }}
                                        </button>
                                    </li>
                                </ul>
                            </details>
                        </div>
                    </div>
                </aside>

                <section class="space-y-6">
                    <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800 p-6">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.35em] text-slate-500">Lesson</p>
                                <h2 class="mt-2 text-2xl font-semibold text-white">
                                    {{ chapterTitle || currentChapter?.title || '' }}
                                </h2>
                            </div>
                            <div class="text-xs uppercase tracking-[0.3em] text-slate-400">
                                {{ currentChapter?.id || 'Chapter' }}
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6">
                        <div v-if="isLoading" class="space-y-3">
                            <div class="h-5 w-2/3 animate-pulse rounded-full bg-white/10"></div>
                            <div class="h-4 w-full animate-pulse rounded-full bg-white/5"></div>
                            <div class="h-4 w-5/6 animate-pulse rounded-full bg-white/5"></div>
                        </div>
                        <article v-else class="course-content space-y-4" v-html="chapterContentHtml"></article>

                        <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="rounded-full border border-white/20 px-4 py-2 text-xs uppercase tracking-[0.3em] text-white/80 hover:text-white"
                                    @click="prevChapter"
                                >
                                    Prev
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border border-white/20 px-4 py-2 text-xs uppercase tracking-[0.3em] text-white/80 hover:text-white"
                                    @click="nextChapter"
                                >
                                    Next
                                </button>
                            </div>
                            <button
                                v-if="quizAvailable"
                                type="button"
                                class="rounded-full bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-slate-900"
                                @click="goToQuiz"
                            >
                                {{ quizTitle }}
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>

<style scoped>
.course-content :deep(p) {
    color: rgba(248, 250, 252, 0.85);
    line-height: 1.7;
}

.course-content :deep(h2),
.course-content :deep(h3) {
    color: #ffffff;
    margin-top: 1.5rem;
}

.course-content :deep(pre) {
    background: rgba(15, 23, 42, 0.8);
    border-radius: 16px;
    padding: 1rem;
    overflow-x: auto;
    border: 1px solid rgba(148, 163, 184, 0.2);
}

.course-content :deep(code) {
    color: #fbbf24;
}
</style>

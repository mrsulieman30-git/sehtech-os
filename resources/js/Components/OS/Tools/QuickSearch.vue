<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { PhMagnifyingGlass, PhX, PhArrowSquareOut, PhMinus, PhArrowsOut, PhClockCounterClockwise } from '@phosphor-icons/vue';

const props = defineProps<{ minimized: boolean }>();
const emit = defineEmits(['close', 'minimize', 'restore']);

const STORAGE_KEY = 'sehtech_search_history';
const query = ref('');
const searchInput = ref<HTMLInputElement | null>(null);
const searchHistory = ref<string[]>([]);

const search = () => {
    const q = query.value.trim();
    if (!q) return;
    if (!searchHistory.value.includes(q)) {
        searchHistory.value.unshift(q);
        if (searchHistory.value.length > 10) searchHistory.value.pop();
        localStorage.setItem(STORAGE_KEY, JSON.stringify(searchHistory.value));
    }
    window.open(`https://www.google.com/search?q=${encodeURIComponent(q)}`, '_blank');
    query.value = '';
};

const handleKeydown = (e: KeyboardEvent) => { if (e.key === 'Enter') search(); };

const quickLinks = [
    { label: 'Google', url: 'https://google.com' },
    { label: 'Stack Overflow', url: 'https://stackoverflow.com' },
    { label: 'GitHub', url: 'https://github.com' },
    { label: 'MDN Docs', url: 'https://developer.mozilla.org' },
    { label: 'ChatGPT', url: 'https://chatgpt.com' },
    { label: 'YouTube', url: 'https://youtube.com' },
];

// Drag
const pos = ref({ x: window.innerWidth / 2 - 200, y: 100 });
const isDragging = ref(false);
const dragOffset = ref({ x: 0, y: 0 });
const onMouseDown = (e: MouseEvent) => { isDragging.value = true; dragOffset.value = { x: e.clientX - pos.value.x, y: e.clientY - pos.value.y }; window.addEventListener('mousemove', onMouseMove); window.addEventListener('mouseup', onMouseUp); };
const onMouseMove = (e: MouseEvent) => { if (!isDragging.value) return; pos.value = { x: e.clientX - dragOffset.value.x, y: e.clientY - dragOffset.value.y }; };
const onMouseUp = () => { isDragging.value = false; window.removeEventListener('mousemove', onMouseMove); window.removeEventListener('mouseup', onMouseUp); };

// Resize
const size = ref({ w: 400, h: 280 });
const onResizeDown = (e: MouseEvent) => {
    e.preventDefault(); e.stopPropagation();
    const startX = e.clientX, startY = e.clientY, startW = size.value.w, startH = size.value.h;
    const doMove = (ev: MouseEvent) => { size.value = { w: Math.max(300, startW + ev.clientX - startX), h: Math.max(200, startH + ev.clientY - startY) }; };
    const doUp = () => { window.removeEventListener('mousemove', doMove); window.removeEventListener('mouseup', doUp); };
    window.addEventListener('mousemove', doMove); window.addEventListener('mouseup', doUp);
};

// Persist position & history
const POS_KEY = 'sehtech_search_pos';
onMounted(() => {
    try { const p = JSON.parse(localStorage.getItem(POS_KEY)!); if (p) pos.value = p; } catch (_) {}
    try { const h = JSON.parse(localStorage.getItem(STORAGE_KEY)!); if (h) searchHistory.value = h; } catch (_) {}
    nextTick(() => searchInput.value?.focus());
});
watch(pos, (v) => localStorage.setItem(POS_KEY, JSON.stringify(v)), { deep: true });

onUnmounted(() => { window.removeEventListener('mousemove', onMouseMove); window.removeEventListener('mouseup', onMouseUp); });
</script>

<template>
    <div 
        class="fixed z-[9990] rounded-2xl overflow-hidden shadow-2xl border border-white/10 select-none flex flex-col transition-all duration-200"
        :style="{ left: pos.x + 'px', top: pos.y + 'px', width: (minimized ? 240 : size.w) + 'px', height: minimized ? 'auto' : size.h + 'px' }"
        style="background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(24px);"
    >
        <!-- Header -->
        <div @mousedown="onMouseDown" class="h-10 flex items-center justify-between px-4 cursor-move border-b border-white/5 shrink-0">
            <div class="flex items-center gap-2 text-white/80 text-[12px] font-bold">
                <PhMagnifyingGlass :size="14" weight="bold" class="text-blue-400" />
                Quick Search
            </div>
            <div class="flex items-center gap-0.5">
                <button @click="minimized ? $emit('restore') : $emit('minimize')" class="w-5 h-5 flex items-center justify-center rounded-full hover:bg-white/10 text-white/30 hover:text-white/80 transition-colors bg-transparent border-0 cursor-pointer">
                    <PhMinus v-if="!minimized" :size="9" weight="bold" />
                    <PhArrowsOut v-else :size="9" weight="bold" />
                </button>
                <button @click="$emit('close')" class="w-5 h-5 flex items-center justify-center rounded-full hover:bg-red-500/20 text-white/30 hover:text-red-400 transition-colors bg-transparent border-0 cursor-pointer">
                    <PhX :size="9" weight="bold" />
                </button>
            </div>
        </div>

        <template v-if="!minimized">
            <!-- Search Input -->
            <div class="p-4 shrink-0">
                <div class="flex items-center gap-2 bg-white/5 rounded-xl px-4 py-2.5 border border-white/10 focus-within:border-blue-500/50 transition-colors">
                    <PhMagnifyingGlass :size="16" class="text-white/30 shrink-0" />
                    <input ref="searchInput" v-model="query" @keydown="handleKeydown" type="text" placeholder="Search Google..." class="flex-1 bg-transparent border-0 outline-none text-white text-[13px] placeholder:text-white/20" />
                    <button @click="search" class="w-7 h-7 rounded-lg flex items-center justify-center bg-blue-500/80 hover:bg-blue-500 text-white transition-colors border-0 cursor-pointer shrink-0">
                        <PhArrowSquareOut :size="13" weight="bold" />
                    </button>
                </div>
            </div>

            <!-- Recent + Quick Links -->
            <div class="flex-1 overflow-y-auto px-4 pb-4">
                <!-- Recent Searches -->
                <div v-if="searchHistory.length > 0" class="mb-4">
                    <div class="text-[9px] text-white/20 uppercase tracking-wider font-bold mb-2 flex items-center gap-1">
                        <PhClockCounterClockwise :size="9" /> Recent
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button v-for="h in searchHistory" :key="h" @click="query = h; search()" class="px-2.5 py-1 rounded-lg bg-white/[0.03] hover:bg-blue-500/20 text-white/40 hover:text-blue-300 text-[10px] font-medium transition-all border border-white/5 cursor-pointer">
                            {{ h }}
                        </button>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="text-[9px] text-white/20 uppercase tracking-wider font-bold mb-2">Quick Links</div>
                <div class="grid grid-cols-2 gap-2">
                    <a v-for="link in quickLinks" :key="link.url" :href="link.url" target="_blank"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/[0.03] hover:bg-white/[0.08] text-white/50 hover:text-white/80 text-[11px] font-medium transition-all no-underline border border-white/5">
                        <PhArrowSquareOut :size="10" /> {{ link.label }}
                    </a>
                </div>
            </div>

            <!-- Resize Handle -->
            <div @mousedown="onResizeDown" class="absolute bottom-0 right-0 w-4 h-4 cursor-nwse-resize flex items-end justify-end p-0.5 opacity-30 hover:opacity-60 transition-opacity">
                <svg width="8" height="8" viewBox="0 0 8 8"><path d="M7 1v6H1" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
        </template>
    </div>
</template>

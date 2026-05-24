<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { PhNotepad, PhX, PhTextBolder, PhTextItalic, PhCode, PhLink, PhTrash, PhMinus, PhArrowsOut } from '@phosphor-icons/vue';

const props = defineProps<{ minimized: boolean }>();
const emit = defineEmits(['close', 'minimize', 'restore']);

const STORAGE_KEY = 'sehtech_floating_notes';
const content = ref('');
const isSaving = ref(false);
const lastSaved = ref('');
let saveTimeout: ReturnType<typeof setTimeout> | null = null;

onMounted(() => {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
        try { const data = JSON.parse(saved); content.value = data.content || ''; lastSaved.value = data.savedAt || ''; } catch (_) { content.value = saved; }
    }
});

watch(content, () => {
    if (saveTimeout) clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
        isSaving.value = true;
        const now = new Date().toLocaleTimeString();
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ content: content.value, savedAt: now }));
        lastSaved.value = now;
        setTimeout(() => { isSaving.value = false; }, 500);
    }, 800);
});

const editorRef = ref<HTMLDivElement | null>(null);
const execCommand = (command: string, value?: string) => { document.execCommand(command, false, value); editorRef.value?.focus(); };
const insertBold = () => execCommand('bold');
const insertItalic = () => execCommand('italic');
const insertCode = () => {
    const sel = window.getSelection();
    if (sel && sel.rangeCount > 0) {
        const range = sel.getRangeAt(0);
        const code = document.createElement('code');
        code.style.cssText = 'background: rgba(255,255,255,0.08); padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 12px; color: #F59E0B;';
        range.surroundContents(code);
    }
};
const insertLink = () => { const url = prompt('Enter URL:'); if (url) execCommand('createLink', url); };
const clearNotes = () => {
    if (confirm('Clear all notes?')) { content.value = ''; if (editorRef.value) editorRef.value.innerHTML = ''; localStorage.removeItem(STORAGE_KEY); lastSaved.value = ''; }
};
const onInput = () => { if (editorRef.value) content.value = editorRef.value.innerHTML; };
const onPaste = (e: ClipboardEvent) => {
    const items = e.clipboardData?.items;
    if (items) {
        for (const item of items) {
            if (item.type.startsWith('image/')) {
                e.preventDefault();
                const file = item.getAsFile();
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        const img = document.createElement('img');
                        img.src = ev.target?.result as string;
                        img.style.cssText = 'max-width: 100%; border-radius: 8px; margin: 8px 0;';
                        const sel = window.getSelection();
                        if (sel && sel.rangeCount > 0) { const range = sel.getRangeAt(0); range.deleteContents(); range.insertNode(img); }
                    };
                    reader.readAsDataURL(file);
                }
                return;
            }
        }
    }
};

onMounted(() => { setTimeout(() => { if (editorRef.value && content.value) editorRef.value.innerHTML = content.value; }, 50); });

// Drag
const pos = ref({ x: 120, y: 120 });
const isDragging = ref(false);
const dragOffset = ref({ x: 0, y: 0 });
const onMouseDown = (e: MouseEvent) => { isDragging.value = true; dragOffset.value = { x: e.clientX - pos.value.x, y: e.clientY - pos.value.y }; window.addEventListener('mousemove', onMouseMove); window.addEventListener('mouseup', onMouseUp); };
const onMouseMove = (e: MouseEvent) => { if (!isDragging.value) return; pos.value = { x: e.clientX - dragOffset.value.x, y: e.clientY - dragOffset.value.y }; };
const onMouseUp = () => { isDragging.value = false; window.removeEventListener('mousemove', onMouseMove); window.removeEventListener('mouseup', onMouseUp); };

// Resize
const size = ref({ w: 360, h: 420 });
const onResizeDown = (e: MouseEvent) => {
    e.preventDefault(); e.stopPropagation();
    const startX = e.clientX, startY = e.clientY, startW = size.value.w, startH = size.value.h;
    const doMove = (ev: MouseEvent) => { size.value = { w: Math.max(280, startW + ev.clientX - startX), h: Math.max(250, startH + ev.clientY - startY) }; };
    const doUp = () => { window.removeEventListener('mousemove', doMove); window.removeEventListener('mouseup', doUp); };
    window.addEventListener('mousemove', doMove); window.addEventListener('mouseup', doUp);
};

// Persist position
const POS_KEY = 'sehtech_notes_pos';
onMounted(() => { try { const p = JSON.parse(localStorage.getItem(POS_KEY)!); if (p) pos.value = p; } catch (_) {} });
watch(pos, (v) => localStorage.setItem(POS_KEY, JSON.stringify(v)), { deep: true });

onUnmounted(() => {
    if (saveTimeout) clearTimeout(saveTimeout);
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseup', onMouseUp);
});
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
                <PhNotepad :size="14" weight="fill" class="text-violet-400" />
                Notes
                <span v-if="isSaving" class="text-[9px] text-amber-400/60 font-normal ml-1">saving...</span>
                <span v-else-if="lastSaved" class="text-[9px] text-white/20 font-normal ml-1">saved {{ lastSaved }}</span>
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
            <!-- Toolbar -->
            <div class="flex items-center gap-1 px-3 py-1.5 border-b border-white/5 shrink-0">
                <button @click="insertBold" class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-white/10 text-white/40 hover:text-white/80 transition-colors bg-transparent border-0 cursor-pointer" title="Bold">
                    <PhTextBolder :size="14" />
                </button>
                <button @click="insertItalic" class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-white/10 text-white/40 hover:text-white/80 transition-colors bg-transparent border-0 cursor-pointer" title="Italic">
                    <PhTextItalic :size="14" />
                </button>
                <button @click="insertCode" class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-white/10 text-white/40 hover:text-white/80 transition-colors bg-transparent border-0 cursor-pointer" title="Code">
                    <PhCode :size="14" />
                </button>
                <button @click="insertLink" class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-white/10 text-white/40 hover:text-white/80 transition-colors bg-transparent border-0 cursor-pointer" title="Insert Link">
                    <PhLink :size="14" />
                </button>
                <div class="flex-1"></div>
                <button @click="clearNotes" class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-red-500/20 text-white/20 hover:text-red-400 transition-colors bg-transparent border-0 cursor-pointer" title="Clear All">
                    <PhTrash :size="13" />
                </button>
            </div>

            <!-- Editor -->
            <div 
                ref="editorRef" contenteditable="true" @input="onInput" @paste="onPaste"
                class="flex-1 overflow-y-auto px-4 py-3 text-white/80 text-[13px] leading-relaxed outline-none"
                style="word-break: break-word;" data-placeholder="Start typing your notes..."
            ></div>

            <!-- Resize Handle -->
            <div @mousedown="onResizeDown" class="absolute bottom-0 right-0 w-4 h-4 cursor-nwse-resize flex items-end justify-end p-0.5 opacity-30 hover:opacity-60 transition-opacity">
                <svg width="8" height="8" viewBox="0 0 8 8"><path d="M7 1v6H1" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
        </template>
    </div>
</template>

<style scoped>
[contenteditable]:empty::before { content: attr(data-placeholder); color: rgba(255,255,255,0.15); pointer-events: none; }
[contenteditable] a { color: #60A5FA; text-decoration: underline; }
</style>

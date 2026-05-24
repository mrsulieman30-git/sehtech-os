<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { PhTimer, PhPlay, PhPause, PhArrowCounterClockwise, PhX, PhCoffee, PhBrain, PhMinus, PhArrowsOut } from '@phosphor-icons/vue';

const props = defineProps<{ minimized: boolean }>();
const emit = defineEmits(['close', 'minimize', 'restore']);

const STORAGE_KEY = 'sehtech_pomodoro_state';
const WORK_MINUTES = 25;
const BREAK_MINUTES = 5;
const LONG_BREAK_MINUTES = 15;

type Mode = 'work' | 'break' | 'longBreak';
const mode = ref<Mode>('work');
const secondsLeft = ref(WORK_MINUTES * 60);
const isRunning = ref(false);
const sessionsCompleted = ref(0);
const targetEndTime = ref<number | null>(null); // ms timestamp when timer should end
let interval: ReturnType<typeof setInterval> | null = null;

const totalForMode = (m: Mode) => m === 'work' ? WORK_MINUTES * 60 : m === 'break' ? BREAK_MINUTES * 60 : LONG_BREAK_MINUTES * 60;
const totalSeconds = computed(() => totalForMode(mode.value));
const progress = computed(() => ((totalSeconds.value - secondsLeft.value) / totalSeconds.value) * 100);
const displayTime = computed(() => {
    const m = Math.floor(secondsLeft.value / 60);
    const s = secondsLeft.value % 60;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});
const circumference = 2 * Math.PI * 54;
const dashOffset = computed(() => circumference - (progress.value / 100) * circumference);
const modeColor = computed(() => mode.value === 'work' ? '#854D0E' : mode.value === 'break' ? '#059669' : '#7C3AED');

const saveState = () => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
        mode: mode.value, isRunning: isRunning.value, targetEndTime: targetEndTime.value,
        sessionsCompleted: sessionsCompleted.value, secondsLeft: secondsLeft.value
    }));
};

const playBeep = () => {
    try {
        const ctx = new (window.AudioContext || (window as any).webkitAudioContext)();
        const play = (freq: number, delay: number) => {
            const osc = ctx.createOscillator(); const gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.frequency.value = freq; osc.type = 'sine'; gain.gain.value = 0.3;
            osc.start(ctx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + 0.6);
            osc.stop(ctx.currentTime + delay + 0.6);
        };
        play(800, 0); play(1000, 0.3); play(1200, 0.6);
    } catch (_) {}
};

const tick = () => {
    if (targetEndTime.value) {
        const remaining = Math.max(0, Math.ceil((targetEndTime.value - Date.now()) / 1000));
        secondsLeft.value = remaining;
        if (remaining <= 0) {
            pause();
            playBeep();
            if (mode.value === 'work') {
                sessionsCompleted.value++;
                switchMode(sessionsCompleted.value % 4 === 0 ? 'longBreak' : 'break');
            } else {
                switchMode('work');
            }
        }
    }
    saveState();
};

const start = () => {
    if (isRunning.value) return;
    isRunning.value = true;
    targetEndTime.value = Date.now() + secondsLeft.value * 1000;
    interval = setInterval(tick, 250);
    saveState();
};

const pause = () => {
    isRunning.value = false;
    targetEndTime.value = null;
    if (interval) { clearInterval(interval); interval = null; }
    saveState();
};

const reset = () => {
    pause();
    secondsLeft.value = totalSeconds.value;
    saveState();
};

const switchMode = (m: Mode) => {
    pause();
    mode.value = m;
    secondsLeft.value = totalForMode(m);
    saveState();
};

// Restore state from localStorage on mount
onMounted(() => {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
            const s = JSON.parse(raw);
            mode.value = s.mode || 'work';
            sessionsCompleted.value = s.sessionsCompleted || 0;
            if (s.isRunning && s.targetEndTime) {
                const remaining = Math.max(0, Math.ceil((s.targetEndTime - Date.now()) / 1000));
                if (remaining > 0) {
                    secondsLeft.value = remaining;
                    targetEndTime.value = s.targetEndTime;
                    isRunning.value = true;
                    interval = setInterval(tick, 250);
                } else {
                    secondsLeft.value = 0;
                    playBeep();
                }
            } else {
                secondsLeft.value = s.secondsLeft ?? totalForMode(s.mode || 'work');
            }
        }
    } catch (_) {}
});

// Drag
const pos = ref({ x: window.innerWidth - 340, y: 100 });
const isDragging = ref(false);
const dragOffset = ref({ x: 0, y: 0 });
const onMouseDown = (e: MouseEvent) => { isDragging.value = true; dragOffset.value = { x: e.clientX - pos.value.x, y: e.clientY - pos.value.y }; window.addEventListener('mousemove', onMouseMove); window.addEventListener('mouseup', onMouseUp); };
const onMouseMove = (e: MouseEvent) => { if (!isDragging.value) return; pos.value = { x: e.clientX - dragOffset.value.x, y: e.clientY - dragOffset.value.y }; };
const onMouseUp = () => { isDragging.value = false; window.removeEventListener('mousemove', onMouseMove); window.removeEventListener('mouseup', onMouseUp); };

// Resize
const size = ref({ w: 280, h: 360 });
const isResizing = ref(false);
const onResizeDown = (e: MouseEvent) => {
    e.preventDefault(); e.stopPropagation();
    isResizing.value = true;
    const startX = e.clientX, startY = e.clientY;
    const startW = size.value.w, startH = size.value.h;
    const doMove = (ev: MouseEvent) => { size.value = { w: Math.max(240, startW + ev.clientX - startX), h: Math.max(280, startH + ev.clientY - startY) }; };
    const doUp = () => { isResizing.value = false; window.removeEventListener('mousemove', doMove); window.removeEventListener('mouseup', doUp); };
    window.addEventListener('mousemove', doMove);
    window.addEventListener('mouseup', doUp);
};

// Persist position
const POS_KEY = 'sehtech_pomodoro_pos';
onMounted(() => { try { const p = JSON.parse(localStorage.getItem(POS_KEY)!); if (p) pos.value = p; } catch (_) {} });
watch(pos, (v) => localStorage.setItem(POS_KEY, JSON.stringify(v)), { deep: true });

onUnmounted(() => {
    if (interval) clearInterval(interval);
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
                <PhTimer :size="14" weight="fill" :style="{ color: modeColor }" />
                Pomodoro
                <span v-if="isRunning" class="text-[9px] font-mono px-1.5 py-0.5 rounded bg-white/5" :style="{ color: modeColor }">{{ displayTime }}</span>
            </div>
            <div class="flex items-center gap-0.5">
                <button @click="minimized ? $emit('restore') : $emit('minimize')" class="w-5 h-5 flex items-center justify-center rounded-full hover:bg-white/10 text-white/30 hover:text-white/80 transition-colors bg-transparent border-0 cursor-pointer" :title="minimized ? 'Restore' : 'Minimize'">
                    <PhMinus v-if="!minimized" :size="9" weight="bold" />
                    <PhArrowsOut v-else :size="9" weight="bold" />
                </button>
                <button @click="$emit('close')" class="w-5 h-5 flex items-center justify-center rounded-full hover:bg-red-500/20 text-white/30 hover:text-red-400 transition-colors bg-transparent border-0 cursor-pointer">
                    <PhX :size="9" weight="bold" />
                </button>
            </div>
        </div>

        <template v-if="!minimized">
            <!-- Mode Tabs -->
            <div class="flex items-center gap-1 px-4 pt-3 pb-1 shrink-0">
                <button @click="switchMode('work')" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all border-0 cursor-pointer" :class="mode === 'work' ? 'bg-amber-500/20 text-amber-400' : 'bg-transparent text-white/30 hover:text-white/60'">
                    <PhBrain :size="10" /> Focus
                </button>
                <button @click="switchMode('break')" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all border-0 cursor-pointer" :class="mode === 'break' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-transparent text-white/30 hover:text-white/60'">
                    <PhCoffee :size="10" /> Break
                </button>
                <button @click="switchMode('longBreak')" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all border-0 cursor-pointer" :class="mode === 'longBreak' ? 'bg-violet-500/20 text-violet-400' : 'bg-transparent text-white/30 hover:text-white/60'">
                    Long
                </button>
            </div>

            <!-- Timer Ring -->
            <div class="flex-1 flex flex-col items-center justify-center min-h-0">
                <div class="relative" :style="{ width: Math.min(size.w - 80, 160) + 'px', height: Math.min(size.w - 80, 160) + 'px' }">
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="6" />
                        <circle cx="60" cy="60" r="54" fill="none" :stroke="modeColor" stroke-width="6" stroke-linecap="round" :stroke-dasharray="circumference" :stroke-dashoffset="dashOffset" style="transition: stroke-dashoffset 0.5s ease;" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-[28px] font-black text-white tracking-tight" style="font-variant-numeric: tabular-nums;">{{ displayTime }}</span>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-white/30 mt-0.5">{{ mode === 'work' ? 'Focus Time' : mode === 'break' ? 'Short Break' : 'Long Break' }}</span>
                    </div>
                </div>
            </div>

            <!-- Controls -->
            <div class="flex items-center justify-center gap-3 pb-4 shrink-0">
                <button @click="reset" class="w-9 h-9 rounded-full flex items-center justify-center bg-white/5 hover:bg-white/10 text-white/40 hover:text-white/80 transition-all border-0 cursor-pointer">
                    <PhArrowCounterClockwise :size="16" />
                </button>
                <button @click="isRunning ? pause() : start()" class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold transition-all border-0 cursor-pointer shadow-lg" :style="{ background: modeColor }">
                    <PhPause v-if="isRunning" :size="20" weight="fill" />
                    <PhPlay v-else :size="20" weight="fill" />
                </button>
                <div class="w-9 h-9 rounded-full flex items-center justify-center bg-white/5 text-white/25 text-[11px] font-bold">
                    {{ sessionsCompleted }}
                </div>
            </div>

            <!-- Resize Handle -->
            <div @mousedown="onResizeDown" class="absolute bottom-0 right-0 w-4 h-4 cursor-nwse-resize flex items-end justify-end p-0.5 opacity-30 hover:opacity-60 transition-opacity">
                <svg width="8" height="8" viewBox="0 0 8 8"><path d="M7 1v6H1" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { PhCalculator, PhX, PhClockCounterClockwise, PhMinus, PhArrowsOut } from '@phosphor-icons/vue';

const props = defineProps<{ minimized: boolean }>();
const emit = defineEmits(['close', 'minimize', 'restore']);

const STORAGE_KEY = 'sehtech_calc_state';

const display = ref('0');
const previousValue = ref<number | null>(null);
const operator = ref<string | null>(null);
const waitingForOperand = ref(false);
const history = ref<string[]>([]);
const showHistory = ref(false);

const inputDigit = (digit: string) => {
    if (waitingForOperand.value) { display.value = digit; waitingForOperand.value = false; }
    else { display.value = display.value === '0' ? digit : display.value + digit; }
};
const inputDecimal = () => {
    if (waitingForOperand.value) { display.value = '0.'; waitingForOperand.value = false; return; }
    if (!display.value.includes('.')) display.value += '.';
};
const performOperation = (nextOp: string) => {
    const currentValue = parseFloat(display.value);
    if (previousValue.value !== null && operator.value && !waitingForOperand.value) {
        let result = 0;
        switch (operator.value) {
            case '+': result = previousValue.value + currentValue; break;
            case '-': result = previousValue.value - currentValue; break;
            case '×': result = previousValue.value * currentValue; break;
            case '÷': result = currentValue !== 0 ? previousValue.value / currentValue : 0; break;
        }
        const expr = `${previousValue.value} ${operator.value} ${currentValue} = ${parseFloat(result.toFixed(10))}`;
        history.value.unshift(expr);
        if (history.value.length > 12) history.value.pop();
        display.value = String(parseFloat(result.toFixed(10)));
        previousValue.value = result;
    } else { previousValue.value = currentValue; }
    waitingForOperand.value = true;
    operator.value = nextOp === '=' ? null : nextOp;
    saveState();
};
const clearAll = () => { display.value = '0'; previousValue.value = null; operator.value = null; waitingForOperand.value = false; };
const toggleSign = () => { display.value = String(-parseFloat(display.value)); };
const percentage = () => { display.value = String(parseFloat(display.value) / 100); };

const buttons = [['C', '±', '%', '÷'], ['7', '8', '9', '×'], ['4', '5', '6', '-'], ['1', '2', '3', '+'], ['0', '.', '=']];
const handleButton = (btn: string) => {
    if (btn === 'C') clearAll(); else if (btn === '±') toggleSign(); else if (btn === '%') percentage();
    else if (btn === '.') inputDecimal(); else if (['+', '-', '×', '÷', '='].includes(btn)) performOperation(btn);
    else inputDigit(btn);
};
const isOperator = (btn: string) => ['+', '-', '×', '÷', '='].includes(btn);
const isActive = (btn: string) => operator.value === btn && waitingForOperand.value;

// Persistence
const saveState = () => { localStorage.setItem(STORAGE_KEY, JSON.stringify({ history: history.value })); };
onMounted(() => { try { const s = JSON.parse(localStorage.getItem(STORAGE_KEY)!); if (s?.history) history.value = s.history; } catch (_) {} });

// Drag
const pos = ref({ x: window.innerWidth - 340, y: 360 });
const isDragging = ref(false);
const dragOffset = ref({ x: 0, y: 0 });
const onMouseDown = (e: MouseEvent) => { isDragging.value = true; dragOffset.value = { x: e.clientX - pos.value.x, y: e.clientY - pos.value.y }; window.addEventListener('mousemove', onMouseMove); window.addEventListener('mouseup', onMouseUp); };
const onMouseMove = (e: MouseEvent) => { if (!isDragging.value) return; pos.value = { x: e.clientX - dragOffset.value.x, y: e.clientY - dragOffset.value.y }; };
const onMouseUp = () => { isDragging.value = false; window.removeEventListener('mousemove', onMouseMove); window.removeEventListener('mouseup', onMouseUp); };

// Resize
const size = ref({ w: 260, h: 420 });
const onResizeDown = (e: MouseEvent) => {
    e.preventDefault(); e.stopPropagation();
    const startX = e.clientX, startY = e.clientY, startW = size.value.w, startH = size.value.h;
    const doMove = (ev: MouseEvent) => { size.value = { w: Math.max(220, startW + ev.clientX - startX), h: Math.max(340, startH + ev.clientY - startY) }; };
    const doUp = () => { window.removeEventListener('mousemove', doMove); window.removeEventListener('mouseup', doUp); };
    window.addEventListener('mousemove', doMove); window.addEventListener('mouseup', doUp);
};

// Persist position
const POS_KEY = 'sehtech_calc_pos';
onMounted(() => { try { const p = JSON.parse(localStorage.getItem(POS_KEY)!); if (p) pos.value = p; } catch (_) {} });
watch(pos, (v) => localStorage.setItem(POS_KEY, JSON.stringify(v)), { deep: true });

onUnmounted(() => { window.removeEventListener('mousemove', onMouseMove); window.removeEventListener('mouseup', onMouseUp); });
</script>

<template>
    <div 
        class="fixed z-[9990] rounded-2xl overflow-hidden shadow-2xl border border-white/10 select-none flex flex-col transition-all duration-200"
        :style="{ left: pos.x + 'px', top: pos.y + 'px', width: (minimized ? 220 : size.w) + 'px', height: minimized ? 'auto' : size.h + 'px' }"
        style="background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(24px);"
    >
        <!-- Header -->
        <div @mousedown="onMouseDown" class="h-10 flex items-center justify-between px-4 cursor-move border-b border-white/5 shrink-0">
            <div class="flex items-center gap-2 text-white/80 text-[12px] font-bold">
                <PhCalculator :size="14" weight="fill" class="text-emerald-400" />
                Calculator
                <span v-if="minimized && display !== '0'" class="text-[10px] font-mono text-white/40 ml-1">{{ display }}</span>
            </div>
            <div class="flex items-center gap-0.5">
                <button @click="showHistory = !showHistory" class="w-5 h-5 flex items-center justify-center rounded-full hover:bg-white/10 text-white/30 hover:text-white/80 transition-colors bg-transparent border-0 cursor-pointer" v-if="!minimized">
                    <PhClockCounterClockwise :size="10" />
                </button>
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
            <!-- History -->
            <div v-if="showHistory" class="px-4 py-2 bg-white/[0.02] border-b border-white/5 max-h-[120px] overflow-y-auto shrink-0">
                <div v-if="history.length === 0" class="text-white/20 text-[11px] text-center py-2">No history yet</div>
                <div v-for="(h, i) in history" :key="i" class="text-white/40 text-[11px] py-0.5 font-mono">{{ h }}</div>
            </div>

            <!-- Display -->
            <div class="px-4 py-3 shrink-0">
                <div class="text-white/30 text-[11px] h-4 font-mono">{{ previousValue !== null && operator ? `${previousValue} ${operator}` : '' }}</div>
                <div class="text-white text-[28px] font-light tracking-tight truncate" style="font-variant-numeric: tabular-nums;">{{ display }}</div>
            </div>

            <!-- Buttons -->
            <div class="px-3 pb-3 flex-1 grid gap-[6px] content-end">
                <div v-for="(row, ri) in buttons" :key="ri" class="grid gap-[6px]" :style="{ gridTemplateColumns: ri === 4 ? '2fr 1fr 1fr' : 'repeat(4, 1fr)' }">
                    <button v-for="btn in row" :key="btn" @click="handleButton(btn)"
                        class="h-10 rounded-xl text-[14px] font-semibold transition-all border-0 cursor-pointer active:scale-95"
                        :class="{ 'bg-white/5 text-white/80 hover:bg-white/10': !isOperator(btn) && !['C','±','%'].includes(btn), 'bg-amber-500/80 text-white hover:bg-amber-500': isOperator(btn), 'bg-amber-400 text-white': isActive(btn), 'bg-white/10 text-white/60 hover:bg-white/15': ['C','±','%'].includes(btn) }">
                        {{ btn }}
                    </button>
                </div>
            </div>

            <!-- Resize Handle -->
            <div @mousedown="onResizeDown" class="absolute bottom-0 right-0 w-4 h-4 cursor-nwse-resize flex items-end justify-end p-0.5 opacity-30 hover:opacity-60 transition-opacity">
                <svg width="8" height="8" viewBox="0 0 8 8"><path d="M7 1v6H1" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
        </template>
    </div>
</template>

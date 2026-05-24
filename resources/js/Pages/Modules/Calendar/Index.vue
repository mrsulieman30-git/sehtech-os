<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { 
    PhCalendarBlank, PhVideoCamera, PhUsers, PhListDashes, 
    PhClock, PhPlus, PhCaretLeft, PhCaretRight, PhMapPin
} from '@phosphor-icons/vue';
import dayjs from 'dayjs';

interface CalendarEvent {
    id: string;
    title: string;
    start: string;
    end: string;
    type: string;
    color: string;
    data: any;
}

const events = ref<CalendarEvent[]>([]);
const selectedDate = ref(dayjs());
const isScheduling = ref(false);

const newMeeting = ref({
    title: '',
    type: 'video',
    start_time: '',
    end_time: '',
    agenda: ''
});

const fetchEvents = async () => {
    try {
        const res = await axios.get('/api/calendar/events');
        events.value = res.data.events;
    } catch (error) {
        console.error('Failed to load events', error);
    }
};

// Generates an array of days for a simple month view logic
const calendarDays = computed(() => {
    const days = [];
    const startOfMonth = selectedDate.value.startOf('month');
    const endOfMonth = selectedDate.value.endOf('month');
    
    // Pad start
    const startPadding = startOfMonth.day(); // 0 is Sunday
    for (let i = startPadding - 1; i >= 0; i--) {
        days.push({ date: startOfMonth.subtract(i + 1, 'day'), isCurrentMonth: false });
    }
    
    // Current month
    for (let i = 0; i < endOfMonth.date(); i++) {
        days.push({ date: startOfMonth.add(i, 'day'), isCurrentMonth: true });
    }
    
    // Pad end
    const remaining = 42 - days.length; // 6 rows * 7 days
    for (let i = 1; i <= remaining; i++) {
        days.push({ date: endOfMonth.add(i, 'day'), isCurrentMonth: false });
    }
    
    return days;
});

const getEventsForDay = (date: dayjs.Dayjs) => {
    return events.value.filter(e => dayjs(e.start).isSame(date, 'day'));
};

const nextMonth = () => selectedDate.value = selectedDate.value.add(1, 'month');
const prevMonth = () => selectedDate.value = selectedDate.value.subtract(1, 'month');
const today = () => selectedDate.value = dayjs();

const scheduleMeeting = async () => {
    try {
        await axios.post('/api/calendar/meetings', newMeeting.value);
        isScheduling.value = false;
        fetchEvents();
        // Reset form
        newMeeting.value.title = '';
        newMeeting.value.agenda = '';
    } catch (e) {
        console.error('Failed to schedule', e);
    }
};

onMounted(() => fetchEvents());
</script>

<template>
    <div class="h-full flex flex-row bg-shell-panel text-text-primary overflow-hidden font-sans">
        
        <div class="w-[300px] flex-shrink-0 border-r border-shell-border bg-white flex flex-col h-full z-10">
            <div class="h-[56px] flex items-center justify-between px-6 border-b border-shell-border bg-white shrink-0">
                <div class="flex items-center gap-3">
                    <PhCalendarBlank :size="24" class="text-state-focus" weight="fill" />
                    <h2 class="text-[15px] font-bold text-text-primary tracking-wide">Calendar</h2>
                </div>
            </div>

            <div class="p-4 border-b border-shell-border bg-[#F8FAFC]">
                <button 
                    @click="isScheduling = !isScheduling"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-state-focus text-white text-[13px] font-bold rounded-btn hover:bg-blue-700 transition-colors shadow-sm"
                >
                    <PhPlus :size="16" weight="bold" /> Schedule Meeting
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-4">
                
                <div>
                    <h3 class="text-[11px] font-bold text-text-disabled uppercase tracking-wider mb-3">Event Types</h3>
                    <div class="flex flex-col gap-2 text-[13px] font-medium text-text-secondary">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <div class="w-3 h-3 rounded-full bg-[#2563EB]"></div> Meetings
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <div class="w-3 h-3 rounded-full bg-[#0F172A]"></div> Dev Tasks
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <div class="w-3 h-3 rounded-full bg-[#CA8A04]"></div> Invoices Due
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <div class="w-3 h-3 rounded-full bg-[#0891B2]"></div> Team Leaves
                        </label>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-shell-border">
                    <h3 class="text-[11px] font-bold text-text-disabled uppercase tracking-wider mb-3">Upcoming 7 Days</h3>
                    <div class="flex flex-col gap-3">
                        <div v-if="events.length === 0" class="text-[12px] text-text-disabled">No upcoming events.</div>
                        <div 
                            v-for="event in events.slice(0, 5)" 
                            :key="event.id"
                            class="p-3 bg-white border border-shell-border rounded-card shadow-sm hover:border-state-focus cursor-pointer transition-colors"
                        >
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: event.color }"></div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-text-secondary">{{ event.type }}</span>
                            </div>
                            <h4 class="text-[13px] font-bold text-text-primary leading-snug">{{ event.title }}</h4>
                            <div class="text-[11px] text-text-disabled mt-1.5 flex items-center gap-1">
                                <PhClock :size="12" /> {{ dayjs(event.start).format('MMM D, h:mm A') }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="flex-1 flex flex-col h-full bg-[#F8FAFC] overflow-hidden">
            
            <div class="h-[56px] bg-white border-b border-shell-border flex items-center px-6 shrink-0 justify-between">
                <div v-if="!isScheduling" class="flex items-center gap-4">
                    <h2 class="text-[18px] font-bold text-text-primary min-w-[150px]">{{ selectedDate.format('MMMM YYYY') }}</h2>
                    <div class="flex items-center bg-shell-panel border border-shell-border rounded-btn p-0.5">
                        <button @click="prevMonth" class="p-1 hover:bg-white rounded transition-colors"><PhCaretLeft :size="16" weight="bold"/></button>
                        <button @click="today" class="px-3 py-1 text-[12px] font-bold hover:bg-white rounded transition-colors border-x border-shell-border">Today</button>
                        <button @click="nextMonth" class="p-1 hover:bg-white rounded transition-colors"><PhCaretRight :size="16" weight="bold"/></button>
                    </div>
                </div>
                <div v-else class="flex items-center gap-4">
                    <h2 class="text-[18px] font-bold text-text-primary">Schedule New Meeting</h2>
                    <button @click="isScheduling = false" class="text-[13px] font-medium text-text-secondary hover:text-text-primary">Cancel</button>
                </div>
            </div>

            <div v-if="!isScheduling" class="flex-1 overflow-auto p-6 flex justify-center">
                <div class="w-full max-w-5xl bg-white border border-shell-border rounded-card shadow-sm overflow-hidden flex flex-col">
                    <div class="grid grid-cols-7 border-b border-shell-border bg-shell-panel">
                        <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day" class="py-2 text-center text-[12px] font-bold text-text-secondary uppercase">
                            {{ day }}
                        </div>
                    </div>
                    <div class="grid grid-cols-7 flex-1 auto-rows-fr">
                        <div 
                            v-for="(day, index) in calendarDays" 
                            :key="index"
                            class="min-h-[100px] border-b border-r border-shell-border p-1 flex flex-col"
                            :class="[
                                !day.isCurrentMonth ? 'bg-gray-50' : 'bg-white',
                                day.date.isSame(dayjs(), 'day') ? 'ring-2 ring-inset ring-state-focus bg-blue-50/20' : ''
                            ]"
                        >
                            <div class="text-right text-[12px] font-medium p-1" :class="day.isCurrentMonth ? 'text-text-primary' : 'text-text-disabled'">
                                {{ day.date.date() }}
                            </div>
                            <div class="flex flex-col gap-1 flex-1 overflow-y-auto hide-scrollbar">
                                <div 
                                    v-for="ev in getEventsForDay(day.date)" 
                                    :key="ev.id"
                                    class="px-1.5 py-0.5 rounded text-[10px] font-semibold text-white truncate cursor-pointer hover:opacity-80 transition-opacity"
                                    :style="{ backgroundColor: ev.color }"
                                    :title="ev.title"
                                >
                                    {{ ev.title }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="flex-1 overflow-y-auto p-8 flex justify-center">
                <form @submit.prevent="scheduleMeeting" class="w-full max-w-3xl bg-white border border-shell-border rounded-card shadow-card p-8 flex flex-col gap-6">
                    
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[13px] font-semibold text-text-primary">Meeting Title</label>
                        <input v-model="newMeeting.title" required type="text" placeholder="e.g., Weekly Sync" class="w-full px-4 py-2.5 bg-gray-50 border border-shell-border rounded-input text-[14px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none" />
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[13px] font-semibold text-text-primary">Start Time</label>
                            <input v-model="newMeeting.start_time" required type="datetime-local" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[13px] font-semibold text-text-primary">End Time</label>
                            <input v-model="newMeeting.end_time" required type="datetime-local" class="w-full px-4 py-2 bg-gray-50 border border-shell-border rounded-input text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <label class="text-[13px] font-semibold text-text-primary">Meeting Type & Location</label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="border border-shell-border rounded-card p-3 flex flex-col items-center gap-2 cursor-pointer transition-all" :class="newMeeting.type === 'video' ? 'ring-2 ring-state-focus bg-blue-50/30' : 'hover:bg-shell-panel'">
                                <input type="radio" v-model="newMeeting.type" value="video" class="hidden">
                                <PhVideoCamera :size="24" :class="newMeeting.type === 'video' ? 'text-state-focus' : 'text-text-secondary'" />
                                <span class="text-[12px] font-bold">Video Room (LiveKit)</span>
                            </label>
                            <label class="border border-shell-border rounded-card p-3 flex flex-col items-center gap-2 cursor-pointer transition-all" :class="newMeeting.type === 'in_person' ? 'ring-2 ring-state-focus bg-blue-50/30' : 'hover:bg-shell-panel'">
                                <input type="radio" v-model="newMeeting.type" value="in_person" class="hidden">
                                <PhMapPin :size="24" :class="newMeeting.type === 'in_person' ? 'text-state-focus' : 'text-text-secondary'" />
                                <span class="text-[12px] font-bold">In Person</span>
                            </label>
                            <label class="border border-shell-border rounded-card p-3 flex flex-col items-center gap-2 cursor-pointer transition-all" :class="newMeeting.type === 'internal' ? 'ring-2 ring-state-focus bg-blue-50/30' : 'hover:bg-shell-panel'">
                                <input type="radio" v-model="newMeeting.type" value="internal" class="hidden">
                                <PhUsers :size="24" :class="newMeeting.type === 'internal' ? 'text-state-focus' : 'text-text-secondary'" />
                                <span class="text-[12px] font-bold">Audio / Phone</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[13px] font-semibold text-text-primary">Agenda & Notes</label>
                        <textarea v-model="newMeeting.agenda" rows="4" placeholder="Brief agenda for attendees..." class="w-full px-4 py-3 bg-gray-50 border border-shell-border rounded-card text-[13px] focus:bg-white focus:ring-1 focus:ring-state-focus outline-none resize-none"></textarea>
                    </div>

                    <div class="pt-6 border-t border-shell-border flex justify-end">
                        <button type="submit" class="px-6 py-2.5 bg-state-focus text-white text-[14px] font-bold rounded-btn hover:bg-blue-700 transition-colors shadow-sm">
                            Schedule Meeting
                        </button>
                    </div>

                </form>
            </div>

        </div>

    </div>
</template>

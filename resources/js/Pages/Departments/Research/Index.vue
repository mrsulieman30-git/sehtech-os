<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { 
    PhLightbulb, PhFunnel, PhPlus, PhChatCircle, 
    PhThumbsUp, PhThumbsDown, PhTextBolder, PhTextItalic, 
    PhListBullets, PhPaperclip, PhRobot, PhCaretRight,
    PhPaperPlaneRight, PhCheckCircle, PhPushPin, PhList
} from '@phosphor-icons/vue';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { useModalStore } from '@/Stores/useModalStore';
import RichTextEditor from '@/Components/OS/RichTextEditor.vue';
import _ from 'lodash';

const modalStore = useModalStore();

dayjs.extend(relativeTime);

interface Idea {
    id: string; title: string; summary: string; category: string; content: string;
    status: string; vote_count: number; comments_count: number;
    priority: string; tags: string[];
    created_at: string;
    author: { name: string; avatar: string | null; };
    comments: { id: string; body: string; created_at: string; user: { name: string; avatar: string | null; } }[];
}

const ideas = ref<Idea[]>([]);
const selectedIdea = ref<Idea | null>(null);
const editorRef = ref<any>(null);
const searchQuery = ref('');
const newComment = ref('');
const aiSidebarMode = ref('pinned');
const aiSidebarHovered = ref(false);
const listSidebarMode = ref('full'); // 'full', 'half', 'hidden'

// Filter states
const activeFilter = ref('all'); // all, draft, under_review, approved

const fetchIdeas = async () => {
    try {
        const response = await axios.get('/api/research/ideas');
        ideas.value = response.data.ideas;
    } catch (error) {
        console.error('Failed to fetch research ideas', error);
    }
};

const filteredIdeas = computed(() => {
    let result = ideas.value;
    
    if (activeFilter.value !== 'all') {
        result = result.filter(i => i.status === activeFilter.value);
    }
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(i => 
            i.title.toLowerCase().includes(query) || 
            i.category.toLowerCase().includes(query)
        );
    }
    
    return result;
});

const getStatusBadge = (status: string) => {
    const badges: Record<string, string> = {
        'draft': 'bg-shell-border text-text-secondary border-shell-border',
        'under_review': 'bg-state-warning/10 text-state-warning border-state-warning/20',
        'approved': 'bg-state-success/10 text-state-success border-state-success/20',
        'rejected': 'bg-state-error/10 text-state-error border-state-error/20',
        'in_development': 'bg-dept-dev-main/10 text-dept-dev-main border-dept-dev-main/20'
    };
    return badges[status] || badges['draft'];
};

const updateIdeaStatus = async (newStatus: string) => {
    if (!selectedIdea.value) return;
    try {
        await axios.put(`/api/research/ideas/${selectedIdea.value.id}/status`, { status: newStatus });
        selectedIdea.value.status = newStatus;
        fetchIdeas(); // sync library
    } catch (error) {
        console.error('Failed to update status', error);
    }
};

const submitComment = async () => {
    if (!selectedIdea.value || !newComment.value.trim()) return;
    try {
        const response = await axios.post(`/api/research/ideas/${selectedIdea.value.id}/comments`, {
            body: newComment.value
        });
        newComment.value = '';
        selectedIdea.value.comments.push(response.data.comment);
        selectedIdea.value.comments_count++;
        // sync array
        const idx = ideas.value.findIndex(i => i.id === selectedIdea.value!.id);
        if (idx > -1) ideas.value[idx] = selectedIdea.value;
    } catch (error) {
        console.error('Failed to submit comment', error);
    }
};

const submitVote = async (type: 'up' | 'down') => {
    if (!selectedIdea.value) return;
    try {
        const response = await axios.post(`/api/research/ideas/${selectedIdea.value.id}/vote`, { type });
        selectedIdea.value.vote_count = response.data.vote_count;
        const idx = ideas.value.findIndex(i => i.id === selectedIdea.value!.id);
        if (idx > -1) ideas.value[idx] = selectedIdea.value;
    } catch (error) {
        console.error('Failed to submit vote', error);
    }
};

const convertToProject = async () => {
    if (!selectedIdea.value) return;
    if (!confirm('Are you sure you want to convert this approved idea into a Project?')) return;
    
    try {
        const response = await axios.post(`/api/research/ideas/${selectedIdea.value.id}/convert`);
        selectedIdea.value.status = response.data.idea.status;
        const idx = ideas.value.findIndex(i => i.id === selectedIdea.value!.id);
        if (idx > -1) ideas.value[idx].status = response.data.idea.status;
        
        alert(`Success! Project "${response.data.project.name}" created.`);
        // Note: You could redirect to the project page here:
        // window.location.href = `/development/projects/${response.data.project.id}`;
    } catch (error) {
        console.error('Failed to convert idea to project', error);
        alert('Failed to convert to project. Please check if it is approved.');
    }
};

const triggerAIToast = (msg: string) => {
    alert(`Research ARIA: ${msg}`);
};

const saveIdeaContent = _.debounce(async (idea: Idea) => {
    try {
        await axios.put(`/api/research/ideas/${idea.id}`, { content: idea.content });
    } catch (e) {
        console.error('Failed to auto-save content', e);
    }
}, 1000);

const handleAiAction = (action: string) => {
    const editor = editorRef.value?.editor;
    if (!editor) return;
    
    const { from, to } = editor.state.selection;
    if (from === to) return;
    
    const text = editor.state.doc.textBetween(from, to, ' ');
    triggerAIToast(`Processing action: ${action} on text: "${text}"`);
    
    // Simulate AI changing text
    setTimeout(() => {
        let newText = text;
        if (action === 'improve') newText = `✨ ${text} (improved by AI) ✨`;
        if (action === 'shorten') newText = text.substring(0, Math.max(10, text.length / 2)) + '...';
        if (action === 'fix') newText = text.replace(/teh/g, 'the'); // basic mock
        
        editor.chain().focus().deleteRange({ from, to }).insertContent(newText).run();
    }, 800);
};

const handleIdeaSelect = (idea: Idea) => {
    selectedIdea.value = idea;
    if (window.innerWidth > 1024) {
        listSidebarMode.value = 'half';
    } else {
        listSidebarMode.value = 'hidden';
    }
};

const handleEditorTyping = () => {
    if (listSidebarMode.value !== 'hidden') {
        listSidebarMode.value = 'hidden';
    }
};

onMounted(() => {
    fetchIdeas();
    window.addEventListener('refresh-research-dashboard', (e: any) => {
        fetchIdeas();
        if (e.detail?.newIdea) {
            selectedIdea.value = e.detail.newIdea;
        }
    });
});
</script>

<template>
    <div class="h-full flex flex-row bg-shell-panel text-text-primary overflow-hidden font-sans">
        
        <div 
            class="flex-shrink-0 border-r border-shell-border bg-[#F8FAFC] flex flex-col h-full transition-all duration-300"
            :class="{
                'w-[35%] min-w-[320px] max-w-[450px]': listSidebarMode === 'full',
                'w-[240px]': listSidebarMode === 'half',
                'w-0 overflow-hidden border-none': listSidebarMode === 'hidden'
            }"
        >
            
            <div class="p-4 border-b border-shell-border bg-white shrink-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded bg-dept-research-sec flex items-center justify-center text-dept-research-main shrink-0">
                            <PhLightbulb :size="20" weight="fill" />
                        </div>
                        <h2 v-if="listSidebarMode === 'full'" class="text-[16px] font-bold text-text-primary tracking-wide">Idea Library</h2>
                    </div>
                    <button @click="modalStore.openModal('add-research-idea')" class="w-8 h-8 rounded-btn bg-dept-research-main text-white flex items-center justify-center hover:bg-[#3730A3] transition-colors shadow-sm shrink-0" title="New Research Idea">
                        <PhPlus :size="16" weight="bold" />
                    </button>
                </div>
                
                <div class="flex flex-col gap-3" v-if="listSidebarMode === 'full'">
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Search researches, products..." 
                        class="w-full px-3 py-2 bg-shell-panel border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-research-main outline-none transition-all"
                    />
                    
                    <div class="flex gap-2 text-[12px] overflow-x-auto pb-1 hide-scrollbar">
                        <button @click="activeFilter = 'all'" class="px-3 py-1 rounded-tag whitespace-nowrap transition-colors border" :class="activeFilter === 'all' ? 'bg-dept-research-main text-white border-dept-research-main' : 'bg-white text-text-secondary border-shell-border hover:bg-shell-panel'">All Ideas</button>
                        <button @click="activeFilter = 'under_review'" class="px-3 py-1 rounded-tag whitespace-nowrap transition-colors border" :class="activeFilter === 'under_review' ? 'bg-state-warning text-white border-state-warning' : 'bg-white text-text-secondary border-shell-border hover:bg-shell-panel'">Under Review</button>
                        <button @click="activeFilter = 'approved'" class="px-3 py-1 rounded-tag whitespace-nowrap transition-colors border" :class="activeFilter === 'approved' ? 'bg-state-success text-white border-state-success' : 'bg-white text-text-secondary border-shell-border hover:bg-shell-panel'">Approved</button>
                    </div>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto p-3 space-y-3">
                <div 
                    v-for="idea in filteredIdeas" 
                    :key="idea.id"
                    @click="handleIdeaSelect(idea)"
                    class="bg-white p-4 rounded-card border border-shell-border shadow-sm cursor-pointer hover:border-dept-research-main hover:shadow-card transition-all"
                    :class="{'border-dept-research-main ring-1 ring-dept-research-main': selectedIdea?.id === idea.id}"
                >
                    <div class="flex justify-between items-start mb-2">
                        <span class="px-2 py-0.5 rounded-tag text-[10px] font-bold uppercase tracking-wider border" :class="getStatusBadge(idea.status)">
                            {{ idea.status.replace('_', ' ') }}
                        </span>
                        <span v-if="listSidebarMode === 'full'" class="text-[11px] font-medium text-dept-research-main bg-dept-research-sec px-2 py-0.5 rounded uppercase tracking-wider">
                            {{ idea.category }}
                        </span>
                    </div>
                    
                    <h3 class="text-[14px] font-semibold text-text-primary leading-tight mb-1" :class="{'truncate': listSidebarMode === 'half'}">{{ idea.title }}</h3>
                    <p v-if="listSidebarMode === 'full'" class="text-[12px] text-text-secondary line-clamp-2 mb-3 leading-snug">{{ idea.summary }}</p>

                    <div v-if="listSidebarMode === 'full'" class="flex items-center justify-between mt-3 pt-3 border-t border-shell-border border-dashed">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 rounded-full bg-shell-border flex items-center justify-center text-[10px] text-text-primary overflow-hidden">
                                <img v-if="idea.author.avatar" :src="idea.author.avatar" class="w-full h-full object-cover"/>
                                <span v-else>{{ idea.author.name.charAt(0) }}</span>
                            </div>
                            <span class="text-[11px] text-text-secondary">{{ dayjs(idea.created_at).fromNow() }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-text-disabled text-[12px]">
                            <span class="flex items-center gap-1"><PhThumbsUp :size="14" /> {{ idea.vote_count }}</span>
                            <span class="flex items-center gap-1"><PhChatCircle :size="14" /> {{ idea.comments_count }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="filteredIdeas.length === 0" class="text-center text-text-disabled py-10 px-4">
                    <PhLightbulb :size="32" weight="thin" class="mx-auto mb-2 text-shell-border" />
                    <p class="text-[13px]">No ideas found. Try adjusting your filters or create a new one.</p>
                </div>
            </div>
        </div>

        <div v-if="selectedIdea" class="flex-1 flex min-w-0 h-full relative z-10">
            <!-- Center Panel: Editor -->
            <div class="flex-1 flex flex-col min-w-0 bg-white border-r border-shell-border">
                <div class="h-[60px] border-b border-shell-border flex items-center justify-between px-6 shrink-0">
                    <div class="flex items-center gap-4">
                        <button @click="listSidebarMode = listSidebarMode === 'hidden' ? 'half' : (listSidebarMode === 'half' ? 'full' : 'hidden')" class="p-1.5 rounded-btn hover:bg-shell-border/50 text-text-secondary transition-colors" title="Toggle Sidebar">
                            <PhList :size="18" />
                        </button>
                        <span class="px-3 py-1 rounded-tag text-[11px] font-bold uppercase tracking-wider border" :class="getStatusBadge(selectedIdea.status)">
                            {{ selectedIdea.status.replace('_', ' ') }}
                        </span>
                        <span class="text-[13px] text-text-secondary hidden md:inline">Author: <strong class="text-text-primary">{{ selectedIdea.author.name }}</strong></span>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <button 
                            v-if="selectedIdea.status === 'draft'" 
                            @click="updateIdeaStatus('under_review')"
                            class="px-4 py-1.5 bg-shell-panel border border-shell-border text-text-primary text-[13px] font-medium rounded-btn hover:bg-shell-border/50 transition-colors shadow-sm"
                        >
                            Submit for Review
                        </button>
                        <div v-if="selectedIdea.status === 'under_review'" class="flex gap-2">
                            <button @click="updateIdeaStatus('approved')" class="px-4 py-1.5 bg-state-success text-white text-[13px] font-medium rounded-btn hover:bg-opacity-90 transition-colors shadow-sm flex items-center gap-2">
                                <PhCheckCircle :size="16" /> Approve
                            </button>
                        </div>
                        <button 
                            v-if="selectedIdea.status === 'approved'"
                            @click="convertToProject"
                            class="px-4 py-1.5 bg-dept-dev-main text-white text-[13px] font-medium rounded-btn hover:bg-[#1E293B] transition-colors shadow-sm"
                        >
                            Convert to Project
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-6 lg:p-10 pb-24 flex flex-col gap-6">
                    <div class="max-w-4xl mx-auto w-full flex flex-col gap-6">
                        <div>
                            <h1 class="text-[28px] font-bold text-text-primary leading-tight mb-2">{{ selectedIdea.title }}</h1>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200">
                                    {{ selectedIdea.priority || 'P2' }}
                                </span>
                                <span v-for="tag in selectedIdea.tags" :key="tag" class="text-[11px] font-medium text-dept-research-main bg-dept-research-sec px-2 py-0.5 rounded">
                                    #{{ tag }}
                                </span>
                            </div>
                            <p class="text-[16px] text-text-secondary leading-relaxed border-l-4 border-dept-research-main pl-4">
                                {{ selectedIdea.summary }}
                            </p>
                        </div>

                        <div class="flex-1 min-h-[500px]">
                            <RichTextEditor 
                                ref="editorRef" 
                                v-model="selectedIdea.content" 
                                @update:modelValue="saveIdeaContent(selectedIdea)"
                                @typing="handleEditorTyping"
                                @ai-action="handleAiAction"
                            />
                        </div>

                        <div class="mt-8 border-t border-shell-border pt-8">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-[16px] font-bold text-text-primary">Team Discussion</h3>
                                <div class="flex items-center gap-2 bg-shell-panel p-1 rounded-tag border border-shell-border">
                                    <button @click="submitVote('up')" class="px-3 py-1 flex items-center gap-2 text-[13px] font-medium text-text-secondary hover:bg-white hover:text-dept-research-main rounded-tag transition-all">
                                        <PhThumbsUp :size="16" /> +1
                                    </button>
                                    <span class="text-[14px] font-bold px-2">{{ selectedIdea.vote_count }}</span>
                                    <button @click="submitVote('down')" class="px-3 py-1 flex items-center gap-2 text-[13px] font-medium text-text-secondary hover:bg-white hover:text-state-error rounded-tag transition-all">
                                        <PhThumbsDown :size="16" /> -1
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col gap-4 mb-8">
                                <div v-for="comment in selectedIdea.comments" :key="comment.id" class="flex gap-4 p-4 bg-[#F8FAFC] border border-shell-border rounded-card">
                                    <div class="w-8 h-8 rounded-full bg-shell-border flex items-center justify-center text-[10px] text-text-primary overflow-hidden shrink-0">
                                        <img v-if="comment.user.avatar" :src="comment.user.avatar" class="w-full h-full object-cover"/>
                                        <span v-else class="text-[12px] font-bold">{{ comment.user.name.charAt(0) }}</span>
                                    </div>
                                    <div class="flex-1 flex flex-col">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[13px] font-bold text-text-primary">{{ comment.user.name }}</span>
                                            <span class="text-[11px] text-text-secondary">{{ dayjs(comment.created_at).fromNow() }}</span>
                                        </div>
                                        <p class="text-[13px] text-text-secondary whitespace-pre-wrap">{{ comment.body }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-4 mb-8">
                                <div class="w-8 h-8 rounded-full bg-dept-dev-main text-white flex items-center justify-center text-[12px] font-bold shrink-0">
                                    M
                                </div>
                                <div class="flex-1 relative">
                                    <textarea v-model="newComment" rows="2" placeholder="Add a comment or tag someone..." class="w-full p-3 pr-12 bg-[#F8FAFC] border border-shell-border rounded-card text-[13px] focus:ring-1 focus:ring-dept-research-main outline-none resize-none"></textarea>
                                    <button @click="submitComment" class="absolute right-3 bottom-3 p-1.5 bg-dept-research-main text-white rounded hover:bg-[#3730A3] transition-colors">
                                        <PhPaperPlaneRight :size="14" weight="fill" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Sidebar Area -->
            <div 
                class="h-full flex-shrink-0 transition-all duration-300 relative z-20 flex"
                :class="aiSidebarMode === 'pinned' ? 'w-[300px]' : (aiSidebarHovered ? 'w-[300px] absolute right-0 shadow-2xl bg-white' : 'w-[40px] absolute right-0 bg-[#F8FAFC]')"
                @mouseenter="aiSidebarMode === 'auto-hide' && (aiSidebarHovered = true)"
                @mouseleave="aiSidebarMode === 'auto-hide' && (aiSidebarHovered = false)"
            >
                <div class="h-full w-full bg-[#F8FAFC] flex flex-col border-l border-shell-border transition-all"
                     :class="aiSidebarMode === 'auto-hide' && !aiSidebarHovered ? 'opacity-0 overflow-hidden pointer-events-none' : 'opacity-100'"
                >
                    <div class="h-[60px] px-4 bg-dept-research-main text-white flex items-center justify-between shrink-0 shadow-sm z-10">
                        <div class="flex items-center gap-2">
                            <PhRobot :size="20" weight="fill" />
                            <span class="text-[14px] font-bold tracking-wide">Research ARIA</span>
                        </div>
                        <button @click="aiSidebarMode = aiSidebarMode === 'pinned' ? 'auto-hide' : 'pinned'" class="p-1 hover:bg-white/20 rounded text-white" title="Toggle Auto-Hide">
                            <PhCaretRight v-if="aiSidebarMode === 'pinned'" :size="16" />
                            <PhPushPin v-else :size="16" />
                        </button>
                    </div>
                    
                    <div class="flex-1 p-4 flex flex-col gap-4 overflow-y-auto">
                        <div class="p-3 bg-white border border-shell-border rounded-card shadow-sm text-text-primary text-[13px] leading-relaxed">
                            I'm reviewing the document <strong>{{ selectedIdea.title }}</strong>. How can I assist you with this idea?
                        </div>

                        <div class="flex flex-col gap-2 mt-2">
                            <button @click="triggerAIToast('Analyzing market...')" class="w-full text-left px-3 py-2.5 bg-white border border-shell-border rounded-btn hover:border-dept-research-main hover:text-dept-research-main transition-colors text-[12px] font-medium flex items-center justify-between group shadow-sm">
                                Analyze Market Competitors <PhCaretRight :size="12" class="opacity-0 group-hover:opacity-100 transition-opacity" />
                            </button>
                            <button @click="triggerAIToast('Drafting abstract...')" class="w-full text-left px-3 py-2.5 bg-white border border-shell-border rounded-btn hover:border-dept-research-main hover:text-dept-research-main transition-colors text-[12px] font-medium flex items-center justify-between group shadow-sm">
                                Draft Executive Summary <PhCaretRight :size="12" class="opacity-0 group-hover:opacity-100 transition-opacity" />
                            </button>
                            <button @click="triggerAIToast('Evaluating feasibility...')" class="w-full text-left px-3 py-2.5 bg-white border border-shell-border rounded-btn hover:border-dept-research-main hover:text-dept-research-main transition-colors text-[12px] font-medium flex items-center justify-between group shadow-sm">
                                Evaluate Technical Feasibility <PhCaretRight :size="12" class="opacity-0 group-hover:opacity-100 transition-opacity" />
                            </button>
                        </div>
                    </div>

                    <div class="p-3 bg-white border-t border-shell-border shrink-0">
                        <div class="relative">
                            <input type="text" placeholder="Ask ARIA to write or edit..." class="w-full pl-3 pr-10 py-2.5 bg-shell-panel border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-research-main outline-none">
                            <button class="absolute right-1.5 top-1.5 p-1.5 text-dept-research-main hover:bg-dept-research-sec rounded transition-colors">
                                <PhPaperPlaneRight :size="16" weight="fill" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Collapsed Handle -->
                <div 
                    v-if="aiSidebarMode === 'auto-hide' && !aiSidebarHovered"
                    class="absolute top-0 right-0 w-[40px] h-full border-l border-shell-border flex flex-col items-center py-4 cursor-pointer hover:bg-slate-100 transition-colors z-30"
                >
                    <div class="w-8 h-8 rounded bg-dept-research-sec text-dept-research-main flex items-center justify-center shadow-sm">
                        <PhRobot :size="20" weight="fill" />
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="flex-[1.85] flex flex-col items-center justify-center bg-white text-text-disabled relative z-10">
            <PhLightbulb :size="64" weight="thin" class="mb-4 text-shell-border"/>
            <p class="text-[14px]">Select an idea from the library to view or edit the research document.</p>
        </div>

    </div>
</template>

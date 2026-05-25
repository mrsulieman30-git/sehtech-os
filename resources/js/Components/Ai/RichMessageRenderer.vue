<script setup lang="ts">
import { computed } from 'vue';
import { marked } from 'marked';
import DataCard from './Widgets/DataCard.vue';
import DeptLink from './Widgets/DeptLink.vue';
import ReportChart from './Widgets/ReportChart.vue';

const props = defineProps({
    content: { type: String, required: true },
});

const isArabic = computed(() => {
    return /[\u0600-\u06FF]/.test(props.content);
});

const blocks = computed(() => {
    let parsedBlocks = [];
    let currentIndex = 0;
    
    // Match <tag attr="value">text</tag> OR <tag attr="value" />
    const tagRegex = /<(data-card|report-chart|dept-link)\s+([^>]*?)(?:>(.*?)<\/\1>|\s*\/?>)/gs;
    
    let match;
    while ((match = tagRegex.exec(props.content)) !== null) {
        if (match.index > currentIndex) {
            parsedBlocks.push({
                type: 'text',
                content: props.content.slice(currentIndex, match.index)
            });
        }
        
        const tagName = match[1];
        const attrString = match[2];
        const innerText = match[3];
        
        const blockProps: Record<string, string> = {};
        const attrRegex = /([a-zA-Z-]+)=["']([^"']*)["']/g;
        let attrMatch;
        while ((attrMatch = attrRegex.exec(attrString)) !== null) {
            blockProps[attrMatch[1]] = attrMatch[2];
        }
        
        if (tagName === 'dept-link') {
            blockProps.text = innerText || blockProps.slug || 'Link';
        }
        
        parsedBlocks.push({
            type: tagName,
            props: blockProps
        });
        
        currentIndex = tagRegex.lastIndex;
    }
    
    if (currentIndex < props.content.length) {
        parsedBlocks.push({
            type: 'text',
            content: props.content.slice(currentIndex)
        });
    }
    
    return parsedBlocks;
});

const renderMarkdown = (text: string) => {
    return marked(text, { breaks: true });
};
</script>

<template>
    <div 
        :dir="isArabic ? 'rtl' : 'ltr'" 
        :class="['rich-message-renderer flex flex-col gap-2 w-full', isArabic ? 'font-arabic text-right' : 'font-sans text-left']"
    >
        <template v-for="(block, index) in blocks" :key="index">
            
            <div 
                v-if="block.type === 'text'" 
                class="prose prose-sm max-w-none prose-p:leading-relaxed prose-a:text-dept-ai-main prose-strong:text-current prose-headings:text-current"
                v-html="renderMarkdown(block.content)"
            ></div>

            <DataCard 
                v-else-if="block.type === 'data-card'" 
                :title="block.props.title || 'Data'" 
                :value="block.props.value || '-'" 
                :icon="block.props.icon"
                :color="block.props.color"
            />
            
            <ReportChart 
                v-else-if="block.type === 'report-chart'" 
                :type="block.props.type"
                :title="block.props.title || 'Chart'"
                :labels="block.props.labels || ''"
                :data="block.props.data || ''"
            />
            
            <DeptLink 
                v-else-if="block.type === 'dept-link'" 
                :slug="block.props.slug || ''"
            />

        </template>
    </div>
</template>

<style>
/* Arabic Font Support */
@import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap');
.font-arabic {
    font-family: 'Tajawal', sans-serif;
}
.rich-message-renderer .prose p {
    margin-top: 0.5em;
    margin-bottom: 0.5em;
}
.rich-message-renderer .prose table {
    width: 100%;
    border-collapse: collapse;
    margin: 1em 0;
}
.rich-message-renderer .prose th, .rich-message-renderer .prose td {
    border: 1px solid #e2e8f0;
    padding: 0.5em;
}
.rich-message-renderer .prose th {
    background-color: #f8fafc;
    font-weight: bold;
}
</style>

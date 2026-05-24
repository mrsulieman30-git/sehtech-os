<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import { VueDraggable } from 'vue-draggable-plus';
import { 
    PhTextT, PhTextAlignLeft, PhHash, PhCalendarBlank, 
    PhList, PhCheckSquareOffset, PhRadioButton, PhPaperclip,
    PhGear, PhTrash, PhFloppyDisk, PhCopy, PhChecks
} from '@phosphor-icons/vue';

interface FormField {
    id: string; // Unique local ID for dragging
    type: string;
    label: string;
    name: string;
    placeholder: string;
    is_required: boolean;
    options?: string[]; // Simplified array for dropdown/radios
}

// Left Panel: Available Field Types (Clone sources)
const fieldTemplates = [
    { type: 'text', label: 'Short Text', icon: PhTextT },
    { type: 'textarea', label: 'Long Text', icon: PhTextAlignLeft },
    { type: 'number', label: 'Number', icon: PhHash },
    { type: 'date', label: 'Date Selection', icon: PhCalendarBlank },
    { type: 'select', label: 'Dropdown List', icon: PhList },
    { type: 'checkbox', label: 'Checkboxes', icon: PhCheckSquareOffset },
    { type: 'radio', label: 'Radio Buttons', icon: PhRadioButton },
    { type: 'file', label: 'File Upload', icon: PhPaperclip },
];

const formTitle = ref('Untitled Form');
const formDescription = ref('');
const canvasFields = ref<FormField[]>([]);
const selectedFieldIndex = ref<number | null>(null);
const isSaving = ref(false);

const selectedField = computed(() => {
    if (selectedFieldIndex.value === null) return null;
    return canvasFields.value[selectedFieldIndex.value];
});

// Clone function: When dragging from the left panel, initialize a new field object
const cloneField = (template: any): FormField => {
    return {
        id: `field_${Date.now()}_${Math.random().toString(36).substr(2, 5)}`,
        type: template.type,
        label: `New ${template.label}`,
        name: `field_${Date.now()}`,
        placeholder: '',
        is_required: false,
        options: ['select', 'radio', 'checkbox'].includes(template.type) ? ['Option 1', 'Option 2'] : undefined
    };
};

const selectField = (index: number) => {
    selectedFieldIndex.value = index;
};

const removeField = (index: number) => {
    canvasFields.value.splice(index, 1);
    if (selectedFieldIndex.value === index) {
        selectedFieldIndex.value = null;
    } else if (selectedFieldIndex.value !== null && selectedFieldIndex.value > index) {
        selectedFieldIndex.value--;
    }
};

const addOption = () => {
    if (selectedField.value && selectedField.value.options) {
        selectedField.value.options.push(`Option ${selectedField.value.options.length + 1}`);
    }
};

const removeOption = (idx: number) => {
    if (selectedField.value && selectedField.value.options) {
        selectedField.value.options.splice(idx, 1);
    }
};

const saveForm = async () => {
    if (canvasFields.value.length === 0) {
        alert("Please add at least one field to the form.");
        return;
    }
    
    isSaving.value = true;
    try {
        await axios.post('/api/forms', {
            title: formTitle.value,
            description: formDescription.value,
            fields: canvasFields.value
        });
        alert('Form saved successfully!');
    } catch (error) {
        console.error('Failed to save form', error);
        alert('Failed to save form.');
    } finally {
        isSaving.value = false;
    }
};
</script>

<template>
    <div class="h-full flex flex-row bg-shell-panel text-text-primary overflow-hidden font-sans">
        
        <div class="w-[260px] flex-shrink-0 border-r border-shell-border bg-shell-panel flex flex-col h-full z-10">
            <div class="h-[56px] flex items-center px-6 border-b border-shell-border bg-white shrink-0">
                <PhChecks :size="24" class="text-state-focus mr-3" weight="fill" />
                <h2 class="text-[15px] font-bold text-text-primary">Forms Builder</h2>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-4">
                <div>
                    <h3 class="text-[11px] font-bold text-text-disabled uppercase tracking-wider mb-3">Form Elements</h3>
                    
                    <VueDraggable
                        :model-value="fieldTemplates"
                        :group="{ name: 'form-builder', pull: 'clone', put: false }"
                        :clone="cloneField"
                        :sort="false"
                        item-key="type"
                        class="grid grid-cols-2 gap-2"
                    >
                        <div 
                            v-for="item in fieldTemplates" 
                            :key="item.type"
                            class="bg-white border border-shell-border rounded p-3 flex flex-col items-center justify-center gap-2 cursor-grab hover:border-state-focus hover:text-state-focus transition-colors shadow-sm"
                        >
                            <component :is="item.icon" :size="24" />
                            <span class="text-[11px] font-medium text-center">{{ item.label }}</span>
                        </div>
                    </VueDraggable>
                </div>
            </div>
        </div>

        <div class="flex-1 flex flex-col h-full bg-[#F8FAFC] overflow-hidden">
            <div class="h-[56px] bg-white border-b border-shell-border flex items-center px-6 shrink-0 justify-between">
                <div class="flex items-center gap-3 w-1/2">
                    <input 
                        v-model="formTitle" 
                        class="text-[16px] font-bold text-text-primary bg-transparent border-none focus:ring-0 outline-none w-full p-0" 
                        placeholder="Form Title"
                    />
                </div>
                <button 
                    @click="saveForm"
                    :disabled="isSaving"
                    class="flex items-center gap-2 px-4 py-1.5 bg-state-focus text-white text-[13px] font-medium rounded-btn hover:bg-blue-700 transition-colors shadow-sm disabled:opacity-50"
                >
                    <PhFloppyDisk :size="16" weight="bold" /> 
                    {{ isSaving ? 'Saving...' : 'Save Template' }}
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-8 flex justify-center">
                <div class="w-full max-w-2xl bg-white border border-shell-border shadow-card rounded-card min-h-[500px] flex flex-col">
                    
                    <div class="p-6 border-b border-shell-border bg-shell-panel rounded-t-card">
                        <input v-model="formTitle" class="text-[24px] font-bold bg-transparent border-none focus:ring-0 outline-none w-full p-0 mb-2" placeholder="Form Title" />
                        <textarea v-model="formDescription" class="text-[14px] text-text-secondary bg-transparent border-none focus:ring-0 outline-none w-full p-0 resize-none h-12" placeholder="Form description or instructions..."></textarea>
                    </div>

                    <div class="flex-1 p-6 relative">
                        <VueDraggable
                            v-model="canvasFields"
                            group="form-builder"
                            item-key="id"
                            class="min-h-[300px] flex flex-col gap-4"
                            ghost-class="opacity-50 border-dashed border-2 border-state-focus bg-state-focus/5"
                        >
                            <div 
                                v-for="(field, index) in canvasFields" 
                                :key="field.id"
                                @click="selectField(index)"
                                class="p-4 rounded-card border-2 transition-all cursor-pointer relative group bg-white"
                                :class="selectedFieldIndex === index ? 'border-state-focus shadow-sm' : 'border-transparent hover:border-shell-border hover:bg-shell-panel'"
                            >
                                <button 
                                    @click.stop="removeField(index)" 
                                    class="absolute -top-3 -right-3 w-6 h-6 bg-white border border-shell-border rounded-full text-text-disabled hover:text-state-error hover:border-state-error flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-opacity z-10"
                                >
                                    <PhTrash :size="12" />
                                </button>

                                <div class="flex flex-col gap-2 pointer-events-none">
                                    <label class="text-[14px] font-semibold text-text-primary flex items-center gap-1">
                                        {{ field.label }} 
                                        <span v-if="field.is_required" class="text-state-error">*</span>
                                    </label>

                                    <input v-if="['text', 'number', 'date', 'file'].includes(field.type)" type="text" disabled :placeholder="field.placeholder || 'User input here...'" class="w-full px-3 py-2 bg-gray-50 border border-shell-border rounded text-[13px] text-text-disabled" />
                                    
                                    <textarea v-else-if="field.type === 'textarea'" disabled rows="2" :placeholder="field.placeholder || 'User long input here...'" class="w-full px-3 py-2 bg-gray-50 border border-shell-border rounded text-[13px] text-text-disabled"></textarea>
                                    
                                    <select v-else-if="field.type === 'select'" disabled class="w-full px-3 py-2 bg-gray-50 border border-shell-border rounded text-[13px] text-text-disabled">
                                        <option v-for="(opt, i) in field.options" :key="i">{{ opt }}</option>
                                    </select>

                                    <div v-else-if="['radio', 'checkbox'].includes(field.type)" class="flex flex-col gap-2">
                                        <label v-for="(opt, i) in field.options" :key="i" class="flex items-center gap-2 text-[13px] text-text-disabled">
                                            <input :type="field.type" disabled class="rounded-sm border-shell-border"> {{ opt }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </VueDraggable>
                        
                        <div v-if="canvasFields.length === 0" class="absolute inset-0 flex flex-col items-center justify-center text-text-disabled pointer-events-none">
                            <PhCopy :size="48" weight="thin" class="mb-3 text-shell-border" />
                            <p class="text-[14px]">Drag and drop form elements here</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="w-[300px] flex-shrink-0 border-l border-shell-border bg-white flex flex-col h-full z-10">
            <div class="h-[56px] flex items-center px-4 border-b border-shell-border bg-shell-panel shrink-0 gap-2">
                <PhGear :size="18" weight="fill" class="text-text-secondary" />
                <span class="text-[14px] font-bold tracking-wide">Field Properties</span>
            </div>
            
            <div class="flex-1 overflow-y-auto p-5">
                <div v-if="selectedField" class="flex flex-col gap-5">
                    
                    <div class="flex items-center justify-between pb-3 border-b border-shell-border">
                        <span class="text-[12px] font-bold text-text-disabled uppercase">Type: {{ selectedField.type }}</span>
                        <span class="text-[11px] text-text-secondary">Internal ID: {{ selectedField.name }}</span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[13px] font-semibold text-text-primary">Field Label</label>
                        <input v-model="selectedField.label" type="text" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-state-focus outline-none" />
                    </div>

                    <div v-if="['text', 'textarea', 'number'].includes(selectedField.type)" class="flex flex-col gap-1.5">
                        <label class="text-[13px] font-semibold text-text-primary">Placeholder Text</label>
                        <input v-model="selectedField.placeholder" type="text" class="w-full px-3 py-2 bg-white border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-state-focus outline-none" />
                    </div>

                    <div v-if="['select', 'radio', 'checkbox'].includes(selectedField.type)" class="flex flex-col gap-3">
                        <label class="text-[13px] font-semibold text-text-primary">Options</label>
                        <div v-for="(opt, idx) in selectedField.options" :key="idx" class="flex items-center gap-2">
                            <input v-model="selectedField.options[idx]" type="text" class="flex-1 px-3 py-1.5 bg-white border border-shell-border rounded text-[13px] focus:ring-1 focus:ring-state-focus outline-none" />
                            <button @click="removeOption(idx)" :disabled="selectedField.options?.length === 1" class="p-1.5 text-text-disabled hover:text-state-error disabled:opacity-30">
                                <PhTrash :size="16" />
                            </button>
                        </div>
                        <button @click="addOption" class="text-[12px] font-medium text-state-focus hover:underline self-start flex items-center gap-1 mt-1">
                            <PhPlus :size="12" /> Add Option
                        </button>
                    </div>

                    <div class="pt-4 border-t border-shell-border flex items-center gap-3">
                        <input type="checkbox" v-model="selectedField.is_required" :id="'req_' + selectedField.id" class="rounded text-state-focus focus:ring-state-focus border-shell-border w-4 h-4">
                        <label :for="'req_' + selectedField.id" class="text-[13px] font-semibold text-text-primary cursor-pointer">Required Field</label>
                    </div>

                </div>
                
                <div v-else class="h-full flex items-center justify-center text-text-disabled text-center">
                    <p class="text-[13px]">Select a field on the canvas to edit its properties.</p>
                </div>
            </div>
        </div>

    </div>
</template>

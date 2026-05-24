<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3';
import { Extension } from '@tiptap/core';
import { BubbleMenu } from '@tiptap/vue-3/menus';
import { StarterKit } from '@tiptap/starter-kit';
import { Underline } from '@tiptap/extension-underline';
import { TextAlign } from '@tiptap/extension-text-align';
import { TextStyle } from '@tiptap/extension-text-style';
import { Color } from '@tiptap/extension-color';
import { Highlight } from '@tiptap/extension-highlight';
import { Subscript } from '@tiptap/extension-subscript';
import { Superscript } from '@tiptap/extension-superscript';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';
import { TaskList } from '@tiptap/extension-task-list';
import { TaskItem } from '@tiptap/extension-task-item';
import { Link } from '@tiptap/extension-link';
import { Image } from '@tiptap/extension-image';
import { onBeforeUnmount, watch } from 'vue';
import { 
    PhTextB, PhTextItalic, PhTextUnderline, PhTextStrikethrough, 
    PhTextAlignLeft, PhTextAlignCenter, PhTextAlignRight, PhTextAlignJustify,
    PhListBullets, PhListNumbers, PhCheckSquareOffset,
    PhTable, PhImage, PhLink, PhQuotes, PhCodeBlock, PhMinus,
    PhArrowUUpLeft, PhArrowUUpRight, PhEraser, PhHighlighter, PhPalette,
    PhMagicWand, PhArrowsIn, PhCheckCircle
} from '@phosphor-icons/vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'ai-action', 'focus', 'blur', 'typing']);

const FontSize = Extension.create({
  name: 'fontSize',
  addOptions() { return { types: ['textStyle'] }; },
  addGlobalAttributes() {
    return [
      {
        types: this.options.types,
        attributes: {
          fontSize: {
            default: null,
            parseHTML: element => element.style.fontSize.replace(/['"]+/g, ''),
            renderHTML: attributes => {
              if (!attributes.fontSize) return {};
              return { style: `font-size: ${attributes.fontSize}` };
            },
          },
        },
      },
    ];
  },
  addCommands() {
    return {
      setFontSize: fontSize => ({ chain }) => {
        return chain().setMark('textStyle', { fontSize }).run();
      },
      unsetFontSize: () => ({ chain }) => {
        return chain().setMark('textStyle', { fontSize: null }).removeEmptyTextStyle().run();
      },
    };
  },
});

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Underline,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        TextStyle,
        Color,
        Highlight.configure({ multicolor: true }),
        Subscript,
        Superscript,
        Table.configure({ resizable: true }),
        TableRow,
        TableHeader,
        TableCell,
        TaskList,
        TaskItem.configure({ nested: true }),
        Link.configure({ openOnClick: false }),
        Image,
        FontSize
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm w-full max-w-none focus:outline-none min-h-[300px] px-6 py-4 text-[14px] text-text-primary bg-white',
        },
    },
    onUpdate: () => {
        if (!editor.value) return;
        emit('update:modelValue', editor.value.getHTML());
        emit('typing');
    },
    onFocus: () => emit('focus'),
    onBlur: () => emit('blur'),
});

watch(() => props.modelValue, (value) => {
    if (!editor.value) return;
    if (editor.value.getHTML() === value) return;
    editor.value.commands.setContent(value, false);
});

onBeforeUnmount(() => {
    if (editor.value) editor.value.destroy();
});

const setLink = () => {
    const previousUrl = editor.value?.getAttributes('link').href;
    const url = window.prompt('URL', previousUrl);
    if (url === null) return;
    if (url === '') {
        editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }
    editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
};

const addImage = () => {
    const url = window.prompt('URL');
    if (url) {
        editor.value?.chain().focus().setImage({ src: url }).run();
    }
};

defineExpose({ editor });
</script>

<template>
    <div class="border border-shell-border rounded-input overflow-hidden bg-[#F8FAFC] shadow-sm flex flex-col font-sans w-full relative">
        <bubble-menu
            v-if="editor"
            :editor="editor"
            :tippy-options="{ duration: 100, placement: 'top' }"
            class="flex items-center gap-1 bg-shell-window/95 backdrop-blur-md border border-shell-border shadow-modal rounded-btn p-1 z-50"
        >
            <button @click="$emit('ai-action', 'improve')" class="px-2 py-1 flex items-center gap-1 text-[12px] font-medium text-dept-development-main hover:bg-white rounded transition-colors" title="AI Improve">
                <PhMagicWand :size="14" weight="bold" /> Improve
            </button>
            <button @click="$emit('ai-action', 'shorten')" class="px-2 py-1 flex items-center gap-1 text-[12px] font-medium text-white hover:text-text-primary hover:bg-white rounded transition-colors">
                <PhArrowsIn :size="14" /> Shorten
            </button>
            <button @click="$emit('ai-action', 'fix')" class="px-2 py-1 flex items-center gap-1 text-[12px] font-medium text-white hover:text-text-primary hover:bg-white rounded transition-colors">
                <PhCheckCircle :size="14" /> Fix
            </button>
        </bubble-menu>

        <div v-if="editor" class="flex flex-col border-b border-shell-border bg-white">
            <!-- Top Toolbar (File, Undo/Redo) -->
            <div class="flex items-center justify-between px-2 py-1.5 border-b border-shell-border bg-[#F8FAFC]">
                <div class="flex items-center gap-1">
                    <button type="button" @click="editor.chain().focus().undo().run()" :disabled="!editor.can().undo()" class="p-1 rounded text-text-secondary hover:bg-shell-border/50 disabled:opacity-30 transition-colors" title="Undo">
                        <PhArrowUUpLeft :size="16" />
                    </button>
                    <button type="button" @click="editor.chain().focus().redo().run()" :disabled="!editor.can().redo()" class="p-1 rounded text-text-secondary hover:bg-shell-border/50 disabled:opacity-30 transition-colors" title="Redo">
                        <PhArrowUUpRight :size="16" />
                    </button>
                </div>
            </div>

            <!-- Main Toolbar Ribbons -->
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 p-2">
                <!-- Group: Font -->
                <div class="flex items-center gap-1">
                    <select @change="editor.chain().focus().toggleHeading({ level: parseInt(($event.target as HTMLSelectElement).value) }).run()" class="text-[12px] border border-shell-border rounded px-2 py-1 outline-none mr-1 w-24">
                        <option value="0" :selected="editor.isActive('paragraph')">Normal</option>
                        <option value="1" :selected="editor.isActive('heading', { level: 1 })">Heading 1</option>
                        <option value="2" :selected="editor.isActive('heading', { level: 2 })">Heading 2</option>
                        <option value="3" :selected="editor.isActive('heading', { level: 3 })">Heading 3</option>
                    </select>

                    <select @change="editor.chain().focus().setFontSize(($event.target as HTMLSelectElement).value).run()" class="text-[12px] border border-shell-border rounded px-2 py-1 outline-none mr-1 w-16">
                        <option value="" :selected="!editor.getAttributes('textStyle').fontSize">Size</option>
                        <option value="12px" :selected="editor.getAttributes('textStyle').fontSize === '12px'">12</option>
                        <option value="14px" :selected="editor.getAttributes('textStyle').fontSize === '14px'">14</option>
                        <option value="16px" :selected="editor.getAttributes('textStyle').fontSize === '16px'">16</option>
                        <option value="18px" :selected="editor.getAttributes('textStyle').fontSize === '18px'">18</option>
                        <option value="20px" :selected="editor.getAttributes('textStyle').fontSize === '20px'">20</option>
                        <option value="24px" :selected="editor.getAttributes('textStyle').fontSize === '24px'">24</option>
                        <option value="30px" :selected="editor.getAttributes('textStyle').fontSize === '30px'">30</option>
                        <option value="36px" :selected="editor.getAttributes('textStyle').fontSize === '36px'">36</option>
                    </select>
                    
                    <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="{'bg-[#E2E8F0] text-dept-development-main': editor.isActive('bold'), 'hover:bg-shell-border/30': !editor.isActive('bold')}" class="p-1.5 rounded transition-colors text-text-secondary">
                        <PhTextB :size="16" weight="bold" />
                    </button>
                    <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="{'bg-[#E2E8F0] text-dept-development-main': editor.isActive('italic'), 'hover:bg-shell-border/30': !editor.isActive('italic')}" class="p-1.5 rounded transition-colors text-text-secondary">
                        <PhTextItalic :size="16" weight="bold" />
                    </button>
                    <button type="button" @click="editor.chain().focus().toggleUnderline().run()" :class="{'bg-[#E2E8F0] text-dept-development-main': editor.isActive('underline'), 'hover:bg-shell-border/30': !editor.isActive('underline')}" class="p-1.5 rounded transition-colors text-text-secondary">
                        <PhTextUnderline :size="16" weight="bold" />
                    </button>
                    <button type="button" @click="editor.chain().focus().toggleStrike().run()" :class="{'bg-[#E2E8F0] text-dept-development-main': editor.isActive('strike'), 'hover:bg-shell-border/30': !editor.isActive('strike')}" class="p-1.5 rounded transition-colors text-text-secondary">
                        <PhTextStrikethrough :size="16" weight="bold" />
                    </button>
                    
                    <!-- Colors (Simplified to preset buttons or native input) -->
                    <label class="p-1.5 rounded hover:bg-shell-border/30 cursor-pointer text-text-secondary flex items-center gap-1" title="Text Color">
                        <PhPalette :size="16" />
                        <input type="color" @input="editor.chain().focus().setColor(($event.target as HTMLInputElement).value).run()" class="w-0 h-0 opacity-0 absolute" />
                    </label>
                    <button type="button" @click="editor.chain().focus().toggleHighlight().run()" :class="{'bg-[#E2E8F0]': editor.isActive('highlight')}" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary" title="Highlight">
                        <PhHighlighter :size="16" />
                    </button>
                    
                    <button type="button" @click="editor.chain().focus().unsetAllMarks().run()" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary ml-1" title="Clear Formatting">
                        <PhEraser :size="16" />
                    </button>
                </div>

                <div class="w-px h-6 bg-shell-border"></div>

                <!-- Group: Paragraph -->
                <div class="flex items-center gap-1">
                    <button type="button" @click="editor.chain().focus().setTextAlign('left').run()" :class="{'bg-[#E2E8F0]': editor.isActive({ textAlign: 'left' })}" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary">
                        <PhTextAlignLeft :size="16" />
                    </button>
                    <button type="button" @click="editor.chain().focus().setTextAlign('center').run()" :class="{'bg-[#E2E8F0]': editor.isActive({ textAlign: 'center' })}" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary">
                        <PhTextAlignCenter :size="16" />
                    </button>
                    <button type="button" @click="editor.chain().focus().setTextAlign('right').run()" :class="{'bg-[#E2E8F0]': editor.isActive({ textAlign: 'right' })}" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary">
                        <PhTextAlignRight :size="16" />
                    </button>
                    <button type="button" @click="editor.chain().focus().setTextAlign('justify').run()" :class="{'bg-[#E2E8F0]': editor.isActive({ textAlign: 'justify' })}" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary">
                        <PhTextAlignJustify :size="16" />
                    </button>

                    <div class="w-px h-4 bg-shell-border mx-1"></div>

                    <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="{'bg-[#E2E8F0]': editor.isActive('bulletList')}" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary">
                        <PhListBullets :size="16" />
                    </button>
                    <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" :class="{'bg-[#E2E8F0]': editor.isActive('orderedList')}" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary">
                        <PhListNumbers :size="16" />
                    </button>
                    <button type="button" @click="editor.chain().focus().toggleTaskList().run()" :class="{'bg-[#E2E8F0]': editor.isActive('taskList')}" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary">
                        <PhCheckSquareOffset :size="16" />
                    </button>
                </div>

                <div class="w-px h-6 bg-shell-border"></div>

                <!-- Group: Insert -->
                <div class="flex items-center gap-1">
                    <button type="button" @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary flex items-center gap-1 text-[12px]">
                        <PhTable :size="16" /> Table
                    </button>
                    <button type="button" @click="addImage" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary flex items-center gap-1 text-[12px]">
                        <PhImage :size="16" /> Image
                    </button>
                    <button type="button" @click="setLink" :class="{'bg-[#E2E8F0]': editor.isActive('link')}" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary">
                        <PhLink :size="16" />
                    </button>
                    <button type="button" @click="editor.chain().focus().toggleBlockquote().run()" :class="{'bg-[#E2E8F0]': editor.isActive('blockquote')}" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary">
                        <PhQuotes :size="16" />
                    </button>
                    <button type="button" @click="editor.chain().focus().toggleCodeBlock().run()" :class="{'bg-[#E2E8F0]': editor.isActive('codeBlock')}" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary">
                        <PhCodeBlock :size="16" />
                    </button>
                    <button type="button" @click="editor.chain().focus().setHorizontalRule().run()" class="p-1.5 rounded hover:bg-shell-border/30 text-text-secondary">
                        <PhMinus :size="16" />
                    </button>
                </div>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto max-h-[500px]">
            <editor-content :editor="editor" />
        </div>
    </div>
</template>

<style>
/* Tiptap Tailwind Prose overrides if needed */
.ProseMirror p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  float: left;
  color: #94a3b8;
  pointer-events: none;
  height: 0;
}
</style>

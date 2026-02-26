<script setup>
import { ref, watch, onMounted } from 'vue'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  readOnly: {
    type: Boolean,
    default: false
  },
  minHeight: {
    type: Number,
    default: 200
  }
})

const emit = defineEmits(['update:modelValue'])
const editorRef = ref(null)
const internalValue = ref(props.modelValue)

watch(() => props.modelValue, (newVal) => {
  if (internalValue.value !== newVal) {
    internalValue.value = newVal
  }
})

const onUpdate = () => {
  emit('update:modelValue', internalValue.value)
}
</script>

<template>
  <div class="rich-editor-wrapper bg-background border rounded-md overflow-hidden relative">
    <QuillEditor
      ref="editorRef"
      theme="snow"
      v-model:content="internalValue"
      contentType="html"
      :placeholder="placeholder"
      :readOnly="readOnly"
      @update:content="onUpdate"
      :style="{ minHeight: `${minHeight}px` }"
    />
  </div>
</template>

<style>
/* Custom overwrites to match Tailwind UI / Shadcn */
.rich-editor-wrapper .ql-toolbar.ql-snow {
  border-top: none;
  border-left: none;
  border-right: none;
  border-bottom: 1px solid var(--border, #e5e5e5);
  background-color: var(--muted, #f3f4f6);
  border-radius: calc(var(--radius) - 2px) calc(var(--radius) - 2px) 0 0;
  padding: 8px;
  font-family: inherit;
}
.rich-editor-wrapper .ql-container.ql-snow {
  border: none;
  font-family: inherit;
  font-size: 0.875rem; /* text-sm */
}
.rich-editor-wrapper .ql-editor {
  min-height: inherit;
  color: var(--foreground, #111827);
}
.rich-editor-wrapper .ql-editor.ql-blank::before {
  color: var(--muted-foreground, #9ca3af);
  font-style: normal;
}
/* Adjust dark mode */
.dark .rich-editor-wrapper .ql-snow .ql-stroke {
  stroke: #d1d5db;
}
.dark .rich-editor-wrapper .ql-snow .ql-fill, 
.dark .rich-editor-wrapper .ql-snow .ql-stroke.ql-fill {
  fill: #d1d5db;
}
.dark .rich-editor-wrapper .ql-snow .ql-picker {
  color: #d1d5db;
}
</style>

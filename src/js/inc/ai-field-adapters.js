/**
 * Adapters to read and write content of every supported form field type
 */
import { markdownToEditorJsBlocks, editorJsBlocksToMarkdown } from './ai-markdown';

const dispatchInput = (element) => {
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
};

const joinText = (current, addition, separator) => (current.trim() ? `${current}${separator}${addition}` : addition);

const titleAdapter = (form) => {
    const input = form.querySelector('#fus_post_title');
    if (!input || input.type === 'hidden') {
        return null;
    }
    return {
        key: 'post_title',
        label: input.closest('.fus-post-title')?.querySelector('label')?.textContent.replace('*', '').trim(),
        format: 'title',
        canAppend: false,
        anchor: input,
        getValue: async () => input.value,
        setValue: async (text) => {
            input.value = text;
            dispatchInput(input);
        }
    };
};

const textareaAdapter = (wrapper) => {
    const textarea = wrapper.querySelector('textarea');
    if (!textarea) {
        return null;
    }
    return {
        key: textarea.id,
        label: wrapper.querySelector('label')?.textContent.replace('*', '').trim(),
        format: 'text',
        canAppend: true,
        anchor: textarea,
        getValue: async () => textarea.value,
        setValue: async (text, append) => {
            textarea.value = append ? joinText(textarea.value, text, '\n\n') : text;
            dispatchInput(textarea);
        }
    };
};

const tinymceAdapter = (wrapper) => {
    const textarea = wrapper.querySelector('textarea');
    if (!textarea) {
        return null;
    }
    const getEditor = () => (window.tinyMCE ? window.tinyMCE.get(textarea.id) : null);
    return {
        key: textarea.id,
        label: wrapper.querySelector(':scope > label')?.textContent.replace('*', '').trim(),
        format: 'html',
        canAppend: true,
        anchor: wrapper.querySelector('.wp-editor-wrap') || textarea,
        getValue: async () => {
            const editor = getEditor();
            return editor && !editor.isHidden() ? editor.getContent() : textarea.value;
        },
        setValue: async (html, append) => {
            const editor = getEditor();
            const current = editor && !editor.isHidden() ? editor.getContent() : textarea.value;
            const value = append ? `${current}${html}` : html;
            if (editor) {
                editor.setContent(value);
                editor.save();
            }
            textarea.value = value;
            dispatchInput(textarea);
        }
    };
};

const markdownAdapter = (wrapper) => {
    const container = wrapper.querySelector('.md-editor');
    const textarea = wrapper.querySelector('textarea.editor-textarea');
    if (!container || !textarea) {
        return null;
    }
    return {
        key: container.id,
        label: wrapper.querySelector('label')?.textContent.replace('*', '').trim(),
        format: 'markdown',
        canAppend: true,
        anchor: container,
        getValue: async () => textarea.value,
        setValue: async (markdown, append) => {
            const value = append ? joinText(textarea.value, markdown, '\n\n') : markdown;
            const editor = window.fe_md_editors?.[container.id];
            if (editor) {
                editor.$set({ value });
            }
            textarea.value = value;
            dispatchInput(textarea);
        }
    };
};

const editorJsAdapter = (wrapper) => {
    const container = wrapper.querySelector('.EditorJS-editor');
    if (!container) {
        return null;
    }
    const id = container.id;
    const getEditor = () => window.fe_editorjs_editors?.[id];
    const getToolsSettings = () => window.editor_data?.editor_js?.[id]?.settings || {};
    const syncHiddenInput = async (editor) => {
        const data = await editor.save();
        const input = document.getElementById(`${id}-textarea`);
        if (input) {
            input.value = JSON.stringify(data);
        }
    };

    return {
        key: id,
        label: wrapper.querySelector('label')?.textContent.replace('*', '').trim(),
        format: 'markdown',
        canAppend: true,
        anchor: container,
        getValue: async () => {
            const editor = getEditor();
            if (!editor) {
                return '';
            }
            await editor.isReady;
            const data = await editor.save();
            return editorJsBlocksToMarkdown(data.blocks || []);
        },
        setValue: async (markdown, append) => {
            const editor = getEditor();
            if (!editor) {
                return;
            }
            await editor.isReady;
            const settings = getToolsSettings();
            const blocks = markdownToEditorJsBlocks(markdown, {
                header: !!settings.editor_header_plugin,
                list: !!settings.editor_list_plugin
            });
            if (append) {
                blocks.forEach((block) => editor.blocks.insert(block.type, block.data));
            } else {
                await editor.render({ blocks });
            }
            await syncHiddenInput(editor);
        }
    };
};

/**
 * Find all fields in the form that AI can work with
 * @param {HTMLElement} form
 * @returns {Array}
 */
export const findAiFields = (form) => {
    const fields = [titleAdapter(form)];

    form.querySelectorAll('.textarea-field').forEach((wrapper) => {
        fields.push(wrapper.classList.contains('tinymce') ? tinymceAdapter(wrapper) : textareaAdapter(wrapper));
    });
    form.querySelectorAll('.fus-md-editor-field-wrap').forEach((wrapper) => fields.push(markdownAdapter(wrapper)));
    form.querySelectorAll('.fus-editor-js-field-wrap').forEach((wrapper) => fields.push(editorJsAdapter(wrapper)));

    return fields.filter(Boolean);
};

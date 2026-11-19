/**
 * TinyMCE fields write their HTML into their textarea, which the form sends.
 * The form also syncs them right before it sends (front-form.js).
 *
 * Only an editor that has loaded its content may write the textarea. Writing it on mouse
 * events wiped the post content when the pointer was over the form while TinyMCE was still
 * starting (getContent() is empty then). getContent() keeps the <p> markup the field always
 * saved; editor.save() would strip it (wpautop setting).
 */

const SYNC_DELAY_MS = 300;

/**
 * @param {HTMLElement} container form or document
 */
export function syncTinyMceFields(container = document) {
    if (!window.tinymce) {
        return;
    }
    container.querySelectorAll('.textarea-field.tinymce textarea').forEach((textarea) => {
        const editor = window.tinymce.get(textarea.id);
        if (editor && editor.initialized && !editor.removed && !editor.isHidden()) {
            textarea.value = editor.getContent();
        }
    });
}

export default ($) => {
    const watch = (editor) => {
        const textarea = editor && editor.id ? document.getElementById(editor.id) : null;
        if (!textarea || !textarea.closest('.textarea-field.tinymce')) {
            return;
        }
        let timer = null;
        editor.on('change input undo redo', () => {
            clearTimeout(timer);
            timer = setTimeout(() => syncTinyMceFields(textarea.closest('.textarea-field.tinymce')), SYNC_DELAY_MS);
        });
    };

    $(document).ready(function () {
        if (!window.tinymce) {
            return;
        }
        window.tinymce.editors.forEach(watch);
        window.tinymce.on('AddEditor', (event) => watch(event.editor));
    })
}

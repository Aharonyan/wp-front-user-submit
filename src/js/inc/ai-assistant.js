/**
 * AI writing assistant for front-end form fields
 */
import { findAiFields } from './ai-field-adapters';

const SPARKLE_ICON = '<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.9 5.6L19.5 9.5l-5.6 1.9L12 17l-1.9-5.6L4.5 9.5l5.6-1.9L12 2zm7 11l.95 2.55L22.5 16.5l-2.55.95L19 20l-.95-2.55L15.5 16.5l2.55-.95L19 13zM5 14l.7 1.8 1.8.7-1.8.7L5 19l-.7-1.8-1.8-.7 1.8-.7L5 14z"/></svg>';

const el = (tag, attributes = {}, children = []) => {
    const element = document.createElement(tag);
    Object.entries(attributes).forEach(([name, value]) => {
        if (name === 'text') {
            element.textContent = value;
        } else if (name === 'html') {
            element.innerHTML = value;
        } else if (value !== false && value !== null && value !== undefined) {
            element.setAttribute(name, value === true ? '' : value);
        }
    });
    children.forEach((child) => element.append(child));
    return element;
};

/**
 * Modal dialog bound to one field
 */
class AiDialog {
    constructor(config, formId, field, getContextTitle, opener, isFieldEmpty) {
        this.config = config;
        this.formId = formId;
        this.field = field;
        this.getContextTitle = getContextTitle;
        this.opener = opener;
        this.isFieldEmpty = isFieldEmpty;
        this.i18n = config.i18n;
        this.result = '';
        this.isLoading = false;
        this.onKeyDown = this.onKeyDown.bind(this);
        this.render();
    }

    render() {
        const actions = Object.entries(this.config.actions);
        // Empty field: write from prompt, filled field: first editing action
        const editAction = actions.find(([, action]) => !action.needs_prompt);
        this.action = (this.isFieldEmpty || !editAction) ? actions[0][0] : editAction[0];

        this.chips = el('div', { class: 'fus-ai-chips', role: 'radiogroup', 'aria-label': this.i18n.action });
        actions.forEach(([slug, action]) => {
            const chip = el('button', {
                type: 'button',
                class: 'fus-ai-chip',
                role: 'radio',
                'data-action': slug,
                'aria-checked': slug === this.action ? 'true' : 'false',
                text: action.label
            });
            chip.addEventListener('click', () => this.selectAction(slug));
            this.chips.append(chip);
        });

        this.prompt = el('textarea', { class: 'fus-ai-prompt', rows: 3, id: 'fus-ai-prompt', 'aria-label': this.i18n.prompt });
        this.promptWrap = el('div', { class: 'fus-ai-prompt-wrap' }, [
            el('label', { for: 'fus-ai-prompt', class: 'fus-ai-label', text: this.i18n.prompt }),
            this.prompt
        ]);
        this.error = el('div', { class: 'fus-ai-error', role: 'alert', hidden: true });
        this.preview = el('div', { class: `fus-ai-preview fus-ai-preview-${this.field.format}`, tabindex: 0 });
        this.previewWrap = el('div', { class: 'fus-ai-preview-wrap', hidden: true }, [
            el('div', { class: 'fus-ai-label', text: this.i18n.result }),
            this.preview
        ]);

        this.generateButton = el('button', { type: 'button', class: 'fus-ai-btn fus-ai-btn-primary fus-ai-generate', html: `${SPARKLE_ICON}<span>${this.i18n.generate}</span>` });
        this.replaceButton = el('button', { type: 'button', class: 'fus-ai-btn fus-ai-btn-primary fus-ai-replace', text: this.i18n.replace, hidden: true });
        this.appendButton = el('button', { type: 'button', class: 'fus-ai-btn fus-ai-append', text: this.i18n.append, hidden: true });
        const cancelButton = el('button', { type: 'button', class: 'fus-ai-btn fus-ai-btn-link fus-ai-cancel', text: this.i18n.cancel });
        const closeButton = el('button', { type: 'button', class: 'fus-ai-close', 'aria-label': this.i18n.cancel, html: '&times;' });

        this.generateButton.addEventListener('click', () => this.generate());
        this.replaceButton.addEventListener('click', () => this.apply(false));
        this.appendButton.addEventListener('click', () => this.apply(true));
        cancelButton.addEventListener('click', () => this.close());
        closeButton.addEventListener('click', () => this.close());
        this.prompt.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && (event.metaKey || event.ctrlKey)) {
                this.generate();
            }
        });

        const titleId = `fus-ai-title-${Date.now()}`;
        this.dialog = el('div', { class: 'fus-ai-dialog', role: 'dialog', 'aria-modal': 'true', 'aria-labelledby': titleId }, [
            el('div', { class: 'fus-ai-header' }, [
                el('div', {}, [
                    el('h2', { class: 'fus-ai-title', id: titleId, html: `${SPARKLE_ICON}<span>${this.i18n.title}</span>` }),
                    this.field.label ? el('div', { class: 'fus-ai-subtitle', text: this.field.label }) : ''
                ]),
                closeButton
            ]),
            el('div', { class: 'fus-ai-body' }, [this.chips, this.promptWrap, this.error, this.previewWrap]),
            el('div', { class: 'fus-ai-footer' }, [cancelButton, this.appendButton, this.replaceButton, this.generateButton])
        ]);
        this.overlay = el('div', { class: 'fus-ai-overlay' }, [this.dialog]);
        this.overlay.addEventListener('mousedown', (event) => {
            if (event.target === this.overlay) {
                this.close();
            }
        });

        document.body.append(this.overlay);
        document.body.classList.add('fus-ai-open');
        document.addEventListener('keydown', this.onKeyDown);
        this.selectAction(this.action);
        window.requestAnimationFrame(() => this.overlay.classList.add('is-visible'));
    }

    selectAction(slug) {
        this.action = slug;
        const action = this.config.actions[slug];
        this.chips.querySelectorAll('.fus-ai-chip').forEach((chip) => {
            chip.setAttribute('aria-checked', chip.dataset.action === slug ? 'true' : 'false');
        });
        this.promptWrap.hidden = !action.needs_prompt;
        this.prompt.placeholder = action.prompt_placeholder;
        this.hideError();
        (action.needs_prompt ? this.prompt : this.chips.querySelector('[aria-checked="true"]')).focus();
    }

    setLoading(isLoading) {
        this.isLoading = isLoading;
        this.dialog.classList.toggle('is-loading', isLoading);
        this.generateButton.disabled = isLoading;
        this.generateButton.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }

    showError(message) {
        this.error.textContent = message;
        this.error.hidden = false;
    }

    hideError() {
        this.error.hidden = true;
    }

    async generate() {
        if (this.isLoading) {
            return;
        }
        const action = this.config.actions[this.action];
        const content = await this.field.getValue();
        const prompt = this.prompt.value.trim();

        if (action.needs_prompt && !prompt) {
            this.showError(this.i18n.empty_prompt);
            this.prompt.focus();
            return;
        }
        if (!action.needs_prompt && !content.replace(/<[^>]*>/g, '').trim()) {
            this.showError(this.i18n.empty_content);
            return;
        }

        const formData = new FormData();
        formData.append('form_id', this.formId);
        formData.append('ai_action', this.action);
        formData.append('format', this.field.format);
        formData.append('prompt', prompt);
        formData.append('content', content);
        formData.append('context_title', this.field.key === 'post_title' ? '' : this.getContextTitle());

        this.hideError();
        this.setLoading(true);
        try {
            const response = await fetch(this.config.rest_url, {
                method: 'POST',
                body: formData,
                headers: { 'X-WP-Nonce': window.editor_data.nonce }
            });
            const json = await response.json();
            if (!response.ok || !json.success) {
                throw new Error(json.message || json.data?.message || this.i18n.error);
            }
            this.showResult(json.data.text);
        } catch (error) {
            this.showError(error.message || this.i18n.error);
        } finally {
            this.setLoading(false);
        }
    }

    showResult(text) {
        this.result = text;
        if (this.field.format === 'html') {
            // Already sanitized on the server with wp_kses_post
            this.preview.innerHTML = text;
        } else {
            this.preview.textContent = text;
        }
        this.previewWrap.hidden = false;
        this.replaceButton.hidden = false;
        this.appendButton.hidden = !this.field.canAppend || this.isFieldEmpty;
        this.generateButton.classList.remove('fus-ai-btn-primary');
        this.generateButton.querySelector('span').textContent = this.i18n.retry || this.i18n.generate;
        this.replaceButton.focus();
    }

    async apply(append) {
        await this.field.setValue(this.result, append);
        this.opener.closest('.fus-ai-toolbar')?.classList.add('is-applied');
        this.close();
    }

    onKeyDown(event) {
        if (event.key === 'Escape') {
            this.close();
            return;
        }
        if (event.key !== 'Tab') {
            return;
        }
        // Keep focus inside dialog
        const focusable = [...this.dialog.querySelectorAll('button:not([hidden]):not([disabled]), textarea:not([hidden]), [tabindex="0"]')]
            .filter((element) => element.offsetParent !== null);
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && this.dialog.ownerDocument.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && this.dialog.ownerDocument.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    close() {
        document.removeEventListener('keydown', this.onKeyDown);
        document.body.classList.remove('fus-ai-open');
        this.overlay.remove();
        this.opener.focus();
    }
}

/**
 * Add AI buttons to supported fields of the form
 * @param {HTMLElement} form
 */
export default (form) => {
    const config = window.editor_data?.ai;
    if (!config || !form || form.dataset.fusAi === 'ready') {
        return;
    }
    form.dataset.fusAi = 'ready';

    const formId = window.editor_data.form_id;
    const getContextTitle = () => form.querySelector('#fus_post_title')?.value || '';

    findAiFields(form).forEach((field) => {
        const button = el('button', {
            type: 'button',
            class: 'fus-ai-trigger',
            'aria-haspopup': 'dialog',
            'aria-label': `${config.i18n.title}${field.label ? `: ${field.label}` : ''}`,
            html: `${SPARKLE_ICON}<span>${config.i18n.button}</span>`
        });
        const toolbar = el('div', { class: 'fus-ai-toolbar', 'data-field': field.key }, [button]);
        field.anchor.parentNode.insertBefore(toolbar, field.anchor);

        button.addEventListener('click', async () => {
            const value = await field.getValue();
            const isFieldEmpty = !value.replace(/<[^>]*>/g, '').trim();
            new AiDialog(config, formId, field, getContextTitle, button, isFieldEmpty);
        });
    });
};

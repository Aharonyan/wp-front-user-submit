/**
 * Login/Register AJAX Module - Vanilla JavaScript (No jQuery)
 * Independent module with webpack build
 */

class FusLoginRegisterAjax {
    constructor() {
        this.config = window.fusAjaxConfig || {};
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.bindEvents());
        } else {
            this.bindEvents();
        }
    }


    bindEvents() {
        // Handle AJAX forms
        document.addEventListener('submit', (e) => {
            if (e.target.matches(this.config.selectors?.form || '.fus-ajax-form')) {
                this.handleAjaxSubmit(e);
            }
        });

        // Handle non-AJAX forms (fallback loading state)
        document.addEventListener('submit', (e) => {
            const fallbackSelector = this.config.selectors?.fallbackForm || '.fus-form:not(.fus-ajax-form)';
            if (e.target.matches(fallbackSelector)) {
                this.handleFallbackSubmit(e);
            }
        });
    }

    handleAjaxSubmit(e) {
        e.preventDefault();

        const form = e.target;
        const submitBtn = form.querySelector(this.config.selectors?.submitBtn || 'input[type="submit"], button[type="submit"]');
        const messageContainer = form.closest('.fus-login-form-wrap, .fus-register-form-wrap')?.querySelector('.fus-message')
            || form.parentElement.querySelector('.fus-message');
        const originalBtnText = submitBtn.value || submitBtn.textContent;

        // Get form action
        const actionInput = form.querySelector('input[name="fus_action"]');
        const action = actionInput?.value;

        if (!action) {
            this.showMessage(messageContainer, 'error', 'Invalid form action');
            return;
        }

        // Check if config is available
        if (!this.config.ajaxUrl) {
            console.warn('fusAjaxConfig not available, falling back to regular form submission');
            form.classList.remove('fus-ajax-form');
            form.submit();
            return;
        }

        // Set loading state
        this.setLoadingState(submitBtn, true, originalBtnText);
        this.clearMessages(messageContainer);

        // Prepare form data
        const formData = new FormData(form);
        const ajaxData = new URLSearchParams();

        // Add AJAX action and nonce
        ajaxData.append('action', 'fus_' + action);
        ajaxData.append('nonce', this.config.nonce);

        // Add form data
        for (const [key, value] of formData.entries()) {
            ajaxData.append(key, value);
        }

        // Make AJAX request
        fetch(this.config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: ajaxData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.handleSuccess(form, messageContainer, data.data);
                } else {
                    this.handleError(form, messageContainer, data.data);
                }
            })
            .catch(error => {
                console.error('AJAX error:', error);
                this.handleError(form, messageContainer, {
                    message: this.config.messages?.networkError || 'Network error occurred'
                });
            })
            .finally(() => {
                this.setLoadingState(submitBtn, false, originalBtnText);
            });
    }

    handleSuccess(form, messageContainer, data) {
        // Show success message
        this.showMessage(messageContainer, 'success', data.message);

        // Clear form
        form.reset();

        // Trigger custom event
        this.dispatchCustomEvent('fus:login:success', { form, data });

        // Handle redirect
        if (data.redirect) {
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1500);
        } else {
            // If no redirect, reload page after short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        }
    }

    handleError(form, messageContainer, data) {
        const errorMessage = data.message || this.config.messages?.error || 'An error occurred';
        this.showMessage(messageContainer, 'error', errorMessage);

        // Trigger custom event
        this.dispatchCustomEvent('fus:login:error', { form, data });
    }

    showMessage(container, type, message) {
        if (!container) return;

        container.className = container.className.replace(/\b(error|success)\b/g, '');
        container.classList.add(type);
        container.innerHTML = `<p>${message}</p>`;
        container.style.display = 'block';

        // Add show class for animation
        container.classList.add('show');

        // Smooth scroll to message
        container.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }

    clearMessages(container) {
        if (!container) return;

        container.classList.remove('error', 'success', 'show');
        container.innerHTML = '';
        container.style.display = 'none';
    }

    setLoadingState(button, isLoading, originalText) {
        if (!button) return;

        if (isLoading) {
            button.disabled = true;
            const loadingText = this.config.messages?.processing || 'Processing...';

            if (button.tagName.toLowerCase() === 'input') {
                button.value = loadingText;
            } else {
                button.textContent = loadingText;
            }

            button.classList.add('loading');
        } else {
            button.disabled = false;

            if (button.tagName.toLowerCase() === 'input') {
                button.value = originalText;
            } else {
                button.textContent = originalText;
            }

            button.classList.remove('loading');
        }
    }

    handleFallbackSubmit(e) {
        // For non-AJAX forms, just add loading state
        const form = e.target;
        const submitBtn = form.querySelector(this.config.selectors?.submitBtn || 'input[type="submit"], button[type="submit"]');
        const originalBtnText = submitBtn?.value || submitBtn?.textContent;

        if (submitBtn && this.config.messages?.processing) {
            this.setLoadingState(submitBtn, true, originalBtnText);
        }
    }

    dispatchCustomEvent(eventName, detail) {
        const event = new CustomEvent(eventName, {
            detail,
            bubbles: true,
            cancelable: true
        });
        document.dispatchEvent(event);
    }

    // Public API methods for programmatic access
    async triggerLogin(credentials) {
        return this.triggerAction('login', credentials);
    }

    async triggerRegister(userData) {
        return this.triggerAction('register', userData);
    }

    async triggerAction(action, data) {
        if (!this.config.ajaxUrl) {
            throw new Error('AJAX not available');
        }

        const ajaxData = new URLSearchParams();
        ajaxData.append('action', 'fus_' + action);
        ajaxData.append('nonce', this.config.nonce);

        // Add user data
        for (const [key, value] of Object.entries(data)) {
            ajaxData.append(key, value);
        }

        try {
            const response = await fetch(this.config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: ajaxData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                return result.data;
            } else {
                throw new Error(result.data?.message || 'Action failed');
            }
        } catch (error) {
            console.error('API error:', error);
            throw error;
        }
    }

    // Utility method to add form validation
    addFormValidation(form, rules) {
        if (!(form instanceof HTMLFormElement)) return;

        const validateField = (field, rule) => {
            const value = field.value.trim();
            let isValid = true;
            let message = '';

            if (rule.required && !value) {
                isValid = false;
                message = rule.requiredMessage || `${field.name} is required`;
            } else if (rule.pattern && !rule.pattern.test(value)) {
                isValid = false;
                message = rule.patternMessage || `Invalid ${field.name} format`;
            } else if (rule.minLength && value.length < rule.minLength) {
                isValid = false;
                message = rule.minLengthMessage || `${field.name} must be at least ${rule.minLength} characters`;
            }

            // Update field appearance
            field.classList.toggle('error', !isValid);

            // Show/hide error message
            let errorElement = field.parentNode.querySelector('.field-error');
            if (!isValid) {
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'field-error';
                    field.parentNode.appendChild(errorElement);
                }
                errorElement.textContent = message;
            } else if (errorElement) {
                errorElement.remove();
            }

            return isValid;
        };

        // Add real-time validation
        Object.entries(rules).forEach(([fieldName, rule]) => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.addEventListener('blur', () => validateField(field, rule));
                field.addEventListener('input', () => {
                    if (field.classList.contains('error')) {
                        validateField(field, rule);
                    }
                });
            }
        });

        // Validate on form submit
        form.addEventListener('submit', (e) => {
            let isFormValid = true;

            Object.entries(rules).forEach(([fieldName, rule]) => {
                const field = form.querySelector(`[name="${fieldName}"]`);
                if (field && !validateField(field, rule)) {
                    isFormValid = false;
                }
            });

            if (!isFormValid) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    }
}

// Initialize when script loads
const fusLoginRegister = new FusLoginRegisterAjax();

// Make it globally available
window.fusLoginRegister = fusLoginRegister;

// Export for module systems (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FusLoginRegisterAjax;
}
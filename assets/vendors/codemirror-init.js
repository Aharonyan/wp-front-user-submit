(function() {
    'use strict';
    
    // Configuration object for easy customization
    const codeMirrorConfig = {
        mode: "css",
        theme: "dracula",
        lineNumbers: true,
        tabSize: 2,
        indentWithTabs: true,
        matchBrackets: true,
        autoCloseBrackets: true,
        lineWrapping: true,
        foldGutter: true,
        gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
        extraKeys: {
            "Ctrl-Space": "autocomplete",
            "F11": function(cm) {
                cm.setOption("fullScreen", !cm.getOption("fullScreen"));
            },
            "Esc": function(cm) {
                if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
            }
        }
    };
    
    // Initialize all CodeMirror editors
    function initializeCodeMirrors() {
        const editors = document.querySelectorAll('.code-mirror-editor-css');
        
        if (!editors.length) {
            console.log('No CodeMirror editors found with class: code-mirror-editor-css');
            return;
        }
        
        editors.forEach((textarea, index) => {
            // Skip if already initialized
            if (textarea.CodeMirrorInstance) {
                console.log(`Editor #${index} already initialized`);
                return;
            }
            
            try {
                // Create CodeMirror instance
                const editor = CodeMirror.fromTextArea(textarea, codeMirrorConfig);
                
                // Store the instance
                textarea.CodeMirrorInstance = editor;
                
                // Set custom attributes if present
                const height = textarea.getAttribute('data-height') || '300px';
                const width = textarea.getAttribute('data-width') || null;
                editor.setSize(width, height);
                
                // Add custom class to wrapper
                editor.getWrapperElement().classList.add('code-mirror-wrapper');
                
                // Handle visibility changes (for tabs, accordions, etc.)
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                            if (textarea.offsetParent !== null) {
                                editor.refresh();
                            }
                        }
                    });
                });
                
                // Observe parent elements for visibility changes
                let parent = textarea.parentElement;
                while (parent && parent !== document.body) {
                    observer.observe(parent, { attributes: true });
                    parent = parent.parentElement;
                }
                
                console.log(`CodeMirror editor #${index} initialized successfully`);
                
            } catch (error) {
                console.error(`Failed to initialize CodeMirror editor #${index}:`, error);
            }
        });
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeCodeMirrors);
    } else {
        // DOM is already loaded
        initializeCodeMirrors();
    }
    
    // Expose function globally for dynamic initialization
    window.initializeCodeMirrors = initializeCodeMirrors;
    
    // Helper function to get editor instance
    window.getCodeMirrorInstance = function(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        return element ? element.CodeMirrorInstance : null;
    };
    
})();
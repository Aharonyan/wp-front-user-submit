var editor = CodeMirror.fromTextArea(document.getElementById("code-editor-css"), {
    mode: "css",                    // Set mode to CSS
    theme: "dracula",               // Set a theme (optional)
    lineNumbers: true,              // Enable line numbers
    tabSize: 2,                     // Set tab size
    indentWithTabs: true,           // Use tabs for indentation
    matchBrackets: true             // Highlight matching brackets
});

/**
 * Minimal markdown <-> EditorJS conversion for AI generated content
 */

const escapeHtml = (text) => text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

/**
 * Inline markdown (bold, italic, code, links) to safe HTML
 * @param {string} text
 * @returns {string}
 */
export const inlineMarkdownToHtml = (text) => escapeHtml(text)
    .replace(/`([^`]+)`/g, '<code class="inline-code">$1</code>')
    .replace(/\*\*([^*]+)\*\*/g, '<b>$1</b>')
    .replace(/__([^_]+)__/g, '<b>$1</b>')
    .replace(/(^|[^*])\*([^*\s][^*]*)\*/g, '$1<i>$2</i>')
    .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2">$1</a>');

const htmlToInlineMarkdown = (html) => {
    const markdown = String(html)
        .replace(/<(b|strong)>(.*?)<\/\1>/gi, '**$2**')
        .replace(/<(i|em)>(.*?)<\/\1>/gi, '*$2*')
        .replace(/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/gi, '[$2]($1)')
        .replace(/<br\s*\/?>/gi, '\n');
    // DOMParser does not load images or run handlers, unlike innerHTML on a detached node
    return new window.DOMParser().parseFromString(markdown, 'text/html').body.textContent || '';
};

const LIST_ITEM = /^\s*(?:[-*+]|(\d+)[.)])\s+(.*)$/;

/**
 * @param {string} markdown
 * @param {{header: boolean, list: boolean}} tools enabled EditorJS tools
 * @returns {Array<{type: string, data: object}>}
 */
export const markdownToEditorJsBlocks = (markdown, tools = { header: true, list: true }) => {
    const blocks = [];
    let paragraph = [];
    let list = null;

    const flushParagraph = () => {
        if (paragraph.length) {
            blocks.push({ type: 'paragraph', data: { text: inlineMarkdownToHtml(paragraph.join(' ')) } });
            paragraph = [];
        }
    };
    const flushList = () => {
        if (!list) {
            return;
        }
        if (tools.list) {
            blocks.push({ type: 'list', data: { style: list.style, items: list.items } });
        } else {
            list.items.forEach((item) => blocks.push({ type: 'paragraph', data: { text: `• ${item}` } }));
        }
        list = null;
    };

    String(markdown).replace(/\r\n/g, '\n').split('\n').forEach((line) => {
        const heading = line.match(/^(#{1,6})\s+(.*)$/);
        const listItem = line.match(LIST_ITEM);

        if (!line.trim()) {
            flushParagraph();
            flushList();
        } else if (heading) {
            flushParagraph();
            flushList();
            const text = inlineMarkdownToHtml(heading[2].trim());
            blocks.push(tools.header
                ? { type: 'header', data: { text, level: Math.min(Math.max(heading[1].length, 2), 4) } }
                : { type: 'paragraph', data: { text: `<b>${text}</b>` } });
        } else if (listItem) {
            flushParagraph();
            const style = listItem[1] ? 'ordered' : 'unordered';
            if (list && list.style !== style) {
                flushList();
            }
            list = list || { style, items: [] };
            list.items.push(inlineMarkdownToHtml(listItem[2].trim()));
        } else {
            flushList();
            paragraph.push(line.trim().replace(/^>\s?/, ''));
        }
    });

    flushParagraph();
    flushList();
    return blocks;
};

/**
 * @param {Array} blocks EditorJS blocks
 * @returns {string}
 */
export const editorJsBlocksToMarkdown = (blocks) => blocks.map((block) => {
    const data = block.data || {};
    switch (block.type) {
        case 'header':
            return `${'#'.repeat(data.level || 2)} ${htmlToInlineMarkdown(data.text || '')}`;
        case 'list':
            return (data.items || []).map((item, index) => {
                const text = typeof item === 'string' ? item : (item.content || '');
                return `${data.style === 'ordered' ? `${index + 1}.` : '-'} ${htmlToInlineMarkdown(text)}`;
            }).join('\n');
        case 'quote':
            return `> ${htmlToInlineMarkdown(data.text || '')}`;
        default:
            return htmlToInlineMarkdown(data.text || data.caption || '');
    }
}).filter((text) => text.trim()).join('\n\n');

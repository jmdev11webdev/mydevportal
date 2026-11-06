function escapeHtml(value: string): string {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function safeUrl(url: string): string | null {
    return /^(https?:\/\/|mailto:|\/)/i.test(url) ? url : null;
}

function renderInline(value: string): string {
    const codeSpans: string[] = [];
    const marker = '\uE000';

    let text = escapeHtml(value)
        .replaceAll(marker, '')
        .replace(/`([^`]+)`/g, (_, code: string) => {
            const token = `${marker}CODE${codeSpans.length}${marker}`;
            codeSpans.push(`<code>${code}</code>`);

            return token;
        });

    text = text
        .replace(
            /!\[([^\]]*)\]\(([^)\s]+)\)/g,
            (match, alt: string, url: string) => {
                const safe = safeUrl(url);

                return safe
                    ? `<img src="${safe}" alt="${alt}" loading="lazy" />`
                    : match;
            },
        )
        .replace(
            /\[([^\]]+)\]\(([^)\s]+)\)/g,
            (match, label: string, url: string) => {
                const safe = safeUrl(url);

                return safe
                    ? `<a href="${safe}" target="_blank" rel="noreferrer">${label}</a>`
                    : match;
            },
        )
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        .replace(/__([^_]+)__/g, '<strong>$1</strong>')
        .replace(/(^|[\s(])\*([^*\n]+)\*/g, '$1<em>$2</em>')
        .replace(/(^|[\s(])_([^_\n]+)_/g, '$1<em>$2</em>');

    return text.replace(
        /\uE000CODE(\d+)\uE000/g,
        (_, index: string) => codeSpans[Number(index)] ?? '',
    );
}

/**
 * A small, dependency-free Markdown renderer for the README preview.
 * Input is HTML-escaped before formatting, so user content is never injected
 * into the preview as raw HTML.
 */
export function renderMarkdown(markdown: string): string {
    const lines = markdown.replace(/\r\n?/g, '\n').split('\n');
    const html: string[] = [];

    let paragraph: string[] = [];
    let listType: 'ul' | 'ol' | null = null;
    let inCodeBlock = false;
    let codeLanguage = '';
    let codeLines: string[] = [];

    const flushParagraph = (): void => {
        if (paragraph.length === 0) {
            return;
        }

        html.push(`<p>${renderInline(paragraph.join(' '))}</p>`);
        paragraph = [];
    };

    const closeList = (): void => {
        if (listType === null) {
            return;
        }

        html.push(`</${listType}>`);
        listType = null;
    };

    const closeCodeBlock = (): void => {
        if (!inCodeBlock) {
            return;
        }

        const language = codeLanguage
            ? ` class="language-${escapeHtml(codeLanguage)}"`
            : '';

        html.push(
            `<pre><code${language}>${escapeHtml(codeLines.join('\n'))}</code></pre>`,
        );

        inCodeBlock = false;
        codeLanguage = '';
        codeLines = [];
    };

    for (const line of lines) {
        const fence = line.match(/^```([\w-]*)\s*$/);

        if (fence) {
            if (inCodeBlock) {
                closeCodeBlock();
            } else {
                flushParagraph();
                closeList();
                inCodeBlock = true;
                codeLanguage = fence[1] ?? '';
            }

            continue;
        }

        if (inCodeBlock) {
            codeLines.push(line);

            continue;
        }

        if (line.trim() === '') {
            flushParagraph();
            closeList();

            continue;
        }

        const heading = line.match(/^(#{1,6})\s+(.*)$/);

        if (heading) {
            flushParagraph();
            closeList();

            const level = heading[1].length;
            html.push(`<h${level}>${renderInline(heading[2])}</h${level}>`);

            continue;
        }

        if (/^>\s?/.test(line)) {
            flushParagraph();
            closeList();
            html.push(
                `<blockquote>${renderInline(line.replace(/^>\s?/, ''))}</blockquote>`,
            );

            continue;
        }

        if (/^(-{3,}|\*{3,}|_{3,})$/.test(line.trim())) {
            flushParagraph();
            closeList();
            html.push('<hr />');

            continue;
        }

        const unordered = line.match(/^\s*[-*]\s+(.*)$/);
        const ordered = line.match(/^\s*\d+\.\s+(.*)$/);

        if (unordered || ordered) {
            flushParagraph();

            const type = unordered ? 'ul' : 'ol';

            if (listType !== type) {
                closeList();
                html.push(`<${type}>`);
                listType = type;
            }

            html.push(
                `<li>${renderInline((unordered ?? ordered)?.[1] ?? '')}</li>`,
            );

            continue;
        }

        paragraph.push(line.trim());
    }

    closeCodeBlock();
    flushParagraph();
    closeList();

    return html.join('\n');
}

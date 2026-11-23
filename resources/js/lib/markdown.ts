import {
    getTechBadgeStyle,
    getTechIconClass,
    resolveTechOption,
} from '@/lib/techStack';

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

function isTechBadgeLine(line: string): boolean {
    const trimmed = line.trim();
    if (
        !trimmed.startsWith('![') ||
        !trimmed.includes('img.shields.io/badge/')
    ) {
        return false;
    }

    // Matches lines composed exclusively of shields.io tech badges (not License / GitHub stars)
    const tokens = trimmed.match(/!\[[^\]]+\]\([^)\s]+\)/g) ?? [];
    if (tokens.length === 0) {
        return false;
    }

    const remainder = trimmed.replace(/!\[[^\]]+\]\([^)\s]+\)/g, '').trim();
    if (remainder !== '') {
        return false;
    }

    return !tokens.some(
        (token) =>
            token.startsWith('![License]') ||
            token.startsWith('![GitHub stars]'),
    );
}

function renderTechBadgePill(rawLabel: string): string {
    const label = rawLabel.trim();
    const safeLabel = escapeHtml(label);
    const option = resolveTechOption(label);
    const iconClass = getTechIconClass(label);
    const style = getTechBadgeStyle(option?.label ?? label);
    const inlineStyle = `background-color: ${style.backgroundColor}; border-color: ${style.borderColor};`;

    const iconHtml = iconClass
        ? `<i class="${escapeHtml(iconClass)} readme-tech-icon" aria-hidden="true"></i>`
        : `<span class="readme-tech-dot" aria-hidden="true"></span>`;

    return `<span class="readme-tech-pill" style="${inlineStyle}">${iconHtml}<span>${safeLabel}</span></span>`;
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

function splitTableRow(line: string): string[] {
    const trimmed = line.trim().replace(/^\|/, '').replace(/\|$/, '');
    return trimmed.split('|').map((cell) => cell.trim());
}

function isTableSeparator(line: string): boolean {
    const cells = splitTableRow(line);
    return cells.length > 0 && cells.every((cell) => /^:?-{3,}:?$/.test(cell));
}

function parseTableAlignments(line: string): ('left' | 'center' | 'right')[] {
    return splitTableRow(line).map((cell) => {
        if (cell.startsWith(':') && cell.endsWith(':')) {
            return 'center';
        }
        if (cell.endsWith(':')) {
            return 'right';
        }
        return 'left';
    });
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

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
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

        // Markdown tables: header row + separator row + 0..N body rows
        if (
            line.includes('|') &&
            i + 1 < lines.length &&
            isTableSeparator(lines[i + 1])
        ) {
            flushParagraph();
            closeList();

            const headers = splitTableRow(line);
            const alignments = parseTableAlignments(lines[i + 1]);
            i += 2;

            const bodyRows: string[][] = [];
            while (
                i < lines.length &&
                lines[i].trim() !== '' &&
                lines[i].includes('|')
            ) {
                bodyRows.push(splitTableRow(lines[i]));
                i++;
            }
            i--; // step back so loop increment lands on next line

            const thead = `<thead><tr>${headers
                .map((cell, col) => {
                    const align = alignments[col] ?? 'left';
                    return `<th style="text-align: ${align};">${renderInline(cell)}</th>`;
                })
                .join('')}</tr></thead>`;

            const tbody =
                bodyRows.length > 0
                    ? `<tbody>${bodyRows
                          .map(
                              (row) =>
                                  `<tr>${headers
                                      .map((_, col) => {
                                          const align =
                                              alignments[col] ?? 'left';
                                          return `<td style="text-align: ${align};">${renderInline(row[col] ?? '—')}</td>`;
                                      })
                                      .join('')}</tr>`,
                          )
                          .join('')}</tbody>`
                    : '';

            html.push(
                `<div class="readme-table-wrap"><table>${thead}${tbody}</table></div>`,
            );

            continue;
        }

        if (isTechBadgeLine(line)) {
            flushParagraph();
            closeList();

            const pills = [...line.matchAll(/!\[([^\]]+)\]\([^)\s]+\)/g)].map(
                (match) => renderTechBadgePill(match[1]),
            );
            html.push(`<div class="readme-tech-list">${pills.join('')}</div>`);

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

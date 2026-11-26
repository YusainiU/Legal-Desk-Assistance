const ink = '#162541';
const muted = '#53647d';

export function caseSummaryDefinition(summary) {
    const { case: record, firm, generated, stages, history } = summary;
    const value = (text) => text || 'Not recorded';
    const section = (text) => ({ text, style: 'section' });
    const list = (items) => ({ ul: items, margin: [12, 3, 0, 8] });
    const metadata = [
        ['Client', record.client],
        ['Other Party', record.otherParty],
        ['Case Owner', record.owner],
        ['Status', [record.status, record.stage].filter(Boolean).join(' · ')],
    ].map(([label, text]) => ({
        columns: [{ text: label, width: 100, bold: true }, { text: value(text), width: '*' }],
        margin: [0, 0, 0, 5],
    }));

    const completed = stages.flatMap((stage) => [
        { text: `Stage ${stage.number} · ${stage.name}`, style: 'subheading' },
        { text: stage.description, margin: [0, 0, 0, 5] },
        ...(stage.checklist.length ? [
            { text: 'Recorded checklist', bold: true },
            list(stage.checklist.map((item) => `${item.label} · ${item.done ? 'Complete' : 'Unchecked'}`)),
        ] : []),
        ...(stage.tasks.length ? [
            { text: 'Stage tasks', bold: true },
            list(stage.tasks.map((task) => [
                task.title, task.done ? 'Complete' : 'To do', task.assignee,
                task.due ? `Due ${task.due}` : null,
            ].filter(Boolean).join(' · '))),
        ] : []),
    ]);

    const timeline = history.flatMap((entry) => [
        { text: [entry.kind, entry.date, entry.emailStatus].filter(Boolean).join(' · '), style: 'subheading' },
        { text: entry.body || '—', margin: [0, 0, 0, 6] },
    ]);

    return {
        info: { title: `Case summary · ${record.reference}`, author: firm },
        pageSize: 'A4',
        pageMargins: [46, 42, 46, 52],
        defaultStyle: { font: 'Roboto', fontSize: 10, color: ink, lineHeight: 1.2 },
        styles: {
            firm: { fontSize: 22, bold: true, margin: [0, 0, 0, 7] },
            title: { fontSize: 16, bold: true, margin: [0, 0, 0, 6] },
            section: { fontSize: 13, bold: true, color: '#254c83', margin: [0, 18, 0, 9] },
            subheading: { fontSize: 10, bold: true, margin: [0, 10, 0, 4] },
        },
        footer: (page, total) => ({
            columns: [
                { text: `${record.reference}  |  Case summary` },
                { text: `Page ${page} of ${total}`, alignment: 'right' },
            ],
            margin: [46, 12, 46, 0],
            color: muted,
            fontSize: 8,
        }),
        content: [
            { text: firm, style: 'firm' },
            { text: 'Case summary', fontSize: 11, color: muted, margin: [0, 0, 0, 8] },
            { text: record.title, style: 'title' },
            { text: `${record.reference} · Prepared ${generated}`, color: muted },
            section('Case details'),
            ...metadata,
            { text: 'Case Description', bold: true, margin: [0, 7, 0, 5] },
            { text: value(record.description) },
            section('Completed stages'),
            ...(completed.length ? completed : [{ text: 'No stages have been completed.' }]),
            section('Final decision'),
            { text: record.finalDecision || 'No final decision has been recorded.' },
            section('Case history'),
            ...(timeline.length ? timeline : [{ text: 'No history has been recorded.' }]),
        ],
    };
}

export function caseReferencesDefinition(summary) {
    const { case: record, firm, generated, references } = summary;
    const definition = caseSummaryDefinition({ ...summary, stages: [], history: [] });
    definition.info.title = `Legal references · ${record.reference}`;
    definition.footer = (page, total) => ({
        columns: [
            { text: `${record.reference}  |  Legal references` },
            { text: `Page ${page} of ${total}`, alignment: 'right' },
        ],
        margin: [46, 12, 46, 0], color: muted, fontSize: 8,
    });
    definition.content = [
        { text: firm, style: 'firm' },
        { text: 'Legal references', fontSize: 11, color: muted },
        { text: record.title, style: 'title' },
        { text: `${record.reference} · Prepared ${generated}`, color: muted },
        { text: `Client: ${record.client || 'Not recorded'} · Owner: ${record.owner || 'Not recorded'}` },
        { text: `Status: ${record.status} · ${references.length} references`, margin: [0, 4, 0, 8] },
        ...(references.length ? references.flatMap((reference, index) => [
            { text: `${index + 1}. ${reference.title}`, style: 'section' },
            { text: reference.type, bold: true },
            ...[['Citation / section / clause', reference.citation], ['Jurisdiction / court', reference.jurisdiction], ['Source URL', reference.source]].filter(([, value]) => value).map(([label, value]) => ({ text: [{ text: label + ': ', bold: true }, { text: value }], margin: [0, 3, 0, 0] })),
            { text: 'Why it applies', style: 'subheading' },
            { text: reference.applicability || 'Not recorded' },
        ]) : [{ text: 'No references recorded for this case.' }]),
    ];
    return definition;
}

let pdfLibrary;
function loadPdfLibrary() {
    pdfLibrary ??= Promise.all([import('pdfmake/build/pdfmake'), import('pdfmake/build/vfs_fonts')])
        .then(([library, fonts]) => {
            library.default.addVirtualFileSystem(fonts.default);
            return library.default;
        })
        .catch((error) => {
            pdfLibrary = undefined;
            throw error;
        });

    return pdfLibrary;
}

class CaseSummary extends HTMLElement {
    connectedCallback() {
        this.addEventListener('click', this.handleClick);
    }

    disconnectedCallback() {
        this.removeEventListener('click', this.handleClick);
        this.request?.abort();
        this.clearPreview();
    }

    handleClick = (event) => {
        if (event.target.closest('[data-summary-open]')) {
            this.prepare();
        } else if (event.target.closest('[data-summary-download]') && this.previewUrl) {
            const link = document.createElement('a');
            link.href = this.previewUrl;
            link.download = this.filename;
            link.click();
        } else if (event.target.closest('[data-summary-print]') && this.pdf) {
            this.pdf.print();
        }
    };

    clearPreview() {
        this.pdf = undefined;
        if (this.previewUrl) {
            URL.revokeObjectURL(this.previewUrl);
            this.previewUrl = undefined;
        }
    }

    async prepare() {
        this.request?.abort();
        this.clearPreview();
        this.request = new AbortController();
        const { signal } = this.request;

        const status = this.querySelector('[data-summary-status]');
        const frame = this.querySelector('[data-summary-preview]');
        const buttons = this.querySelectorAll('[data-summary-print], [data-summary-download]');
        status.hidden = false;
        status.textContent = 'Preparing PDF preview…';
        frame.hidden = true;
        frame.removeAttribute('src');
        buttons.forEach((button) => { button.disabled = true; });

        try {
            const response = await fetch(this.dataset.summaryUrl, {
                signal,
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Case summary request failed.');
            const summary = await response.json();
            const library = await loadPdfLibrary();
            const isReferences = this.dataset.reportKind === 'references';
            const pdf = library.createPdf(isReferences ? caseReferencesDefinition(summary) : caseSummaryDefinition(summary));
            const blob = await new Promise((resolve) => pdf.getBlob(resolve));
            if (signal.aborted) return;

            this.pdf = pdf;
            this.previewUrl = URL.createObjectURL(blob);
            this.filename = `${summary.case.reference}-${isReferences ? 'legal-references' : 'case-summary'}.pdf`;
            frame.src = this.previewUrl;
            frame.hidden = false;
            status.hidden = true;
            buttons.forEach((button) => { button.disabled = false; });
        } catch {
            if (!signal.aborted) {
                status.textContent = 'Could not prepare the summary. Close this window and try again.';
            }
        }
    }
}

customElements.define('case-summary', CaseSummary);

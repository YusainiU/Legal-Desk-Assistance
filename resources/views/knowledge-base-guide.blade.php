<x-layouts.app>
    <style>
        .knowledge-guide { color: inherit; overflow-wrap: anywhere; line-height: 1.8; }
        .knowledge-guide > :first-child { margin-top: 0; }
        .knowledge-guide h1, .knowledge-guide h2, .knowledge-guide h3, .knowledge-guide h4 { font-weight: 700; line-height: 1.35; margin: 1.8em 0 .65em; }
        .knowledge-guide h1 { font-size: 1.8rem; }
        .knowledge-guide h2 { font-size: 1.45rem; padding-bottom: .35em; border-bottom: 1px solid #71717a55; }
        .knowledge-guide h3 { font-size: 1.2rem; }
        .knowledge-guide h4 { font-size: 1.05rem; }
        .knowledge-guide p, .knowledge-guide ul, .knowledge-guide ol, .knowledge-guide blockquote, .knowledge-guide pre, .knowledge-guide table { margin: .9em 0 1.2em; }
        .knowledge-guide ul { list-style: disc; padding-left: 1.7rem; }
        .knowledge-guide ol { list-style: decimal; padding-left: 1.7rem; }
        .knowledge-guide li { padding-left: .15rem; margin: .35em 0; }
        .knowledge-guide li > ul, .knowledge-guide li > ol { margin: .4em 0; }
        .knowledge-guide a { color: #38bdf8; text-decoration: underline; text-underline-offset: 3px; }
        .knowledge-guide a:hover { color: #7dd3fc; }
        .knowledge-guide blockquote { border-left: 4px solid #71717a; padding: .55rem 1rem; background: #71717a16; }
        .knowledge-guide code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .9em; background: #71717a25; border-radius: .25rem; padding: .1em .3em; }
        .knowledge-guide pre { padding: 1rem; overflow-x: auto; border-radius: .5rem; background: #18181b; }
        .knowledge-guide pre code { background: transparent; padding: 0; color: #f4f4f5; }
        .knowledge-guide table { width: 100%; border-collapse: collapse; display: block; overflow-x: auto; }
        .knowledge-guide th, .knowledge-guide td { border: 1px solid #71717a55; padding: .55rem .8rem; text-align: left; }
        .knowledge-guide th { font-weight: 700; background: #71717a18; }
        .knowledge-guide hr { border: 0; border-top: 1px solid #71717a55; margin: 2rem 0; }
        .knowledge-guide strong { font-weight: 700; }
        .knowledge-guide img { max-width: 100%; height: auto; }
    </style>
    <div class="mx-auto max-w-4xl space-y-6">
        <header class="space-y-2">
            <h1 class="desk-title">Knowledge Base User Guide</h1>
            <flux:text>Learn how to research, record, and reuse legal resources across cases.</flux:text>
        </header>

        <flux:card class="desk-panel">
            @php
                $guidePath = base_path('docs/knowledge-base-user-guide.md');
                $guideHtml = is_file($guidePath)
                    ? \Illuminate\Support\Str::markdown(file_get_contents($guidePath), [
                        'html_input' => 'strip',
                        'allow_unsafe_links' => false,
                    ])
                    : null;
            @endphp

            @if ($guideHtml)
                <article class="knowledge-guide max-w-none leading-relaxed">
                    {!! $guideHtml !!}
                </article>
            @else
                <flux:text>The user guide is not available. Make sure docs/knowledge-base-user-guide.md is included in your deployment.</flux:text>
            @endif
        </flux:card>
    </div>
</x-layouts.app>

<x-layouts.app>
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
                <article class="prose prose-zinc dark:prose-invert max-w-none leading-relaxed">
                    {!! $guideHtml !!}
                </article>
            @else
                <flux:text>The user guide is not available. Make sure docs/knowledge-base-user-guide.md is included in your deployment.</flux:text>
            @endif
        </flux:card>
    </div>
</x-layouts.app>

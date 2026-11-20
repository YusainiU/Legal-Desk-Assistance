@php
    $htmlConfig = \HTMLPurifier_Config::createDefault();
    $htmlConfig->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,strike,h1,h2,h3,h4,blockquote,ul,ol,li,a[href|title],span[style],div,table,thead,tbody,tr,th,td');
    $htmlConfig->set('CSS.AllowedProperties', ['color', 'background-color', 'text-align']);
    $htmlConfig->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
    $htmlPurifier = new \HTMLPurifier($htmlConfig);
@endphp
            <div class="space-y-4">
                <flux:heading size="xl">Case summary · MD-{{ $summaryMatter->id }}</flux:heading>
                <flux:heading size="lg">{{ $summaryMatter->title }}</flux:heading>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-zinc-400">Status</dt><dd>{{ $summaryMatter->status }}</dd></div>
                    <div><dt class="text-zinc-400">Client</dt><dd>{{ $summaryMatter->client }}</dd></div>
                    <div><dt class="text-zinc-400">Case owner</dt><dd>{{ $summaryMatter->owner }}</dd></div>
                    <div><dt class="text-zinc-400">Practice area</dt><dd>{{ $summaryMatter->practice }}</dd></div>
                    @if ($summaryMatter->party)
                        <div><dt class="text-zinc-400">Other party</dt><dd>{{ $summaryMatter->party }}</dd></div>
                    @endif
                </dl>
                @if ($summaryMatter->description)
                    <div class="space-y-1">
                        <flux:heading size="sm">Case description</flux:heading>
                        <p class="whitespace-pre-wrap wrap-break-word text-sm">{{ $summaryMatter->description }}</p>
                    </div>
                @endif
                @if ($summaryMatter->final_decision)
                    <div class="space-y-1">
                        <flux:heading size="sm">Final decision</flux:heading>
                        <div class="legal-markdown max-w-none wrap-break-word text-sm">{!! $htmlPurifier->purify($summaryMatter->final_decision) !!}</div>
                    </div>
                @endif
                <div class="space-y-2">
                    <flux:heading size="sm">Case history</flux:heading>
                    <div class="max-h-64 overflow-y-auto">
                        <ul class="divide-y divide-zinc-700">
                            @forelse ($summaryMatter->entries as $entry)
                                <li class="py-2 text-sm">
                                    <div class="text-xs text-zinc-400">{{ $entry->kind }} · {{ $entry->created_at->format('d M Y, H:i') }}</div>
                                    <p class="whitespace-pre-wrap wrap-break-word">{{ $entry->body }}</p>
                                </li>
                            @empty
                                <li class="py-2 text-sm">No case history recorded.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

            </div>

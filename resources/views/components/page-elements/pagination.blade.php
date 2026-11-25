@props(['paginator', 'label' => 'cases', 'livewire' => true])

@if ($paginator->total() > 0)
    <p class="text-center text-sm text-zinc-500 dark:text-zinc-400">
        Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }} {{ $label }}
    </p>
@endif



@if ($paginator->hasPages())
    <nav
        role="navigation"
        aria-label="Pagination Navigation"
        class="flex flex-wrap items-center justify-center gap-2"
    >
        {{-- Previous page --}}
        @if ($paginator->onFirstPage())
            <span class="rounded bg-slate-100 px-3 py-2 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                Previous
            </span>
        @else
            @if ($livewire)
                <button
                type="button"
                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                wire:loading.attr="disabled"
                rel="prev"
                class="rounded bg-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
            >
                Previous
            </button>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                rel="prev"
                class="rounded bg-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
            >
                Previous
            </a>
            @endif
        @endif

        {{-- Page numbers --}}
        @php
            $pages = collect([1, $paginator->lastPage()])
                ->merge(range(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)))
                ->unique()->sort()->values();
        @endphp
        @foreach ($pages as $page)
            @if (!$loop->first && $page > $pages[$loop->index - 1] + 1)
                <span class="px-2 text-slate-500" aria-hidden="true">…</span>
            @endif
            @if ($page == $paginator->currentPage())
                <span
                    aria-current="page"
                    class="rounded bg-slate-800 px-3 py-2 text-white dark:bg-indigo-500/20 dark:text-indigo-300"
                >
                    {{ $page }}
                </span>
            @else
                @if ($livewire)
                <button
                    type="button"
                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    class="rounded bg-slate-100 px-3 py-2 text-slate-700 hover:bg-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                    {{ $page }}
                </button>
            @else
                <a href="{{ $paginator->url($page) }}"
                    class="rounded bg-slate-100 px-3 py-2 text-slate-700 hover:bg-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                    {{ $page }}
                </a>
            @endif
            @endif
        @endforeach

        {{-- Next page --}}
        @if ($paginator->hasMorePages())
            @if ($livewire)
                <button
                type="button"
                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                wire:loading.attr="disabled"
                rel="next"
                class="rounded bg-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
            >
                Next
            </button>
            @else
                <a href="{{ $paginator->nextPageUrl() }}"
                rel="next"
                class="rounded bg-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
            >
                Next
            </a>
            @endif
        @else
            <span class="rounded bg-slate-100 px-3 py-2 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                Next
            </span>
        @endif
    </nav>
@endif

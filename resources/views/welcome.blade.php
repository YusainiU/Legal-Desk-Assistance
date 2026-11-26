<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Legal Desk Assistance brings case management, nine-stage workflows, legal knowledge and AI-assisted research together for modern law firms.">
    <title>Legal Desk Assistance — Case Management &amp; AI Legal Research</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-[#091327] font-sans text-slate-100 antialiased">
    <div class="overflow-hidden bg-[radial-gradient(ellipse_at_20%_0%,#213c68_0%,transparent_48%),radial-gradient(ellipse_at_90%_35%,#10354b_0%,transparent_42%)]">
        <header class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-6 sm:px-8">
            <a href="{{ route('welcome') }}" class="flex items-center gap-3 font-semibold tracking-tight">
                <span aria-hidden="true" class="grid size-11 place-items-center rounded-xl border border-amber-400/40 bg-amber-400/10 text-2xl text-amber-300">⚖</span>
                <span class="text-lg sm:text-xl">Legal Desk <span class="text-amber-300">Assistance</span></span>
            </a>
            <nav aria-label="Main navigation" class="flex items-center gap-3 sm:gap-6">
                <a href="#features" class="hidden text-sm text-slate-300 hover:text-white sm:inline">Features</a>
                <a href="#workflow" class="hidden text-sm text-slate-300 hover:text-white sm:inline">Workflow</a>
                <a href="#research" class="hidden text-sm text-slate-300 hover:text-white sm:inline">AI Research</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-amber-300 px-4 py-2.5 text-sm font-bold text-slate-950 hover:bg-amber-200">Open workspace</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg bg-amber-300 px-4 py-2.5 text-sm font-bold text-slate-950 hover:bg-amber-200">Sign in</a>
                @endauth
            </nav>
        </header>

        <main>
            <section class="mx-auto grid max-w-7xl items-center gap-12 px-5 pb-20 pt-14 sm:px-8 lg:grid-cols-2 lg:py-24">
                <div class="space-y-7">
                    <p class="inline-flex rounded-full border border-amber-300/30 bg-amber-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[.16em] text-amber-200">Case management + AI-assisted legal research</p>
                    <h1 class="text-4xl font-extrabold leading-[1.12] tracking-tight sm:text-5xl xl:text-6xl">From paper files to <span class="text-amber-300">digital case management.</span></h1>
                    <p class="max-w-xl text-lg leading-8 text-slate-300">Organise every case. Track every stage. Build your firm's legal knowledge. Bring client records, tasks, documents, legal references and case progress into one structured workspace.</p>
                    <div class="flex flex-wrap gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-xl bg-amber-300 px-6 py-3 font-semibold text-slate-950 hover:bg-amber-200">Go to dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-xl bg-amber-300 px-6 py-3 font-semibold text-slate-950 hover:bg-amber-200">Sign in to your workspace</a>
                        @endauth
                        <a href="#features" class="rounded-xl border border-slate-500/60 px-6 py-3 font-semibold hover:border-amber-300 hover:text-amber-200">Explore features</a>
                    </div>
                    <p class="text-sm text-slate-400">Built for legal practitioners seeking a more organised approach to everyday casework.</p>
                </div>
                <div class="relative rounded-3xl border border-slate-600/50 bg-slate-800/70 p-5 shadow-2xl shadow-black/40 sm:p-7">
                    <div class="mb-5 flex items-center justify-between border-b border-slate-600/60 pb-4">
                        <div><p class="text-xs uppercase tracking-widest text-amber-300">Case overview</p><h2 class="mt-1 text-xl font-bold">Your legal workspace</h2></div>
                        <span class="rounded-full bg-teal-400/15 px-3 py-1 text-xs font-semibold text-teal-200">In progress</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-[#121e35] p-4"><p class="text-xs text-slate-400">Case register</p><p class="mt-2 text-lg font-semibold">Centralised</p></div>
                        <div class="rounded-xl bg-[#121e35] p-4"><p class="text-xs text-slate-400">Workflow</p><p class="mt-2 text-lg font-semibold">9 stages</p></div>
                        <div class="rounded-xl bg-[#121e35] p-4"><p class="text-xs text-slate-400">Knowledge Base</p><p class="mt-2 text-lg font-semibold">Searchable</p></div>
                        <div class="rounded-xl bg-[#121e35] p-4"><p class="text-xs text-slate-400">Legal research</p><p class="mt-2 text-lg font-semibold text-amber-200">AI assisted</p></div>
                    </div>
                    <div class="mt-5 rounded-xl border border-teal-400/30 bg-teal-400/5 p-4">
                        <div class="flex items-center justify-between gap-3"><span class="font-semibold">Case progress</span><span class="text-xs text-teal-200">Structured workflow</span></div>
                        <div class="mt-4 grid grid-cols-9 gap-1.5" aria-label="Nine-stage workflow illustration">@for ($i = 1; $i <= 9; $i++)<span class="h-2 rounded-full {{ $i <= 4 ? 'bg-teal-400' : 'bg-slate-600' }}"></span>@endfor</div>
                        <p class="mt-3 text-xs text-slate-400">Illustrative interface overview — not live case data.</p>
                    </div>
                </div>
            </section>

            <section id="features" class="border-y border-white/10 bg-slate-950/35 py-20 scroll-mt-10">
                <div class="mx-auto max-w-7xl px-5 sm:px-8">
                    <p class="text-sm font-bold uppercase tracking-widest text-amber-300">One connected workspace</p>
                    <h2 class="mt-3 max-w-3xl text-3xl font-bold sm:text-4xl">Everything your practice needs to keep casework organised.</h2>
                    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            ['Case management', 'Maintain client, party, case owner, team and progress information in one case record.', '01'],
                            ['Structured workflow', 'Follow nine defined stages with checklists, notes and clear next actions.', '02'],
                            ['AI Legal Search', 'Explore potential legal authorities with AI assistance and verify original sources.', '03'],
                            ['Legal Knowledge Base', 'Save and search judgments, legal references, research notes and case-linked resources.', '04'],
                            ['Teams and tasks', 'Assign responsibilities, organise staff and track outstanding work.', '05'],
                            ['Documents and case history', 'Keep documents, recorded actions and important decisions connected to the case.', '06'],
                            ['Time and charges', 'Log time, review charges and export records for further processing.', '07'],
                            ['Court Judgement Builder', 'Index judgment text from authorised sources for reference and future research.', '08'],
                        ] as [$heading, $description, $number])
                            <article class="rounded-2xl border border-slate-700/70 bg-slate-800/60 p-6">
                                <span class="text-sm font-bold text-amber-300">{{ $number }}</span>
                                <h3 class="mt-4 text-xl font-semibold">{{ $heading }}</h3>
                                <p class="mt-3 leading-7 text-slate-300">{{ $description }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="workflow" class="mx-auto max-w-7xl px-5 py-20 sm:px-8 scroll-mt-10">
                <p class="text-sm font-bold uppercase tracking-widest text-amber-300">Case workflow</p>
                <h2 class="mt-3 text-3xl font-bold sm:text-4xl">From the first interview to file closure.</h2>
                <p class="mt-4 max-w-3xl leading-7 text-slate-300">A consistent nine-stage process helps your firm see where a case stands, what has been recorded and what remains to be done.</p>
                <ol class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (['Interview client', 'Analyse the facts', 'Decide whether to take the case', 'Warrant to act & retainer', 'Attend to the case', 'Interim invoices', 'Update the client', 'Conclude & issue final invoice', 'Close the file'] as $stage)
                        <li class="flex items-center gap-4 rounded-xl border border-slate-700/70 bg-slate-800/50 p-4"><span class="grid size-10 shrink-0 place-items-center rounded-lg bg-amber-300/15 font-bold text-amber-300">{{ $loop->iteration }}</span><span class="font-medium">{{ $stage }}</span></li>
                    @endforeach
                </ol>
            </section>

            <section id="research" class="border-y border-amber-300/15 bg-gradient-to-br from-[#12284a] to-[#0a1528] py-20 scroll-mt-10">
                <div class="mx-auto grid max-w-7xl gap-10 px-5 sm:px-8 lg:grid-cols-2 lg:items-center">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-widest text-amber-300">Knowledge Base + AI Legal Search</p>
                        <h2 class="mt-3 text-3xl font-bold leading-tight sm:text-4xl">Discover. Verify. Organise. Reuse.</h2>
                        <p class="mt-5 text-lg leading-8 text-slate-300">Explore public legal sources with AI assistance, build a searchable library of legal research, index authorised court judgments and link relevant references to individual cases.</p>
                        <p class="mt-5 rounded-xl border border-amber-300/30 bg-amber-300/10 p-4 text-sm leading-6 text-amber-100">AI-generated research is preliminary. Legal practitioners must verify citations, judgments and authorities against original sources before relying on them.</p>
                    </div>
                    <div class="grid gap-3">
                        @foreach ([['AI-assisted discovery', 'Ask research questions and explore potential authorities.'], ['Court judgments and citations', 'Keep authorised judgment records and relevant citations organised.'], ['Reusable legal knowledge', 'Retain research notes and case references in a searchable library.'], ['Case-linked references', 'Connect relevant resources to the matters they support.']] as [$title, $detail])
                            <div class="rounded-xl border border-slate-500/40 bg-slate-950/40 p-5"><h3 class="font-semibold text-amber-200">{{ $title }}</h3><p class="mt-2 text-sm leading-6 text-slate-300">{{ $detail }}</p></div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-4xl px-5 py-24 text-center sm:px-8">
                <h2 class="text-3xl font-bold sm:text-4xl">Ready to modernise your legal practice?</h2>
                <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-slate-300">Move from scattered files and disconnected research to a clearer, more organised way of working.</p>
                <div class="mt-8">
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="inline-block rounded-xl bg-amber-300 px-7 py-3.5 font-bold text-slate-950 hover:bg-amber-200">{{ auth()->check() ? 'Open your workspace' : 'Sign in to Legal Desk Assistance' }}</a>
                </div>
            </section>
        </main>
        <footer class="border-t border-white/10 px-5 py-7 text-center text-sm text-slate-400">© {{ date('Y') }} Legal Desk Assistance. Case management and AI-assisted legal research.</footer>
    </div>
</body>
</html>
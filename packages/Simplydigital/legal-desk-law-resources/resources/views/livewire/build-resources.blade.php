<div class="space-y-6">
    <header class="space-y-2">
        <h1 class="desk-title">Build Resources</h1>
        <flux:text>Develop your legal knowledge base by researching legal authorities and saving useful findings to your Library.</flux:text>
    </header>

    <div class="grid gap-4 md:grid-cols-2">
        <flux:card class="desk-panel space-y-3">
            <flux:heading size="lg">Search internal resources</flux:heading>
            <flux:text>Find legal resources already recorded in your case records.</flux:text>
            <flux:button :href="route('legal-research', ['mode' => 'internal'])" variant="primary">Search internal resources</flux:button>
        </flux:card>
        <flux:card class="desk-panel space-y-3">
            <flux:heading size="lg">Search AI resources</flux:heading>
            <flux:text>Research public legal sources with AI and save useful findings.</flux:text>
            <flux:button :href="route('ai-resource-search')" variant="primary">Search AI resources</flux:button>
        </flux:card>
        <flux:card class="desk-panel space-y-3 md:col-span-2">
            <flux:heading size="lg">Library</flux:heading>
            <flux:text>Browse your saved AI legal research and internal case references, review details, and copy references to cases.</flux:text>
            <flux:button :href="route('library')" variant="outline">Open Library</flux:button>
        </flux:card>
    </div>
</div>

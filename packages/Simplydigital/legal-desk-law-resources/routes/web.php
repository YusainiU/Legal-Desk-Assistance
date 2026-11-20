<?php

use Illuminate\Support\Facades\Route;
use Simplydigital\LegalDeskLawResources\Http\Controllers\LegalJudgmentController;
use Simplydigital\LegalDeskLawResources\Livewire\KnowledgeBase;
use Simplydigital\LegalDeskLawResources\Livewire\LegalResearchDesk;
use Simplydigital\LegalDeskLawResources\Livewire\AiResourceSearch;
use Simplydigital\LegalDeskLawResources\Livewire\AiSearchAdmin;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/knowledge-base', KnowledgeBase::class)->name('knowledge-base');
    Route::get('/legal-research', LegalResearchDesk::class)->name('legal-research');
    Route::get('/ai-resource-search', AiResourceSearch::class)->name('ai-resource-search');
    Route::get('/ai-search-admin', AiSearchAdmin::class)->middleware('can:manage-users')->name('ai-search-admin');
    Route::post('/legal-research/judgments', [LegalJudgmentController::class, 'store'])
        ->middleware(['can:manage-users', 'throttle:10,1'])->name('legal-research.import');
    Route::get('/legal-research/cases/{legalCase}', [LegalJudgmentController::class, 'show'])
        ->name('legal-research.cases.show');
});

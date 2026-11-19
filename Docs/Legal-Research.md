# Malaysian Case Law Research — administrator guide

This module extends the existing Laravel 13 / Livewire 4 / MySQL application.

## First deployment

1. Deploy the feature branch after reviewing the pull request.
2. Run `php artisan migrate`.
3. From the authenticated sidebar, open **Case Law Research**.
4. An administrator can import judgment text **only when its use is authorised**.
5. Enter a research question and optionally link it to one of your own open matters.
6. Open each indexed judgment and compare the passages with the original source.
7. Optionally add an authority to the matter's existing **Case references**.

No commercial publisher is scraped, no case law is preloaded, and imports start as
`unverified`. The importer accepts plain judgment text, not PDF or OCR. It
deliberately does **not** fabricate judicial paragraph numbers when splitting text.

## Optional AI summary

Set `OPENAI_API_KEY` in the deployment environment. Optionally set
`LEGAL_RESEARCH_MODEL` (default: `gpt-4.1-mini`). Run `php artisan config:clear`.
Do not send privileged client information to external AI services without
authorisation, appropriate contractual safeguards, and applicable privacy review.

The generated draft is **not** a verified legal opinion. The service restricts
passage IDs to the retrieved passages but cannot independently prove the legal
conclusions or whether a case was subsequently overruled.

## Retrieval limits and planned extensions

The initial search is keyword-based and runs on MySQL with no additional
dependencies. It searches indexed text; it is **not** a nationwide live
case-law database. Later phases can add:
- Licensed feeds and provenance-based ingestion.
- True paragraph-level PDF extraction and source-checking.
- MySQL FULLTEXT / vector retrieval, reranking and bilingual synonyms.
- Verified judicial-treatment graph and claim-level citation support.
- Research exports and comparative legal memoranda.

## Access controls

Only admins may ingest judgments. All authenticated users may browse shared
indexed judgments. Research sessions are restricted to their owning users,
and a research session can be linked only to an open matter owned by that user.
Existing ownership restrictions on case references remain in place.

## Verification caveats

`source_verified` is reserved for a future human verification workflow; the
MVP does not automatically set it. A citation's existence in the index does not
verify the proposition or subsequent treatment. Never use AI research in court
filings without checking the original judgment.

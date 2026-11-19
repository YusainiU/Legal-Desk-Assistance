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

## Public judgment source discovery (first integration stage)

The research desk now links to three public discovery sources:
- Malaysian Judiciary official e-Judgment portal: https://ejudgment.kehakiman.gov.my/ejudgmentweb/SearchPage.aspx?JurisdictionType=ALL
- CommonLII Malaysian decisions: external site-restricted web search of `commonlii.org/my/cases/`
- MY Case Law independent index: native search at `https://malaysiacaselaw.com/search?q=...`

CommonLII uses Google site-restricted search; MY Case Law uses its native `/search?q=` endpoint. Both pass the user's currently entered keywords in the URL. The official court link opens its own search form. They do **not** query external APIs, download PDFs, scrape pages, index results into MySQL, or verify citations. They are a usable discovery starting point, not integrated live search. Public access alone is not a licence to bulk ingest or republish.

Next phase: evaluate source-specific terms and available official APIs/feeds, then add permitted retrieval of judgment metadata and document content with source attribution and human verification.

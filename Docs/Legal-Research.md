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
- CommonLII Malaysian decisions: native SinoSearch at `https://commonlii.org/cgi-bin/sinosrch.cgi?meta=/commonlii&query=...&method=auto&mask_world=:commonlii:my&mask_path=+my`
- MY Case Law independent index: native search at `https://malaysiacaselaw.com/search?q=...`

CommonLII uses its native SinoSearch endpoint with Malaysian jurisdiction filters; MY Case Law uses its native `/search?q=` endpoint. Both pass the user's currently entered keywords in the URL. The official court link opens its own search form. They do **not** query external APIs, download PDFs, scrape pages, index results into MySQL, or verify citations. They are a usable discovery starting point, not integrated live search. Public access alone is not a licence to bulk ingest or republish.

Next phase: evaluate source-specific terms and available official APIs/feeds, then add permitted retrieval of judgment metadata and document content with source attribution and human verification.

## AI public web research

Set `OPENAI_API_KEY` and run `php artisan config:clear`. Use **AI Search (public web)** to request web-grounded Malaysian legal research through OpenAI Responses API `web_search_preview`. This requires supported API model, billing and outbound internet. Research text and source URLs are saved in the existing `analysis` field, labelled `web_research_unverified`. URL links are displayed as plain text in the analysis output; users can copy them to review original sources. No web content is automatically imported into local judgments. An answer without cited web URLs is rejected. Web results may be incomplete or incorrect and must be verified against original judgments. Do not submit confidential client facts without appropriate authorisation.

## AI Search troubleshooting and compatibility

This implementation follows the Responses API pattern already used by the Case-Management-Application: Laravel `Http::withToken()`, configurable `OPENAI_BASE_URL`, JSON response extraction and provider HTTP error inspection. Unlike that app's ordinary text-generation request, legal web research requires the Responses `web_search` tool (the current documented tool, not legacy `web_search_preview`).

Optional `.env` values: `OPENAI_BASE_URL=https://api.openai.com/v1`, `LEGAL_RESEARCH_WEB_SEARCH_MODEL=gpt-4.1-mini`. Set `OPENAI_API_KEY` and run `php artisan optimize:clear`. The research UI now shows sanitized provider HTTP error status/code/message for 400/401/403/429 etc.; sensitive keys are redacted. Source URLs still require verification against real judgments. API web-search access and account billing may differ from ordinary AI text generation. Check `storage/logs/laravel.log` for unexpected connection errors. No migration required for this fix.

## Save AI research to Knowledge Base

The **Save AI result to Knowledge Base** button appears on successful public-web AI research results. It copies the question and AI analysis into the user-owned `saved_legal_research` table, with a unique `(user_id, legal_research_id)` constraint preventing duplicates. Notes appear in Knowledge Base independently of a client matter, labelled unverified. They are not inserted into verified `CaseReference` entries. Removing a note does not delete its original research history. Run `php artisan migrate` after pulling this feature. Stored AI analysis may contain sensitive client information: use the feature only with authorised research prompts.

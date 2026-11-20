# Legal Desk Law Resources

Laravel 13 / Livewire 4 package providing Malaysian case law research, indexed judgment storage, citation checks, AI web research, and saved research notes.

## Install in Legal Desk Assistance

The host project's `composer.json` contains a path repository and requires `simplydigital/legal-desk-law-resources:@dev`.

From the project root:

```bash
composer update simplydigital/legal-desk-law-resources -W
php artisan package:discover
php artisan route:list --path=legal-research
php artisan migrate:status
php artisan test
```

Do **not** run `migrate:fresh` on a database containing real records. The package owns the existing migration filenames so migration history should remain recognised.

## Configuration

Publish configuration with:

```bash
php artisan vendor:publish --tag=legal-desk-law-resources-config
```

The host must provide compatible `matter_model` and `reference_model` Eloquent models if it enables case-reference functionality. Both models must support the relationships used by the supplied Livewire screens. The package currently defaults these to Legal Desk Assistance models; this is an integration seam, not yet a host-independent adapter.

The package uses `services.openai.key` and `services.openai.base_url`, and environment variables `LEGAL_RESEARCH_MODEL` and `LEGAL_RESEARCH_WEB_SEARCH_MODEL`.

## Routes

- `knowledge-base`: `/knowledge-base`
- `legal-research`: `/legal-research`
- `legal-research.import`: `POST /legal-research/judgments`
- `legal-research.cases.show`: `/legal-research/cases/{legalCase}`

The supplied UI requires Livewire, Flux and the host's `components.layouts.app` layout. The judgment import action requires a `manage-users` gate.

## Verification before deployment

This is an in-progress extraction. The original host classes remain temporarily for backward compatibility; host-specific case adapters and test coverage still need to be completed. Run Composer locally, inspect migration status and execute the test suite before deployment.

AI research is unverified and must be checked against original judgments and later treatment. Import only material you are authorised to store.

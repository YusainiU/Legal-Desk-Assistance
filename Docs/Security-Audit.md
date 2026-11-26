# Pre-publication security review

Date: 2026-11-26
Reviewed branch: feature/extract-law-resources-package
Snapshot: 34358a19750ca8735d8247907c711a34c4d601fb (file inspection performed against the branch; no runtime verification).

## Scope and limitations
Targeted static inspection of authentication, user administration, document access/upload, case tasks, references, final decisions, email, CSV exports, login audit, and the extracted legal resources package. Filename inspection of 194 tracked files found no obvious credential/database/log files except the intended .env.example. This is not an exhaustive source or history scan, penetration test, dependency audit, or certification.

## Findings and remediation
| ID | Priority | Finding | Recommended action |
|---|---|---|---|
| S-01 | High design risk | CaseDocumentController relies on authenticated shared access, not case-specific policies | Centralize case/document authorization; document single-firm scope |
| S-02 | Medium | Package case selectors and references retain creator scoping | Define shared case versus private research access explicitly |
| S-03 | Medium | CaseFinalDecision and display allow remote images | Remove image allowance consistently |
| S-04 | Low | HTMLPurifier configuration duplicated | Centralize sanitization |
| S-05 | High | No application-specific AI search/analysis quota | Enforce per-user and firm-wide limits on both AI entry points |
| S-06 | High | External AI receives questions; indexed analysis also sends passages | Explicit opt-in, confidentiality notice, provider privacy review |
| S-07 | Medium | Provider error text returned/logged | Generic messages and controlled diagnostics |
| S-08 | Safeguard | AI output marked unverified | Retain independent citation review |
| S-09 | Medium | Uploads lack malware scanning | Optional antivirus/quarantine |
| S-10 | Low | Filesystem deletion cannot roll back with database | Deferred deletion and cleanup |
| S-11 | Safeguard | Fresh super admin has random password and disabled access | Retain explicit setup; review existing-account promotion |
| S-12 | Medium | Fixed demo password, environment guard only | Random or installer-provided demo passwords |
| S-13 | Medium | Login audit coverage and retention incomplete | Verify events, retention, failure handling |
| S-14 | Medium | Case activity audit incomplete | Structured actor/action/record/time audit |
| S-15 | Medium | Case email lacks explicit send quota | Rate limit and monitor outbound delivery |

## Existing controls
Fortify login throttling and 2FA support; active-user provider; explicit admin gates; locked Livewire identifiers; private document storage, extension/MIME/size validation and restrictive preview headers; HTMLPurifier and safe Markdown options in inspected views; CSV formula-injection mitigation; linked-contact email validation and duplicate-send token. These controls require integration tests.

## Release gates
- Scan all Git refs/history and working tree with a secret scanner; rotate any exposed secrets.
- Run composer validate --strict, composer audit, composer check-platform-reqs, npm audit, npm ci and npm run build.
- Review binary documentation metadata and redistribution rights.
- Verify authentication, revoked sessions, role boundaries, Livewire actions, document access, XSS, AI quotas, and task deadline behavior using automated tests and a clean deployment.
- Confirm production HTTPS, APP_DEBUG=false, secure session cookies, private backups and data retention.
- Resolve high-priority findings or document accepted risks before public release.

No application code was modified by this review. No credentials, repository visibility or history were changed.

### S-03 / S-04 remediation

Centralized legal HTML sanitization in the law resources package (Support/LegalHtmlSanitizer). Final decision save, editor initialization, publication, case closure validation, summary and Library rendering use the shared sanitizer. AI Markdown display also passes through it to remove embedded images. Existing stored HTML is not rewritten automatically; legacy images are removed on these read paths and on subsequent saves. Supporting images should be attached as case documents. Package declares HTMLPurifier explicitly; refresh the Composer lock for the path package. Unit tests added; runtime and browser verification pending. This does not certify unrelated HTML rendering or the rich editor's own upload endpoints.

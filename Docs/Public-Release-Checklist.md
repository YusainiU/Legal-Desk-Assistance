# Public release checklist

Keep the repository private until the maintainer completes these checks.

- [ ] Choose a licence for the application and bundled package; confirm third-party redistribution terms.
- [ ] Review all branches, tags and Git history for secrets, personal paths and confidential data. Rotate any exposed credentials; deleting current files does not remove history.
- [ ] Review the PDF and DOCX guides for personal information, metadata and redistribution rights.
- [ ] Review authorization, private document downloads, HTML sanitization, login auditing and AI data handling.
- [ ] Verify a clean clone on a separate machine using PHP 8.4.1 or later, compatible Node.js and MySQL.
- [ ] Run composer validate, composer audit, npm ci, npm audit and npm run build.
- [ ] Verify lockfile consistency and public dependency availability.
- [ ] Establish a working automated test bootstrap and run tests for authentication, roles, shared cases, archives, task deadlines, dashboard and knowledge base.
- [ ] Confirm production debug is disabled and demo seeders cannot run in production.
- [ ] Review and merge the development branch into the intended default branch.
- [ ] Enable private vulnerability reporting, secret scanning and dependency alerts where available.
- [ ] Publish only after maintainer approval.

This checklist is not a certification that the repository is safe to publish.

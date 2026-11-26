# Security policy

This project handles potentially confidential legal records. Do not include real client information, credentials, API keys, documents or login audit records in public issues or pull requests.

Report suspected vulnerabilities privately using GitHub private vulnerability reporting if enabled, or contact the repository maintainer privately. Do not publish exploit details before a fix is available.

## Deployment precautions

- Use HTTPS, a dedicated database account and a unique application key.
- Set APP_ENV=production and APP_DEBUG=false.
- Serve only the public directory. Keep case attachments private.
- Restrict access to the firm: authenticated staff share case and contact records. This is not a multi-tenant isolation boundary.
- Use strong passwords, two-factor authentication and restricted administrative access.
- Back up the database and private attachments securely; establish retention policies for login audit records.
- Review AI provider data handling before submitting confidential information. Verify legal citations independently.
- Regularly audit Composer and npm dependencies.

No independent security audit or production suitability certification is claimed.

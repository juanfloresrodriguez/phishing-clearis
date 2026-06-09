# SECURITY.md – ClearPhish Security Policy

## Purpose and Authorized Use

ClearPhish is an **internal security awareness tool**. It is designed and permitted **only** for:

- Authorized internal phishing simulation campaigns with organizational consent
- Security awareness training programs
- Red team exercises with explicit written authorization

**It must NOT be used for:**
- Attacks against external organizations or individuals
- Real credential theft
- Bypassing security controls without authorization
- Any activity that violates applicable law or regulations

## What ClearPhish Does NOT Do

| Feature | Policy |
|---|---|
| Store real credentials | **Never.** Form values are discarded or SHA-256 hashed before storage |
| Domain spoofing of external orgs | **Not implemented.** Only verified organizational domains allowed |
| Spam filter evasion | **Not implemented.** No techniques to bypass legitimate security controls |
| Open relay usage | **Blocked.** Sending requires DNS-verified domain + authorized SMTP |
| Credential replay | **Not implemented.** No session hijacking or credential replay |
| Malware/macro attachments | **Blocked.** Attachment simulation is cosmetic only, no executable content |

## Data Protection

### Event Metadata (what we store)
- Email opened: timestamp, truncated IP (last octet removed), user-agent
- Link clicked: timestamp, truncated IP, user-agent, device type, browser
- Landing loaded: same as above
- Form submitted: field names, field types (password/email/text), whether field was filled – **NO values**
- Email reported: timestamp

### What we never store
- Passwords or password hashes submitted via landing pages
- Authentication tokens
- Full IP addresses (last octet is zeroed for IPv4)

### Retention
- Configurable per organization (`event_retention_days`)
- Optional anonymization after retention period
- Audit logs are retained separately

## DNS and Email Security Requirements

Before any campaign can run, the sending domain must have:

| Record | Requirement |
|---|---|
| SPF | Valid SPF record covering the sending server |
| DKIM | At least one active DKIM selector |
| DMARC | DMARC policy published at `_dmarc.domain` |
| Domain ownership | DNS TXT verification token placed by domain owner |

ClearPhish warns administrators if any of these are missing but **does not block on missing DKIM/SPF/DMARC** to support development environments. **In production, these should be enforced via organizational policy.**

## Authentication

- Passwords hashed with bcrypt (12 rounds)
- Optional 2FA (TOTP via Google Authenticator or compatible)
- Session invalidation on password change
- Login rate limiting via Laravel built-in throttling

## Audit Logging

All of the following actions are logged with user, IP, user-agent, and timestamp:

- `campaign.create` / `campaign.launch` / `campaign.pause` / `campaign.cancel`
- `template.create` / `template.update` / `template.send_test`
- `landing_page.create` / `landing_page.update`
- `target_user.import_csv` / `target_user.delete`
- `domain.add` / `domain.verify` / `domain.remove`
- `sending_profile.create` / `sending_profile.verify`
- `campaign.export_csv`
- `organization.update`

Audit logs are stored in the `audit_logs` table and are never automatically deleted.

## OWASP Controls

| Risk | Mitigation |
|---|---|
| SQL Injection | Eloquent ORM with parameterized queries |
| XSS | Inertia.js escaping + `e()` helper in template rendering |
| CSRF | Laravel CSRF tokens on all forms + Inertia CSRF header |
| SSRF | DNS verification uses system `dns_get_record()` only, no user-controlled HTTP |
| Broken Access Control | Organization-scoped queries + `abort_if(org_id !== user.org_id)` guards |
| Sensitive Data Exposure | Passwords hashed, SMTP credentials encrypted with `Crypt::encryptString()` |
| Mass Assignment | Explicit `$fillable` arrays on all models |

## Responsible Disclosure

If you discover a security vulnerability in ClearPhish, please report it to your organization's security team before public disclosure. Do not exploit vulnerabilities beyond what is necessary to demonstrate the issue.

## Legal Compliance

Organizations deploying ClearPhish must ensure:

1. **Written authorization** from organizational leadership
2. **Employee notification** in accordance with local labor law (e.g., works council notification in EU)
3. **Privacy policy** compliance (GDPR, CCPA, or applicable regulation)
4. **Data processing agreement** if operating as a service to third-party organizations
5. **Retention policy** configured to comply with local data retention requirements

The tracking data collected (IP truncated, user-agent, interaction metadata) may be considered personal data under GDPR. Configure `event_retention_days` and `anonymize_after_retention` accordingly.

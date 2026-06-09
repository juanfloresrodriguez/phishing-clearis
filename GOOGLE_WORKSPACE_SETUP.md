# Google Workspace Email Delivery Setup

This guide covers authorized methods for delivering simulation emails through Google Workspace infrastructure. **No spoofing, no relay abuse, no bypass of security controls.**

---

## Option A: Google Workspace SMTP Relay (Recommended for orgs)

This is the preferred method for organizations using Google Workspace.

### Prerequisites
- Google Workspace admin access
- A static IP or IP range for the ClearPhish server, OR
- OAuth2-authenticated SMTP (recommended)

### Steps

1. **Open Google Admin Console** → Apps → Google Workspace → Gmail → Routing

2. **Add an SMTP relay service** (Admin Console → Apps → Gmail → Routing → SMTP relay service):
   - Name: `ClearPhish Internal Simulation`
   - Allowed senders: **Only addresses in my domains**
   - Authentication: **Require SMTP Authentication** (recommended) OR **Only accept mail from the specified IP addresses** (enter your server's IP)
   - Require TLS: ✅ Yes

3. **Configure ClearPhish Sending Profile**:
   ```
   SMTP Host:       smtp-relay.gmail.com
   SMTP Port:       587
   Encryption:      TLS
   Username:        your-service-account@yourdomain.com
   Password:        App password or OAuth2 token
   From Email:      security-awareness@yourdomain.com
   ```

4. **Important**: The `From` address must be a real address in your Google Workspace domain.

### Allowlisting simulation emails (avoid spam filtering)

To prevent simulation emails from being marked as spam by Google's own filters:

1. **Admin Console** → Apps → Google Workspace → Gmail → Compliance
2. Add a **Content compliance rule** or **Inbound gateway** entry for the ClearPhish sending IP
3. Alternatively, create an **Email routing rule** for the sender address with "Skip spam classification" for messages from your authorized sending IP

> **Important**: Only apply this allowlist to the specific sending profile used for simulations. Do not globally disable spam filtering.

---

## Option B: Per-user App Password (Small teams)

For small organizations or testing:

1. Enable 2FA on the sending Google account
2. Go to Google Account → Security → App passwords
3. Generate an app password for "Mail"
4. Use in ClearPhish:
   ```
   SMTP Host:  smtp.gmail.com
   SMTP Port:  587
   Encryption: TLS
   Username:   your-address@yourdomain.com
   Password:   (generated app password)
   ```

> This method works but may hit rate limits at scale. Use SMTP Relay for large campaigns.

---

## Option C: Dedicated Authorized Subdomain

Create a subdomain specifically for awareness campaigns, e.g. `security-sim.yourdomain.com`.

### Benefits
- Clear DNS configuration
- Separate SPF/DKIM/DMARC policies
- Easy to allowlist in email filters
- Clear audit trail

### DNS Configuration Required

```dns
; SPF
security-sim.yourdomain.com. IN TXT "v=spf1 include:_spf.google.com ~all"

; DKIM (after generating via Google Admin)
google._domainkey.security-sim.yourdomain.com. IN TXT "v=DKIM1; k=rsa; p=..."

; DMARC
_dmarc.security-sim.yourdomain.com. IN TXT "v=DMARC1; p=quarantine; rua=mailto:security@yourdomain.com"

; Domain ownership verification for ClearPhish
security-sim.yourdomain.com. IN TXT "clearphish-verify=YOUR_TOKEN_HERE"
```

---

## SPF Configuration

The sending domain must have an SPF record that includes the mail server:

```
# For Google Workspace relay:
yourdomain.com. IN TXT "v=spf1 include:_spf.google.com ~all"

# For custom SMTP server + Google relay:
yourdomain.com. IN TXT "v=spf1 ip4:YOUR.SERVER.IP include:_spf.google.com ~all"
```

## DKIM Configuration

1. **Admin Console** → Apps → Google Workspace → Gmail → Authenticate email
2. Select domain → Generate new record
3. Publish the provided TXT record to DNS
4. Click "Start Authentication"

## DMARC Configuration

Start permissive, tighten over time:

```
# Phase 1 – Monitor only
_dmarc.yourdomain.com. IN TXT "v=DMARC1; p=none; rua=mailto:dmarc@yourdomain.com"

# Phase 2 – Quarantine failures
_dmarc.yourdomain.com. IN TXT "v=DMARC1; p=quarantine; pct=50; rua=mailto:dmarc@yourdomain.com"

# Phase 3 – Reject failures (full enforcement)
_dmarc.yourdomain.com. IN TXT "v=DMARC1; p=reject; rua=mailto:dmarc@yourdomain.com"
```

---

## Verifying DNS in ClearPhish

After configuring DNS:

1. Go to **Sending Profiles** in ClearPhish
2. Click **Check DNS** on your profile
3. ClearPhish will verify SPF, DKIM, and DMARC
4. Warnings will appear for any missing records
5. Click **Verify** to mark the profile as ready for campaigns

---

## What NOT to Do

- **Do not** use Gmail consumer accounts for bulk sending
- **Do not** configure open relays
- **Do not** set SPF to `+all` (allows any server to send as your domain)
- **Do not** disable DMARC enforcement just for simulations
- **Do not** use techniques that bypass Google's Workspace security policies
- **Do not** spoof external company domains (Google, Microsoft, etc.) using real DNS records you don't control

---

## Rate Limits

Google Workspace SMTP Relay has sending limits:
- **Standard Workspace**: up to 10,000 messages/day via SMTP relay
- **Enterprise**: higher limits, contact Google for details

Configure `rate_limit_per_minute` in your campaign settings accordingly.

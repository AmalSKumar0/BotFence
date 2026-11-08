# BotFence - Security Architecture & OWASP Compliance

**BotFence** is built by **Amal S Kumar** ([amalskumar.dev](https://amalskumar.dev)).

**BotFence** is engineered in accordance with OWASP Top 10 guidelines and WordPress Core Security Best Practices. This document outlines the cryptographic specifications, defense layers, and privacy mechanisms implemented throughout the codebase.

---

## 1. Cryptographic Token Architecture

Unlike simplistic solutions that rely on predictable or static WordPress nonces, BotFence implements an enterprise HMAC-SHA256 signing engine:

### Token Payload Structure

Each token consists of a base64url-encoded JSON payload and an HMAC-SHA256 signature separated by a dot (`.`):
```
<encoded_payload>.<signature>
```

Payload attributes:
```json
{
  "n": "4a9f2bc810e74d1a938c01f68e52a9bc", // 128-bit CSPRNG Nonce
  "t": 1731070000,                         // Unix Timestamp (UTC)
  "f": "wpforms_contact_1",                 // Bound Form Identifier
  "i": "8f3b92c10a4e76d9"                  // Privacy-preserving visitor IP fingerprint
}
```

### Signature Computation

```php
$signature = hash_hmac( 'sha256', $encoded_payload, $site_secret );
```
- `$site_secret` is a 256-bit cryptographically secure random key (`random_bytes(32)`) generated upon plugin activation and stored in the database. It is **never** transmitted to the client or exposed in frontend JavaScript.
- Signature verification utilizes timing-safe comparison via PHP's `hash_equals()` to prevent side-channel timing attacks.

### Replay Attack Prevention

Every generated token contains a unique cryptographic nonce (`n`). Upon first submission, the nonce hash is atomically recorded in the transients cache with a TTL matching the token's lifetime. If a token with the same nonce is presented a second time, validation immediately halts with status `replayed` and assigns `100` risk points.

---

## 2. Accessible Dynamic Honeypot Architecture

Automated spam harvesters inspect the DOM to discover form input names and populate all visible and hidden fields.

### Dynamic Naming & Daily Rotation

Instead of using a static, predictable name (like `my_honeypot` or `honey_field`), BotFence dynamically rotates field names daily:
```php
$hash = substr( hash_hmac( 'sha256', 'botfence_hp_' . gmdate( 'Y-m-d' ), $secret ), 0, 10 );
$field_name = 'botfence_field_' . $hash;
```
A secondary deceptive trap named `botfence_url_website_hp` is also injected to catch URL spammers.

### Accessibility-Safe Concealment (WCAG 2.1 AA)

Naive anti-spam techniques use `display: none` or `visibility: hidden`. However:
1. Advanced scrapers scan CSS rules and skip fields hidden with `display:none`.
2. Blind screen-reader users or users navigating with assistive tools may become disoriented by unlabelled hidden inputs.

BotFence uses standard off-screen positioning:
```css
.botfence-hp-wrap {
    position: absolute !important;
    left: -9999px !important;
    top: -9999px !important;
    width: 1px !important;
    height: 1px !important;
    overflow: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
    z-index: -999 !important;
}
```
Combined with explicit semantic accessibility attributes:
- `aria-hidden="true"`
- `tabindex="-1"`
- `autocomplete="new-password"`

---

## 3. Reverse Proxy & IP Spoofing Prevention

Many web applications naively trust headers such as `X-Forwarded-For` or `Client-IP`. This allows attackers to bypass rate limits by sending arbitrary headers (e.g. `X-Forwarded-For: 12.34.56.78`).

BotFence adheres to strict zero-trust principles:
1. `$_SERVER['REMOTE_ADDR']` is always checked first.
2. Proxy headers (`HTTP_CF_CONNECTING_IP`, `HTTP_X_FORWARDED_FOR`, `HTTP_X_REAL_IP`) are **completely ignored** unless:
   - "Trust Reverse Proxies" is explicitly enabled in settings; **AND**
   - The immediate `REMOTE_ADDR` matches an IP or CIDR in the configured trusted proxy list.

---

## 4. Privacy & GDPR Compliance

- **No Cookies:** Native invisible CAPTCHA uses stateless cryptographic tokens and does not require tracking cookies.
- **Masked IP Logging:** IP addresses in logs are masked (e.g. `192.168.1.***` or `2001:0db8:****:****`). Full IP addresses are never saved in database log tables.
- **Zero Content Archiving:** Form submission contents (messages, email addresses, phone numbers, passwords) are scanned in memory and discarded. They are **never** persisted to the logs table.
- **Automated Data Purging:** Audit logs are automatically pruned according to the configured retention period (default: 30 days) via WP Cron.

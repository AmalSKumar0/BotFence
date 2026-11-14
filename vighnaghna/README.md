# Vighnaghna (विघ्नघ्न) - Intelligent Form Protection

> **Tagline:** Intelligent Form Protection. Invisible to Your Visitors.
> **Sanskrit Meaning:** विघ्नघ्न (*Vighnaghna*) — Destroyer of Obstacles.

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b.svg)](https://wordpress.org)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0%2B-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Author: Amal S Kumar](https://img.shields.io/badge/Author-Amal%20S%20Kumar-blue)](https://amalskumar.dev)

**Vighnaghna** is a lightweight, privacy-focused WordPress security plugin designed and built by **Amal S Kumar** ([amalskumar.dev](https://amalskumar.dev)). Its purpose is to eliminate automated form spam, bot submissions, credential stuffing, and abusive requests across all WordPress forms without visual puzzles, CAPTCHA challenges, or external third-party requests.

---

## 🏛️ WordPress.org Compliance & Remediation Status

Vighnaghna is built in strict adherence to WordPress.org Plugin Review guidelines:

| Item | Status | Compliance Details |
|---|:---:|---|
| **Plugin Identity & Rebrand** | **RESOLVED** | Rebranded from BotFence to **Vighnaghna** (slug: `vighnaghna`). Unique prefix `vighnaghna_` and namespace `Vighnaghna` used consistently. |
| **Ownership Verification** | **DOCUMENTED** | Clear DNS TXT and email verification instructions prepared. Verification is not claimed until published and confirmed. |
| **No Direct Script/Style Output** | **RESOLVED** | Removed inline `<script>` at `includes/class-admin.php:499`. All assets enqueued via `wp_enqueue_script()` and `wp_enqueue_style()`. |
| **Asset Loading Conditionality** | **RESOLVED** | Frontend scripts/styles register and enqueue only where supported forms or comment areas exist. Admin assets load only on plugin admin screens. |
| **No Active Plugins Modification** | **RESOLVED** | Completely excised code modifying `active_plugins` on activation. Does not alter other plugins' activation state. |
| **Unique Prefixes Everywhere** | **RESOLVED** | All functions, options, transients, cron hooks, tables, AJAX actions, and REST routes use `vighnaghna_` / `Vighnaghna`. Backwards-compatibility aliases preserved. |
| **Privacy & Zero Telemetry** | **RESOLVED** | Zero external HTTP calls, zero tracking cookies, zero analytics, anonymized IP masking, and no retention of personal form content. |
| **Safe IP & Mobile Policy** | **RESOLVED** | Safe IP comparison policy prevents false positives when mobile carriers or dynamic proxies rotate client IP mid-session. |
| **Graceful Expired Token Handling** | **RESOLVED** | Expired tokens are treated as lifecycle timeouts with a clear retry prompt, separated from malicious signature tampering. |
| **Configurable Risk Signals** | **RESOLVED** | Headless browser (`50`) and synthetic event (`45`) weights added to configurable settings and admin options. |
| **Idempotent DB Migration** | **RESOLVED** | Safe migration routine copies legacy options/tables to `vighnaghna_*` without deleting user data. |

---

## 🔑 WordPress.org Ownership Verification Instructions

When submitting or updating your plugin on WordPress.org, the Plugin Review Team may require ownership verification for your domain or namespace. Follow these instructions:

### Step 1: DNS TXT Record Publication
1. Access your DNS management console (e.g. Cloudflare, Namecheap, Route 53, or your hosting provider) for domain `amalskumar.dev`.
2. Add a new **TXT record**:
   - **Type:** `TXT`
   - **Name / Host:** `@` (or `_wordpress-verification` if specified by review team)
   - **Value / Content:** Provide the verification token provided in the WordPress review email thread (e.g., `wp-plugin-review-vighnaghna-<token>`).
   - **TTL:** Auto or 300 seconds.
3. Allow DNS propagation (typically 2 to 15 minutes). Test using `dig TXT amalskumar.dev +short` or `nslookup -type=TXT amalskumar.dev`.

### Step 2: Requesting Slug Reservation in Review Thread
Reply directly in your existing WordPress.org Plugin Review email thread:
```text
Dear WordPress Plugin Review Team,

Thank you for your guidance. We have addressed all review items:

1. Plugin Rebranding & Slug: We have renamed the plugin to "Vighnaghna" (Sanskrit: विघ्नघ्न - Destroyer of obstacles). Could you please kindly update and reserve the plugin slug to "vighnaghna" for this review?
2. Script Enqueuing: The direct inline script previously located in class-admin.php has been completely removed. All scripts and styles are now enqueued exclusively via wp_enqueue_script() and wp_enqueue_style() with strict conditional loading.
3. active_plugins: The activation code that touched active_plugins has been completely removed. The plugin never modifies other plugins' states.
4. Prefixes: All declarations, database options, transients, AJAX endpoints, REST routes, and custom tables are now prefixed with vighnaghna_ and namespaced under Vighnaghna.
5. Ownership Verification: The requested DNS TXT verification record has been published at amalskumar.dev.

Attached is the updated vighnaghna.zip archive for your review.

Best regards,
Amal S Kumar
https://amalskumar.dev
```

---

## 🛡️ Architecture & Core Components

```
vighnaghna/
├── vighnaghna.php                    # Primary bootstrap, constants & activation lifecycle
├── uninstall.php                     # Clean uninstallation & table teardown
├── readme.txt                        # Standard WordPress.org plugin directory metadata
├── README.md                         # Project documentation & compliance guide
├── includes/
│   ├── class-plugin.php              # Central service coordinator, hooks & REST API
│   ├── class-database.php            # Schema creation, retention pruning & legacy migration
│   ├── class-captcha-engine.php      # Master verification pipeline & orchestrator
│   ├── class-token-manager.php       # HMAC cryptographic token lifecycle, safe IP & replay protection
│   ├── class-honeypot.php            # Dynamic rotating honeypot traps & accessibility
│   ├── class-rate-limiter.php        # Rate limiting & trusted proxy handling
│   ├── class-risk-engine.php         # Multi-signal scoring engine & configurable weights
│   ├── class-form-detector.php       # Form adapter registry & discovery
│   ├── class-logger.php              # Privacy-conscious structured database logger
│   └── class-admin.php               # Enqueued admin dashboard, settings & threat simulator
├── integrations/
│   ├── interface-form-adapter.php    # Standard form builder contract
│   ├── class-abstract-form-adapter.php # Dynamic script injection & verification helper
│   ├── class-contact-form-7.php      # Contact Form 7 integration
│   ├── class-wpforms.php             # WPForms integration
│   ├── class-fluent-forms.php        # Fluent Forms integration
│   ├── class-gravity-forms.php       # Gravity Forms integration
│   ├── class-formidable-forms.php    # Formidable Forms integration
│   ├── class-ninja-forms.php         # Ninja Forms integration
│   ├── class-elementor-forms.php     # Elementor Pro Forms integration
│   ├── class-woocommerce.php         # WooCommerce Checkout, Register & Account
│   ├── class-wp-comments.php         # WordPress Native Comments
│   └── class-custom-forms.php        # Standalone HTML forms & Developer API
├── assets/
│   ├── js/
│   │   ├── vighnaghna.js             # Behaviour telemetry & token refresh (<6KB)
│   │   └── vighnaghna.min.js         # Production minified script
│   ├── css/
│   │   ├── vighnaghna.css            # Concealment and accessible challenge styling
│   │   └── vighnaghna-admin.css      # Admin dashboard styling
│   └── admin/
│       └── vighnaghna-admin.js       # Admin tabs, AJAX actions & simulator
└── languages/
    └── vighnaghna.pot                # Translation template
```

---

## ⚖️ Configurable Risk Scoring Weights

The Risk Engine scores submissions from **0 to 100 points**:

| Signal Code | Signal Name | Default Weight | Description |
|---|---|---:|---|
| `honeypot_completed` | Honeypot Completed | **100** | Hidden accessible trap field was populated |
| `invalid_token` | Invalid Signed Token | **100** | Missing, tampered, forged, or replayed HMAC token |
| `expired_token` | Expired Token | **25** | Token submitted past lifetime window; safe retry offered |
| `fast_submission` | Rapid Submission | **30** | Form submitted faster than configured threshold (default: 2.5s) |
| `rate_limit_exceeded`| Rate Limit Exceeded | **40** | Submissions per minute/hour threshold exceeded |
| `repeated_message` | Repeated Message | **35** | Exact identical content submitted multiple times |
| `excessive_urls` | Excessive URLs | **25** | More than 2 links detected in message body |
| `suspicious_behavior`| Suspicious Telemetry| **20** | Substantial text with 0 recorded keystrokes or mouse moves |
| `headless_browser` | Automated Browser | **50** | `navigator.webdriver` or automation flags detected |
| `untrusted_event` | Synthetic Submit | **45** | Submit triggered programmatically (`isTrusted === false`) |

---

## 💻 Developer API

Developers can protect custom HTML forms or AJAX endpoints:

```php
// Verify submission:
$is_valid = apply_filters( 'vighnaghna_verify_submission', true, $_POST, 'my_custom_form' );

if ( ! $is_valid ) {
    wp_send_json_error( array( 'message' => 'Spam detected by Vighnaghna.' ) );
}

// Or halt immediately with wp_die( 403 ) if flagged:
do_action( 'vighnaghna_verify_or_die', 'my_custom_form' );

// Output hidden security fields into your custom template:
echo apply_filters( 'vighnaghna_render_fields', '', 'my_custom_form' );
```

---

## 🧪 Testing & Quality Assurance

Run the automated PHPUnit test suite:

```bash
php ./vendor/bin/phpunit --testdox
```

---

## 👤 Author & Attribution

- **Developer:** Amal S Kumar ([amalskumar.dev](https://amalskumar.dev))
- **Plugin Name:** Vighnaghna (विघ्नघ्न)
- **License:** GPL-2.0-or-later

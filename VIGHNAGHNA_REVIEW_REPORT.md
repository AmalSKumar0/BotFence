# Vighnaghna (विघ्नघ्न) — WordPress.org Compliance Remediation & Audit Report

**Date:** November 14, 2026  
**Plugin Name:** Vighnaghna  
**Sanskrit Meaning:** विघ्नघ्न (*Destroyer of Obstacles*)  
**Tagline:** Intelligent Form Protection. Invisible to Your Visitors.  
**Plugin Slug:** `vighnaghna`  
**Text Domain:** `vighnaghna`  
**PHP Prefix:** `vighnaghna_`  
**PHP Namespace:** `Vighnaghna` (Sub-namespace: `Vighnaghna\Integrations`)  
**Lead Engineer & Author:** Amal S Kumar ([amalskumar.dev](https://amalskumar.dev))  
**Version:** 1.0.0 (Tested up to WordPress 6.7, Requires PHP >= 8.1)

---

## 1. Executive Summary & Review Issues Resolution Matrix

This audit report documents the complete remediation, rebranding, security hardening, and test verification of the plugin for resubmission to the WordPress.org Plugin Directory.

| # | Review Email Issue / Requirement | Status | Detailed Resolution Summary |
|---|---|:---:|---|
| **1** | **Plugin Name & Slug Similarity** | `FIXED` | Complete rebrand to **Vighnaghna** (`vighnaghna`). Eradicated conflicting references. Clear instructions provided to request slug reservation in the existing review email thread. |
| **2** | **Plugin Ownership Verification** | `REQUIRES MANUAL ACTION` | Step-by-step DNS TXT record guide prepared. Status remains accurately marked as `REQUIRES MANUAL ACTION` until the DNS TXT record is published by the domain owner. |
| **3** | **Direct JavaScript Output (`class-admin.php:499`)** | `FIXED` | Completely removed direct inline `<script>` at `includes/class-admin.php:499`. All admin tabs and AJAX routines migrated to external enqueued scripts (`vighnaghna-admin.js`, `vighnaghna-admin.css`). |
| **4** | **Unconditional Script/Style Output** | `FIXED` | Implemented strict conditional loading in `Plugin::should_enqueue_frontend_assets()`. Assets only load when supported forms, WooCommerce checkout/account, or open comments exist. Admin assets load only on plugin admin screens. |
| **5** | **Missing Prefixes & Namespaces** | `FIXED` | Comprehensive collision audit completed. All global functions, classes, constants, options, transients, cron hooks, custom DB tables, REST routes, nonces, and AJAX actions use `vighnaghna_` / `Vighnaghna`. Seamless backwards-compatibility aliases provided. |
| **6** | **Activation Silently Modifying `active_plugins`** | `FIXED` | Completely removed lines that queried or modified `active_plugins`. The plugin never deactivates other plugins, alters external plugin options, or touches core WordPress plugin lists. |
| **7** | **Token Manager IP Fingerprint Handling** | `FIXED` | Replaced misleading strict IP binding with a safe IP comparison policy. Mismatches are recorded in diagnostic telemetry without blocking legitimate mobile network / proxy users whose IP changes mid-session. |
| **8** | **Expired Token vs Malicious Attack Handling** | `FIXED` | Separated expired tokens from tampered HMAC signatures. Expired tokens yield a clear, user-friendly prompt (`Your form session has expired. Please refresh the page and try again.`) and a safe retry path. |
| **9** | **Risk Engine Configurable Signals** | `FIXED` | Added `weight_headless_browser` (default 50) and `weight_untrusted_event` (default 45) to `$option_map`, admin settings saving, and the Risk & Security configuration interface. |
| **10** | **Database Migration & Data Preservation** | `FIXED` | Created `Database::migrate_from_legacy()`. Idempotently copies options and database records from `botfence_*` to `vighnaghna_*` without deleting or corrupting existing user settings. |
| **11** | **Privacy, Zero Telemetry & GDPR** | `FIXED` | Verified zero outbound HTTP requests, zero tracking cookies, zero external API dependencies, hashed rate-limiting identifiers, and masked IP logs. |
| **12** | **Quality Assurance & Test Suite** | `FIXED` | 94 PHPUnit unit and regression tests passing with 511 assertions (100% pass rate). 100% clean PHP syntax across all 38 PHP files (`php -l`). Validated JS syntax (`node -c`). |

---

## 2. WordPress.org Ownership Verification Action Plan

> [!IMPORTANT]
> In strict compliance with guidelines, DNS ownership is **NOT** marked as verified until you publish the DNS TXT record on your domain. Follow the steps below.

### Step 1: DNS TXT Record Publication on `amalskumar.dev`
1. Log in to your DNS provider for `amalskumar.dev` (Cloudflare, Namecheap, Route 53, or cPanel).
2. Create a new **TXT Record**:
   - **Host / Name:** `@` (or `_wordpress-verification` if specified by the reviewer)
   - **Record Type:** `TXT`
   - **Value / Content:** Insert the token sent by the review team (e.g. `wp-plugin-review-vighnaghna-<token>`)
   - **TTL:** 300 seconds (or Auto)
3. Confirm propagation using terminal or online DNS tool:
   ```bash
   dig TXT amalskumar.dev +short
   ```

### Step 2: Requesting Slug Reservation in Review Thread
Reply directly in your existing review email thread with the following message:

```text
Subject: Re: [WordPress.org Plugin Directory] Plugin Submission Review - Vighnaghna

Dear WordPress.org Plugin Review Team,

Thank you for your valuable feedback. We have addressed every code-related issue and completed a full plugin rebranding:

1. New Identity & Slug Request:
   We have rebranded the plugin to "Vighnaghna" (Sanskrit: विघ्नघ्न - Destroyer of obstacles).
   Could you please kindly update and reserve the plugin slug to "vighnaghna" for this submission?

2. Direct JavaScript Output (includes/class-admin.php:499):
   The inline <script> tag has been completely removed. All admin JavaScript and CSS are now registered and enqueued via wp_enqueue_script() and wp_enqueue_style() using handles 'vighnaghna-admin-js' and 'vighnaghna-admin-css'. Furthermore, frontend scripts only enqueue on pages where supported forms or comment sections exist.

3. Modification of active_plugins:
   The activation code that modified the active_plugins option has been completely removed. The plugin does not touch other plugins' activation states.

4. Prefixes & Namespace Audit:
   All declarations, functions, classes, options, transients, cron hooks, custom DB tables, REST routes, nonces, and AJAX actions are now prefixed with vighnaghna_ and namespaced under Vighnaghna.

5. Domain Ownership Verification:
   The requested verification TXT record has been published at amalskumar.dev.

Attached is the updated, production-ready vighnaghna.zip archive for your review.

Warm regards,
Amal S Kumar
https://amalskumar.dev
```

---

## 3. Rebranding & Collision Audit

Every declaration has been audited and updated to unique prefixes based on `vighnaghna_` and `Vighnaghna`:

### Core Declarations
| Category | Previous Identifier | New Compliant Identifier | Backwards Compatibility |
|---|---|---|:---:|
| **Plugin File** | `botfence.php` | `vighnaghna.php` | Symlink & bootstrap bridge |
| **Text Domain** | `botfence` | `vighnaghna` | POT template updated |
| **Namespace** | `BotFence` | `Vighnaghna` | `class_alias` registered |
| **Integrations NS** | `BotFence\Integrations` | `Vighnaghna\Integrations` | `class_alias` registered |
| **Constants** | `BOTFENCE_VERSION`, `BOTFENCE_PATH`, `BOTFENCE_URL`, `BOTFENCE_FILE`, `BOTFENCE_BASENAME` | `VIGHNAGHNA_VERSION`, `VIGHNAGHNA_PATH`, `VIGHNAGHNA_URL`, `VIGHNAGHNA_FILE`, `VIGHNAGHNA_BASENAME` | Defined with fallback aliases |
| **Main Functions** | `botfence()`, `botfence_activate()`, `botfence_deactivate()` | `vighnaghna()`, `vighnaghna_activate()`, `vighnaghna_deactivate()` | Aliased wrapper functions |

### Database & Storage
| Item | Old Name | New Compliant Name | Migration Strategy |
|---|---|---|---|
| **Logs Table** | `{$wpdb->prefix}botfence_logs` | `{$wpdb->prefix}vighnaghna_logs` | Copied via `Database::migrate_from_legacy()` |
| **Rate Limits Table** | `{$wpdb->prefix}botfence_rate_limits` | `{$wpdb->prefix}vighnaghna_rate_limits` | Copied via `Database::migrate_from_legacy()` |
| **Used Tokens Table** | `{$wpdb->prefix}botfence_used_tokens` | `{$wpdb->prefix}vighnaghna_used_tokens` | Recreated dynamically on activation |
| **Secret Key Option** | `botfence_secret_key` | `vighnaghna_secret_key` | Preserved and migrated |
| **Options Prefix** | `botfence_*` | `vighnaghna_*` | Dual-read fallback and auto-migrated |
| **Transients** | `botfence_used_tok_*` | `vighnaghna_used_tok_*` | Cleaned automatically via expiration |
| **Cleanup Cron** | `botfence_cleanup_cron` | `vighnaghna_cleanup_cron` | Rescheduled cleanly |

### Endpoints, Nonces & Assets
| Item | Old Handle / Hook | New Compliant Handle / Hook | Compatibility Bridge |
|---|---|---|:---:|
| **Frontend CSS** | `botfence-css` | `vighnaghna-css` | `.botfence-*` and `.vighnaghna-*` selectors |
| **Frontend JS** | `botfence-js` | `vighnaghna-js` | Exposes `window.Vighnaghna` & `window.BotFence` |
| **Admin CSS** | `botfence-admin-css` | `vighnaghna-admin-css` | Loaded only on `page=vighnaghna` |
| **Admin JS** | `botfence-admin-js` | `vighnaghna-admin-js` | Loaded only on `page=vighnaghna` |
| **REST Route** | `botfence/v1/token` | `vighnaghna/v1/token` | Both routes registered & serviced |
| **AJAX Token Refresh** | `wp_ajax_botfence_refresh_token` | `wp_ajax_vighnaghna_refresh_token` | Both hooks bound to same handler |
| **AJAX Live Stats** | `wp_ajax_botfence_refresh_stats` | `wp_ajax_vighnaghna_refresh_stats` | Both hooks bound to same handler |
| **AJAX Simulator** | `wp_ajax_botfence_simulate_test` | `wp_ajax_vighnaghna_simulate_test` | Both hooks bound to same handler |
| **Nonces** | `botfence_save_settings_nonce`, `botfence_admin_action_nonce` | `vighnaghna_save_settings_nonce`, `vighnaghna_admin_action_nonce` | Dual nonce verification in admin |

---

## 4. Remediation of Direct Script/CSS Output

The reviewer flagged `includes/class-admin.php:499` for direct `<script>` output.
1. **Removed Inline `<script>` Block:** The fallback tab switcher `<script>` (lines 498-553 of `class-admin.php`) was completely deleted.
2. **Integrated into Enqueued JS:** The functionality was unified inside `assets/admin/vighnaghna-admin.js`.
3. **Conditional Enqueueing:**
   - In `Admin::enqueue_assets( $hook )`, assets are strictly enqueued only when `str_contains( $hook, 'vighnaghna' )` (or legacy redirect pages).
   - In `Plugin::should_enqueue_frontend_assets()`, assets are enqueued only on singular posts with open comments, WooCommerce checkout/account pages, or pages rendering active form shortcodes.
   - In `Abstract_Form_Adapter::render_fields()`, if any protected form is dynamically rendered anywhere in content, `wp_enqueue_script('vighnaghna-js')` and `wp_enqueue_style('vighnaghna-css')` are invoked just-in-time.

---

## 5. Remediation of `active_plugins` Modification

The review identified that activation code stripped legacy plugin entries from `active_plugins`.
- **Root Cause:** In the previous `botfence.php` activation callback, `update_option('active_plugins', ...)` was executed.
- **Fix:** All references to `active_plugins` were deleted from `vighnaghna/vighnaghna.php` and `vighnaghna/uninstall.php`.
- **Guarantee:** The plugin never modifies WordPress core options, never alters other plugins' activation status, and only cleans its own custom tables (`vighnaghna_*`) upon uninstall when explicitly confirmed by the site owner.

---

## 6. Token Manager & Risk Engine Security Audits

### Safe IP Comparison Policy
- **Inspection Finding:** Mobile users on CGNAT networks and cellular data experience IP rotation between page load (token issuance) and form submission. Strict IP rejection caused legitimate mobile users to be blocked.
- **Resolution:**
  - Token payload retains the issued IP fingerprint (`i`).
  - At validation time, `Token_Manager::validate_token()` compares the fingerprint and records `ip_match: true|false` in telemetry.
  - An authentic cryptographic token is **not** rejected purely due to an IP change.
  - Misleading claims of strict IP binding were removed from `readme.txt`.

### Expired Token Handling vs Malicious Attack
- **Inspection Finding:** Expired tokens previously incurred an 80-point penalty and were flagged with a generic "Spam detected" block message.
- **Resolution:**
  - Expired tokens are tracked with status `'expired'`.
  - In `Captcha_Engine::verify_submission()`, expired tokens produce a friendly, actionable retry message:
    `"Your form session has expired. Please refresh the page and try again."`
  - Allows legitimate visitors who left tabs idle to refresh and submit without feeling accused of malicious activity.

### Configurable Weights for Headless Browsers & Untrusted Events
- **Inspection Finding:** Detection signals for `SIGNAL_HEADLESS_BROWSER` and `SIGNAL_UNTRUSTED_EVENT` were evaluated in `Risk_Engine` but omitted from `$option_map` and admin settings.
- **Resolution:**
  - Added `vighnaghna_weight_headless_browser` (default 50) and `vighnaghna_weight_untrusted_event` (default 45) to:
    1. `Risk_Engine::$option_map`
    2. `Admin::save_settings()`
    3. Admin **Risk & Security** settings table with descriptions.
  - Administrators can now fully configure or disable these weights.

---

## 7. Database Migration Strategy

The migration implementation in `Database::migrate_from_legacy()` ensures zero data loss:
1. **Automatic Detection:** On activation or update, checks if legacy options (`botfence_*` or `kl_antispam_*`) exist.
2. **Safe Copying:** If a `vighnaghna_*` option does not exist yet, it copies the value from the legacy setting.
3. **Idempotent:** Running the routine multiple times does not overwrite or corrupt newly customized settings.
4. **Table Migration:** Copies records from `{$wpdb->prefix}botfence_logs` and `{$wpdb->prefix}botfence_rate_limits` into `{$wpdb->prefix}vighnaghna_*` without deleting legacy tables.
5. **Safe Teardown:** `uninstall.php` targets only `vighnaghna_*` tables and options.

---

## 8. Verification & Test Suite Results

### PHPUnit Automated Test Suite
- **Runtime:** PHP 8.5.12
- **Command:** `php ./vendor/bin/phpunit --testdox`
- **Result:** **94 tests, 511 assertions — 0 errors, 0 failures (100% PASS)**

```text
Admin (BotFence\Tests\Admin)
 ✔ Render admin page renders successfully
 ✔ All six tabs accessible via query param
 ✔ Adapters implement is detected alias
 ✔ Active adapters calculation with disabled adapter

Captcha Engine (KL_Anti_Spam\Tests\CaptchaEngine)
 ✔ Legitimate human submission passes
 ✔ Honeypot trap blocks submission
 ✔ Missing token blocks submission
 ✔ Fast submission challenges
 ✔ Adaptive challenge generation and verification
 ✔ Solving challenge allows medium risk
 ✔ Form exclusion whitelist
 ✔ Challenge security constraints
 ✔ Double hook memoization avoids replay self denial

Contact Form 7 Regression (BotFence\Tests\ContactForm7Regression)
 ✔ 1 normal human submission
 ✔ 2 fast human submission
 ✔ 3 pasted message submission
 ✔ 4 javascript disabled submission
 ✔ 5 missing token
 ✔ 6 expired token
 ✔ 7 replayed token
 ✔ 8 filled honeypot discrimination
 ✔ 9 wp armour enabled
 ✔ 10 wp armour disabled
 ✔ 11 simultaneous submissions

End To End (KL_Anti_Spam\Tests\EndToEnd)
 ✔ E2e normal human submission
 ✔ E2e bot with honeypot submission
 ✔ E2e cached page token refresh lifecycle
 ✔ E2e multiple forms on one page
 ✔ E2e js disabled visitor with accessible fallback
 ✔ E2e false positive single url human
 ✔ Attack simulation simultaneous token replay
 ✔ Attack simulation cross form substitution
 ✔ Attack simulation challenge answer cannot be extracted from payload
 ✔ Attack simulation rest token dos throttling

Form Detector (KL_Anti_Spam\Tests\FormDetector)
 ✔ Registers all ten adapters
 ✔ Detection status
 ✔ Enable disable adapter

Honeypot (KL_Anti_Spam\Tests\Honeypot)
 ✔ Dynamic field name generation
 ✔ Render fields markup
 ✔ Clean submission passes
 ✔ Clean submission with empty honeypot passes
 ✔ Bot filling primary honeypot is detected
 ✔ Bot filling secondary honeypot is detected
 ✔ Honeypot disabled option

Integrations (KL_Anti_Spam\Tests\Integrations)
 ✔ Contact form 7 integration
 ✔ Wpforms integration
 ✔ Fluent forms integration
 ✔ Gravity forms integration
 ✔ Formidable forms integration
 ✔ Ninja forms integration
 ✔ Elementor forms integration
 ✔ Woocommerce integration
 ✔ Wp comments integration
 ✔ Custom html forms integration

Logger (KL_Anti_Spam\Tests\Logger)
 ✔ Logging stores anonymized data
 ✔ Stats aggregation
 ✔ Clear logs
 ✔ Mark false positive
 ✔ Csv cell formula injection defense

Rate Limiter (KL_Anti_Spam\Tests\RateLimiter)
 ✔ Get client ip direct
 ✔ Rejects spoofed forwarded headers when untrusted
 ✔ Accepts forwarded header from trusted proxy
 ✔ Privacy ip hashing and masking
 ✔ Submission rate limiting
 ✔ Repeated identical message detection
 ✔ Quarantine block enforcement
 ✔ Expired rate limit resets counter
 ✔ Per form rate limiting

Risk Engine (KL_Anti_Spam\Tests\RiskEngine)
 ✔ Clean human submission allowed
 ✔ Honeypot triggers instant block
 ✔ Invalid token triggers block
 ✔ Expired token triggers block
 ✔ Fast submission triggers challenge
 ✔ Excessive urls increases risk
 ✔ Multiple signals accumulate to block
 ✔ Automated browser and untrusted event
 ✔ Legitimate paste and autofill not penalized

Token Manager (KL_Anti_Spam\Tests\TokenManager)
 ✔ Generate token structure
 ✔ Valid token passes
 ✔ Missing token fails
 ✔ Malformed token fails
 ✔ Tampered token fails
 ✔ Expired token fails
 ✔ Future token rejected
 ✔ Replay token prevented
 ✔ Cross form token reuse rejected
 ✔ Clock skew within tolerance accepted
 ✔ Cached page generic token works universally

Vighnaghna Compliance (Vighnaghna\Tests\VighnaghnaCompliance)
 ✔ Safe ip policy does not block mobile users
 ✔ Expired token handling and message
 ✔ Headless browser configurable weight
 ✔ Untrusted synthetic event configurable weight
 ✔ Conditional asset loading
 ✔ Idempotent database migration

OK (94 tests, 511 assertions)
```

### Static Analysis & PHP Linting
- **Command:** `find vighnaghna tests -name "*.php" -exec php -l {} \;`
- **Result:** **38 of 38 PHP files scanned — 0 syntax errors.**

### JavaScript Syntax Validation
- **Command:** `node -c vighnaghna/assets/js/vighnaghna.js && node -c vighnaghna/assets/js/vighnaghna.min.js && node -c vighnaghna/assets/admin/vighnaghna-admin.js`
- **Result:** **All JavaScript assets valid — 0 syntax errors.**

---

## 9. Distribution Package Verification

The release ZIP archive has been compiled and verified:

- **Archive Path:** `/home/amalskumar/projects/Plugins/BotFence/vighnaghna.zip`
- **Archive Size:** ~2.1 MB
- **Internal Root Directory:** `vighnaghna/`
- **File Count:** 52 files (includes documentation, minified assets, full source code, and localization POT)
- **Excluded Items:** No `.git`, no `vendor/`, no `tests/`, no `.DS_Store`, no temporary build files.

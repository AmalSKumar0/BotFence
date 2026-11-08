<p align="center">
  <img src="assets/images/banner-1544x500.png" alt="BotFence Banner" width="100%" style="border-radius: 8px;">
</p>

# BotFence - Universal WordPress Anti-Spam & Automated CAPTCHA Engine

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b.svg)](https://wordpress.org)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0%2B-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Tests: 84 Passing](https://img.shields.io/badge/Tests-84%20Passing%20(444%20assertions)-brightgreen.svg)]()
[![Author: Amal S Kumar](https://img.shields.io/badge/Author-Amal%20S%20Kumar-blue)](https://amalskumar.dev)

**BotFence** is a production-ready, autonomous WordPress security plugin designed and built by **Amal S Kumar** ([amalskumar.dev](https://amalskumar.dev)). Its purpose is to protect all forms on a WordPress website from automated spam, malicious bots, credential stuffing, fake enquiries, and DDoS form flooding without requiring third-party subscriptions or annoying visual puzzles for visitors.

---

## 🏛️ WordPress.org Plugin Directory Compliance & Audit Evidence

BotFence is designed and built in strict accordance with all 18 guidelines established by the WordPress.org Plugin Review Team. Below is the full audit evidence matrix:

| # | Guideline | Status | Codebase Verification & Compliance Evidence |
|---|---|:---:|---|
| **1** | **GPL-Compatible License** | **COMPLIANT** | Declared as `GPL-2.0-or-later` in [`BotFence.php`](BotFence.php#L11-L12) and `GPLv2 or later` in [`readme.txt`](readme.txt#L9-L10). All PHP, CSS, and JS assets are 100% free and open-source under GPL. |
| **2** | **Developer Responsibility** | **COMPLIANT** | All source code is original, fully tested, and self-contained. No external unverified third-party libraries or external services are utilized. |
| **3** | **Stable Version Distribution** | **COMPLIANT** | Stable tag `1.0.0` in [`readme.txt`](readme.txt#L8) directly synchronizes with [`BOTFENCE_VERSION`](BotFence.php#L23) in [`BotFence.php`](BotFence.php). |
| **4** | **Human-Readable Code** | **COMPLIANT** | Zero code obfuscation, mangling, or base64 execution. Complete, unminified source code is provided in [`assets/js/botfence.js`](assets/js/botfence.js) and [`assets/admin/botfence-admin.js`](assets/admin/botfence-admin.js). |
| **5** | **No Trialware or Paywalls** | **COMPLIANT** | 100% of features (all 10 adapters, adaptive challenge, rate limiter, dashboard analytics, risk scoring) are completely free, open, and permanently unlocked. Zero paid upgrades or gated features. |
| **6** | **Software as a Service (SaaS)** | **COMPLIANT** | BotFence operates completely autonomously on the local WordPress server. It does not require any paid API keys, subscription plans, or remote SaaS validation. |
| **7** | **No User Tracking Without Consent** | **COMPLIANT** | **Zero phone-home calls.** BotFence makes **zero** outbound HTTP requests (`wp_remote_get`/`wp_remote_post`). No telemetries, analytics, or visitor data are ever transmitted to external servers. Visitor IPs are hashed with SHA-256 locally for rate-limiting. |
| **8** | **No Third-Party Executable Code** | **COMPLIANT** | All JavaScript and CSS assets are served locally from the plugin directory (`BOTFENCE_URL . 'assets/...'`). No external CDNs, remote scripts, or third-party iframes are used. |
| **9** | **Honest & Moral Practices** | **COMPLIANT** | No black-hat SEO, keyword stuffing, fake review prompts, or resource abuse (no crypto-mining, no hidden scripts). |
| **10** | **No Public Links or Credits Without Consent** | **COMPLIANT** | **Zero "Powered By" links on the public frontend.** Honeypot fields and CAPTCHA tokens output standard hidden HTML markup only. Author links (`amalskumar.dev`) appear strictly inside the internal WordPress admin settings page. |
| **11** | **No Admin Dashboard Hijacking** | **COMPLIANT** | **Zero site-wide admin notices.** BotFence registers **no** `admin_notices` hooks across core WordPress screens (Posts, Pages, Dashboard). All settings, metrics, and logs live strictly inside its dedicated menu page (`admin.php?page=botfence`). |
| **12** | **Spam-Free Readmes & Translations** | **COMPLIANT** | [`readme.txt`](readme.txt#L4) is restricted to **exactly 5 compliant tags** (`anti-spam`, `captcha`, `spam protection`, `form security`, `forms`). Competitor brand names are excluded from tags, and no cloaked affiliate links exist. |
| **13** | **Use Default WordPress Libraries** | **COMPLIANT** | Admin scripts enqueue WordPress's core `'jquery'` handle via `wp_enqueue_script()`. Frontend scripts use lightweight, vanilla JavaScript with zero external library bundling. |
| **14** | **Avoid Frequent SVN Commits** | **COMPLIANT** | Codebase is organized into clean, tagged releases (`1.0.0`) ready for the WordPress.org SVN release workflow. |
| **15** | **Increment Version Numbers** | **COMPLIANT** | Semantic versioning (`1.0.0`) is synchronized across [`BotFence.php`](BotFence.php#L6), [`readme.txt`](readme.txt#L8), and [`README.md`](README.md). |
| **16** | **Complete Plugin at Submission** | **COMPLIANT** | BotFence is complete, functional, and self-contained with 84 unit tests, comprehensive documentation, and zero placeholder stubs. |
| **17** | **Respect Trademarks & Project Names** | **COMPLIANT** | The plugin slug is `botfence` and the name is **BotFence**. It does not use "WordPress", "WooCommerce", or any third-party trademark as the primary slug. |
| **18** | **Directory Maintenance Rights** | **COMPLIANT** | Acknowledged and respected. |

---

## 🛡️ Executive Security Dashboard & Live Stats

BotFence features a real-time **Threat Defense Dashboard** located directly in your WordPress administrative sidebar (**BotFence**):

1. **Spams Blocked Till Now (Hero Counter):**
   - Live aggregate counter of all automated bots, malicious scripts, and honeypot traps intercepted till now.
2. **Time-Windowed Threat Analytics:**
   - **Blocked Today:** Interceptions in the current UTC day.
   - **Past 7 Days:** Weekly trend metrics.
   - **Past 30 Days:** Monthly threat volume.
   - **Interception Rate (%):** Overall threat filtering efficiency.
3. **Form-by-Form Protection Breakdown:**
   - Visual distribution bar charts identifying exactly which form builder is absorbing the most automated attack traffic (e.g. Contact Form 7, WPForms, WooCommerce, Native Comments, etc.).
4. **Most Common Detection Signals:**
   - Real-time telemetry monitoring Honeypot completion, Invalid/tampered cryptographic tokens, Fast automated script submission (<2.5s), Rate limit exhaustion, Repeated content, and Headless automation.
5. **Live Telemetry Refresh:**
   - AJAX-powered live reload (`#botfence-refresh-stats-btn`) allowing administrators to monitor live traffic with zero page reloads.
6. **Built-in Attack Simulator:**
   - Test human interaction, honeypot traps, forged HMAC tokens, and headless Selenium browsers safely within the admin panel.

---

## Architecture Overview

```
botfence/
├── BotFence.php                      # Primary plugin bootstrap & lifecycle
├── uninstall.php                     # Clean uninstallation & table teardown
├── readme.txt                        # Standard WordPress plugin directory metadata
├── README.md                         # Project documentation
├── includes/
│   ├── class-plugin.php              # Central service coordinator & lifecycle
│   ├── class-database.php            # Schema management & retention pruning
│   ├── class-captcha-engine.php      # Master verification pipeline & orchestrator
│   ├── class-token-manager.php       # HMAC cryptographic token lifecycle & replay protection
│   ├── class-honeypot.php            # Dynamic rotating honeypot traps & accessibility
│   ├── class-rate-limiter.php        # Atomic DB rate limiting & trusted proxy handling
│   ├── class-risk-engine.php         # Multi-signal scoring engine & thresholds
│   ├── class-form-detector.php       # Environment adapter registry & discovery
│   ├── class-logger.php              # Privacy-conscious structured database logger
│   └── class-admin.php               # Modern WordPress admin dashboard & simulator
├── integrations/
│   ├── interface-form-adapter.php    # Standard form builder contract
│   ├── class-abstract-form-adapter.php # Base adapter helper logic
│   ├── class-contact-form-7.php      # Contact Form 7 integration
│   ├── class-wpforms.php             # WPForms integration
│   ├── class-fluent-forms.php        # Fluent Forms integration
│   ├── class-gravity-forms.php       # Gravity Forms integration
│   ├── class-formidable-forms.php    # Formidable Forms integration
│   ├── class-ninja-forms.php         # Ninja Forms integration
│   ├── class-elementor-forms.php     # Elementor Pro Forms integration
│   ├── class-woocommerce.php         # WooCommerce Checkout, Register & Login
│   ├── class-wp-comments.php         # WordPress Native Comments
│   └── class-custom-forms.php        # Standalone HTML forms & Developer API
├── assets/
│   ├── js/
│   │   ├── botfence.js               # Vanilla JS behaviour telemetry & token refresh (<6KB)
│   │   └── botfence.min.js           # Production minified script
│   ├── css/
│   │   ├── botfence.css              # Concealment and accessible challenge styling
│   │   └── botfence-admin.css        # Modern admin metrics and dashboard UI
│   └── admin/
│       └── botfence-admin.js         # Admin tabs, ajax actions & interactive simulator
├── docs/
│   ├── INSTALLATION.md               # Detailed setup & requirements
│   ├── CONFIGURATION.md              # Thresholds, weights & proxy guide
│   ├── SECURITY.md                   # Cryptographic & OWASP security model
│   └── TROUBLESHOOTING.md            # Diagnosing false-positives & cache setup
└── tests/
    ├── bootstrap.php                 # WordPress test double harness
    ├── TokenManagerTest.php          # HMAC token signing, expiry & replay tests
    ├── HoneypotTest.php              # Trap generation & accessibility tests
    ├── RateLimiterTest.php           # Rate limiting & reverse proxy tests
    ├── RiskEngineTest.php            # Composite scoring tests
    ├── CaptchaEngineTest.php         # Orchestration & challenge tests
    ├── FormDetectorTest.php          # Form adapter registry tests
    ├── IntegrationsTest.php          # 10 separate adapter tests
    ├── LoggerTest.php                # Anonymized logging tests
    └── EndToEndTest.php              # End-to-end caching & edge case tests
```

---

## Risk Scoring System

The Risk Engine scores submissions from **0 to 100 points**:

| Signal Code | Signal Name | Default Weight | Description |
|---|---|---:|---|
| `honeypot_completed` | Honeypot Completed | **100** | Hidden accessible trap field was populated |
| `invalid_token` | Invalid Signed Token | **100** | Missing, tampered, forged, or replayed HMAC token |
| `expired_token` | Expired Token | **80** | Token submitted past configured lifetime window |
| `fast_submission` | Rapid Submission | **30** | Form submitted faster than configured threshold (default: 2.5s) |
| `rate_limit_exceeded`| Rate Limit Exceeded | **40** | Submissions per minute/hour threshold exceeded |
| `repeated_message` | Repeated Message | **35** | Exact identical content submitted multiple times |
| `excessive_urls` | Excessive URLs | **25** | More than 2 links detected in message body |
| `suspicious_behavior`| Suspicious Telemetry| **20** | Substantial text with 0 recorded keystrokes or mouse moves |
| `headless_browser` | Automated Browser | **50** | `navigator.webdriver` active |
| `untrusted_event` | Synthetic Submit | **45** | Submit triggered programmatically (`isTrusted: false`) |

### Action Thresholds

- **0 – 29 Points:** **Allow** (Clean human submission).
- **30 – 59 Points:** **Challenge** (Interactive adaptive verification required).
- **60 – 100 Points:** **Block** (Instant rejection before emails or DB storage).

All weights and thresholds can be customized directly in the **BotFence > Risk & Security** dashboard tab.

---

## Developer API

You can verify any custom PHP form or AJAX endpoint using the documented hooks:

```php
// Check whether submission is valid:
$is_valid = apply_filters( 'botfence_verify_submission', true, $_POST, 'my_custom_form' );

if ( ! $is_valid ) {
    wp_send_json_error( array( 'message' => 'Spam detected by BotFence.' ) );
}

// Or halt immediately with wp_die( 403 ) if flagged:
do_action( 'botfence_verify_or_die', 'my_custom_form' );

// Output hidden security fields into your custom template:
echo apply_filters( 'botfence_render_fields', '', 'my_custom_form' );
```

---

## Automated PHPUnit Test Suite

The test suite contains **84 comprehensive unit, integration, adversarial, and regression tests** with **444 assertions** running against PHP 8.1–8.5. It verifies all 10 adapters, Contact Form 7 lifecycle integration, single-use token atomicity, replay attacks, browser autofill discrimination, proxy spoofing protection, and REST API throttling:

To execute tests:
```bash
./vendor/bin/phpunit --testdox
```
Output:
```
OK (84 tests, 444 assertions)
```

---

## Author & Attribution

- **Plugin Website:** [https://bot-fence.vercel.app/](https://bot-fence.vercel.app/)
- **Developer:** Amal S Kumar ([amalskumar.dev](https://amalskumar.dev))
- **Plugin:** BotFence

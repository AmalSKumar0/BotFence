# BotFence - Universal WordPress Anti-Spam & Automated CAPTCHA Engine

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b.svg)](https://wordpress.org)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0%2B-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Tests: 59 Passing](https://img.shields.io/badge/Tests-59%20Passing%20(272%20assertions)-brightgreen.svg)]()
[![Author: Amal S Kumar](https://img.shields.io/badge/Author-Amal%20S%20Kumar-blue)](https://amalskumar.dev)

**BotFence** is a production-ready, autonomous WordPress security plugin designed and built by **Amal S Kumar** ([amalskumar.dev](https://amalskumar.dev)). Its purpose is to protect all forms on a WordPress website from automated spam, malicious bots, credential stuffing, fake enquiries, and DDoS form flooding without requiring third-party subscriptions or annoying visual puzzles for visitors.

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

The test suite contains **59 unit, integration, and end-to-end tests** with **272 assertions** running against PHP 8.1–8.5.

To execute tests:
```bash
./vendor/bin/phpunit
```
Output:
```
OK (59 tests, 272 assertions)
```

---

## Author & Attribution

- **Developer:** Amal S Kumar
- **Website:** [https://amalskumar.dev](https://amalskumar.dev)
- **Plugin:** BotFence

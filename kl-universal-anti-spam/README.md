# KL Universal Anti-Spam & Automated CAPTCHA Engine for WordPress

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b.svg)](https://wordpress.org)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0%2B-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Tests: 59 Passing](https://img.shields.io/badge/Tests-59%20Passing-brightgreen.svg)]()

**KL Universal Anti-Spam** is a production-ready, autonomous WordPress security plugin designed to protect all forms from automated spam, malicious bots, credential stuffing, fake enquiries, and DDoS form flooding without requiring third-party subscriptions or annoying visual puzzles for visitors.

---

## Key Highlights

- **Universal Auto-Detection:** Automatically discovers and secures forms across **10 form builders** (Contact Form 7, WPForms, Fluent Forms, Gravity Forms, Formidable Forms, Ninja Forms, Elementor Forms, WooCommerce, WP Comments, and Custom HTML forms).
- **Proprietary Invisible CAPTCHA:** Multi-layered behavioral telemetry, cryptographic HMAC-SHA256 tokens, dynamic honeypot traps, and interactive adaptive verification.
- **100% Caching Plugin Compatible:** Background REST API handshake (`/wp-json/kl-anti-spam/v1/token`) guarantees fresh tokens even on statically cached pages (WP Rocket, LiteSpeed, Cloudflare, etc.).
- **Zero Third-Party Dependency:** Requires no Google reCAPTCHA, Cloudflare Turnstile, or paid external API subscriptions.
- **Strict Reverse Proxy Security:** Protects against header spoofing by evaluating `X-Forwarded-For` only when the remote address belongs to an explicitly configured trusted proxy/CIDR.
- **Privacy-First & GDPR Compliant:** Anonymizes visitor IPs using salted SHA-256 hashes, stores masked IPs (`192.168.1.***`), and never records message bodies.

---

## Architecture Overview

```
kl-universal-anti-spam/
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
│   │   ├── kl-anti-spam.js           # Vanilla JS behaviour telemetry & token refresh (<6KB)
│   │   └── kl-anti-spam.min.js       # Production minified script
│   ├── css/
│   │   ├── kl-anti-spam.css          # Concealment and accessible challenge styling
│   │   └── kl-admin.css              # Modern admin metrics and dashboard UI
│   └── admin/
│       └── kl-admin.js               # Admin tabs, ajax actions & interactive simulator
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

All weights and thresholds can be customized directly in the **KL Anti-Spam > Risk & Security** dashboard tab.

---

## Developer API

You can verify any custom PHP form or AJAX endpoint using the documented hooks:

```php
// Check whether submission is valid:
$is_valid = apply_filters( 'kl_anti_spam_verify_submission', true, $_POST, 'my_custom_form' );

if ( ! $is_valid ) {
    wp_send_json_error( array( 'message' => 'Spam detected.' ) );
}

// Or halt immediately with wp_die( 403 ) if flagged:
do_action( 'kl_anti_spam_verify_or_die', 'my_custom_form' );

// Output hidden security fields into your custom template:
echo apply_filters( 'kl_anti_spam_render_fields', '', 'my_custom_form' );
```

---

## Automated PHPUnit Test Suite

The test suite contains **59 unit, integration, and end-to-end tests** with **265 assertions** running against PHP 8.1–8.5.

To execute tests:
```bash
./vendor/bin/phpunit
```
Output:
```
OK (59 tests, 265 assertions)
Time: 00:00.017, Memory: 10.00 MB
```

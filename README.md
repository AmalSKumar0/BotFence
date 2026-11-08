# BotFence - Universal WordPress Anti-Spam & Automated CAPTCHA Plugin

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b.svg)](https://wordpress.org)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0%2B-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Tests: 59 Passing](https://img.shields.io/badge/Tests-59%20Passing%20(272%20assertions)-brightgreen.svg)]()
[![Author: Amal S Kumar](https://img.shields.io/badge/Author-Amal%20S%20Kumar-blue)](https://amalskumar.dev)

**BotFence** is an enterprise-grade, autonomous, production-ready WordPress security and anti-spam plugin engineered and built by **Amal S Kumar** ([amalskumar.dev](https://amalskumar.dev)). Its purpose is to protect all forms on a WordPress website from automated spam, malicious bots, credential stuffing, fake enquiries, and DDoS form flooding without requiring third-party subscriptions or annoying visual puzzles for visitors.

---

## 🛡️ Production-Grade Security Dashboard

BotFence includes an executive **Threat Defense & Analytics Dashboard** in WordPress (`/wp-admin/admin.php?page=botfence`):

- **Spams Blocked Till Now (Hero Stat):** Live real-time counter displaying total malicious bots, fake inquiries, and honeypot traps intercepted and terminated before reaching your database or email inboxes.
- **Time-Windowed Interception Metrics:** Instant breakdown for **Blocked Today**, **Past 7 Days**, **Past 30 Days**, and overall **Threat Interception Rate %**.
- **Form-by-Form Protection Breakdown:** Visual breakdown of which forms are being targeted (Contact Form 7, WPForms, WooCommerce, WordPress Native Comments, Fluent Forms, etc.) with relative threat percentages.
- **Top Spam Signals Detection Analysis:** Real-time telemetry monitoring Honeypot completion, Invalid/tampered cryptographic tokens, Fast automated script submission (<2.5s), Rate limit exhaustion, Repeated content, and Headless automation.
- **Live Stats Refresh Engine:** AJAX-powered one-click live telemetry refresh without reloading the page.
- **Audit Logs & CSV Export:** Privacy-conscious submission audit logs with masked IP addresses (`192.168.1.***`) and instant CSV report export.
- **Integrated Attack Simulator:** Built-in diagnostics simulator to test human, honeypot bot, tampered token, and headless browser scenarios directly within the admin interface.

---

## 📦 Deliverables & Source Code

- **Plugin Source Directory:** [`botfence/`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/)
  - Main bootstrap: [`botfence/BotFence.php`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/BotFence.php)
  - Core subsystems: [`botfence/includes/`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/includes/)
    - Dynamic Honeypot Engine: [`class-honeypot.php`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/includes/class-honeypot.php)
    - Cryptographic Token Manager: [`class-token-manager.php`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/includes/class-token-manager.php)
    - Composite Risk Scoring Engine: [`class-risk-engine.php`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/includes/class-risk-engine.php)
    - Atomic Rate Limiter: [`class-rate-limiter.php`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/includes/class-rate-limiter.php)
    - Form Detector Registry: [`class-form-detector.php`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/includes/class-form-detector.php)
    - Structured Audit Logger: [`class-logger.php`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/includes/class-logger.php)
    - Modern Admin Dashboard: [`class-admin.php`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/includes/class-admin.php)
  - Form builder integrations (10 adapters): [`botfence/integrations/`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/integrations/)
  - Client telemetry script (<6KB): [`botfence/assets/js/botfence.js`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/assets/js/botfence.js)
  - PHPUnit test suite: [`botfence/tests/`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/tests/)
- **Installable Production Archive:** [`BotFence.zip`](file:///home/amalskumar/projects/Plugins/BotFence/BotFence.zip)
- **Detailed Documentation:**
  - [Installation Guide](file:///home/amalskumar/projects/Plugins/BotFence/botfence/docs/INSTALLATION.md)
  - [Configuration Guide](file:///home/amalskumar/projects/Plugins/BotFence/botfence/docs/CONFIGURATION.md)
  - [Security Architecture](file:///home/amalskumar/projects/Plugins/BotFence/botfence/docs/SECURITY.md)
  - [Troubleshooting Guide](file:///home/amalskumar/projects/Plugins/BotFence/botfence/docs/TROUBLESHOOTING.md)

---

## ⚡ Universal Form Builder Compatibility

BotFence automatically attaches server-side validation hooks without breaking AJAX submissions, file uploads, or existing styles across **10 form builders**:

1. **Contact Form 7** (`wpcf7_spam`, REST feedback)
2. **WPForms** (`wpforms_process_initial_errors`, frontend render)
3. **Fluent Forms** (`fluentform/validate_submission`)
4. **Gravity Forms** (`gform_validation`, `gform_entry_is_spam`)
5. **Formidable Forms** (`frm_validate_entry`)
6. **Ninja Forms** (`ninja_forms_submit_data`)
7. **Elementor Pro Forms** (`elementor_pro/forms/validation`)
8. **WooCommerce Forms** (Checkout validation, customer registration, login)
9. **WordPress Native Comments** (`preprocess_comment`)
10. **Custom HTML Forms & Developer API** (`botfence_verify_submission` filter & `botfence_render_fields`)

---

## 🧪 Automated PHPUnit Test Suite

The test suite runs against PHP 8.1–8.5 and tests every adapter, HMAC signing, replay protection, dynamic honeypot rotation, zero-trust IP proxy spoofing prevention, and cached page REST handshake:

```bash
./vendor/bin/phpunit
```

**Results:**
```
OK (59 tests, 272 assertions)
```

---

## 👨‍💻 Author & Attribution

- **Developer:** Amal S Kumar
- **Website:** [https://amalskumar.dev](https://amalskumar.dev)
- **Plugin:** BotFence
- **License:** GPL-2.0-or-later

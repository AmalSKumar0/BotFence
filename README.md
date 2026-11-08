<p align="center">
  <img src="assets/banner-1544x500.png" alt="BotFence Banner" width="100%" style="border-radius: 8px;">
</p>

# BotFence - Universal WordPress Anti-Spam & Automated CAPTCHA Plugin

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b.svg)](https://wordpress.org)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0%2B-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Tests: 84 Passing](https://img.shields.io/badge/Tests-84%20Passing%20(444%20assertions)-brightgreen.svg)]()
[![Author: Amal S Kumar](https://img.shields.io/badge/Author-Amal%20S%20Kumar-blue)](https://amalskumar.dev)

**BotFence** is an enterprise-grade, autonomous, production-ready WordPress security and anti-spam plugin engineered and built by **Amal S Kumar** ([amalskumar.dev](https://amalskumar.dev)). Its purpose is to protect all forms on a WordPress website from automated spam, malicious bots, credential stuffing, fake enquiries, and DDoS form flooding without requiring third-party subscriptions or annoying visual puzzles for visitors.

---

## 🏛️ WordPress.org Plugin Directory Compliance & Audit Evidence

BotFence is designed and built in strict accordance with all 18 guidelines established by the WordPress.org Plugin Review Team. Below is the full audit evidence matrix:

| # | Guideline | Status | Codebase Verification & Compliance Evidence |
|---|---|:---:|---|
| **1** | **GPL-Compatible License** | **COMPLIANT** | Declared as `GPL-2.0-or-later` in [`botfence/BotFence.php`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/BotFence.php#L11-L12) and `GPLv2 or later` in [`botfence/readme.txt`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/readme.txt#L9-L10). All PHP, CSS, and JS assets are 100% free and open-source under GPL. |
| **2** | **Developer Responsibility** | **COMPLIANT** | All source code is original, fully tested, and self-contained. No external unverified third-party libraries or external services are utilized. |
| **3** | **Stable Version Distribution** | **COMPLIANT** | Stable tag `1.0.0` in [`botfence/readme.txt`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/readme.txt#L8) directly synchronizes with [`BOTFENCE_VERSION`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/BotFence.php#L23) in [`botfence/BotFence.php`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/BotFence.php). |
| **4** | **Human-Readable Code** | **COMPLIANT** | Zero code obfuscation, mangling, or base64 execution. Complete, unminified source code is provided in [`botfence/assets/js/botfence.js`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/assets/js/botfence.js) and [`botfence/assets/admin/botfence-admin.js`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/assets/admin/botfence-admin.js). |
| **5** | **No Trialware or Paywalls** | **COMPLIANT** | 100% of features (all 10 adapters, adaptive challenge, rate limiter, dashboard analytics, risk scoring) are completely free, open, and permanently unlocked. Zero paid upgrades or gated features. |
| **6** | **Software as a Service (SaaS)** | **COMPLIANT** | BotFence operates completely autonomously on the local WordPress server. It does not require any paid API keys, subscription plans, or remote SaaS validation. |
| **7** | **No User Tracking Without Consent** | **COMPLIANT** | **Zero phone-home calls.** BotFence makes **zero** outbound HTTP requests (`wp_remote_get`/`wp_remote_post`). No telemetries, analytics, or visitor data are ever transmitted to external servers. Visitor IPs are hashed with SHA-256 locally for rate-limiting. |
| **8** | **No Third-Party Executable Code** | **COMPLIANT** | All JavaScript and CSS assets are served locally from the plugin directory (`BOTFENCE_URL . 'assets/...'`). No external CDNs, remote scripts, or third-party iframes are used. |
| **9** | **Honest & Moral Practices** | **COMPLIANT** | No black-hat SEO, keyword stuffing, fake review prompts, or resource abuse (no crypto-mining, no hidden scripts). |
| **10** | **No Public Links or Credits Without Consent** | **COMPLIANT** | **Zero "Powered By" links on the public frontend.** Honeypot fields and CAPTCHA tokens output standard hidden HTML markup only. Author links (`amalskumar.dev`) appear strictly inside the internal WordPress admin settings page. |
| **11** | **No Admin Dashboard Hijacking** | **COMPLIANT** | **Zero site-wide admin notices.** BotFence registers **no** `admin_notices` hooks across core WordPress screens (Posts, Pages, Dashboard). All settings, metrics, and logs live strictly inside its dedicated menu page (`admin.php?page=botfence`). |
| **12** | **Spam-Free Readmes & Translations** | **COMPLIANT** | [`botfence/readme.txt`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/readme.txt#L4) is restricted to **exactly 5 compliant tags** (`anti-spam`, `captcha`, `spam protection`, `form security`, `forms`). Competitor brand names are excluded from tags, and no cloaked affiliate links exist. |
| **13** | **Use Default WordPress Libraries** | **COMPLIANT** | Admin scripts enqueue WordPress's core `'jquery'` handle via `wp_enqueue_script()`. Frontend scripts use lightweight, vanilla JavaScript with zero external library bundling. |
| **14** | **Avoid Frequent SVN Commits** | **COMPLIANT** | Codebase is organized into clean, tagged releases (`1.0.0`) ready for the WordPress.org SVN release workflow. |
| **15** | **Increment Version Numbers** | **COMPLIANT** | Semantic versioning (`1.0.0`) is synchronized across [`BotFence.php`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/BotFence.php#L6), [`readme.txt`](file:///home/amalskumar/projects/Plugins/BotFence/botfence/readme.txt#L8), and [`README.md`](file:///home/amalskumar/projects/Plugins/BotFence/README.md). |
| **16** | **Complete Plugin at Submission** | **COMPLIANT** | BotFence is complete, functional, and self-contained with 84 unit tests, comprehensive documentation, and zero placeholder stubs. |
| **17** | **Respect Trademarks & Project Names** | **COMPLIANT** | The plugin slug is `botfence` and the name is **BotFence**. It does not use "WordPress", "WooCommerce", or any third-party trademark as the primary slug. |
| **18** | **Directory Maintenance Rights** | **COMPLIANT** | Acknowledged and respected. |

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

The test suite contains **84 comprehensive unit, integration, adversarial, and regression tests** with **444 assertions** executing against PHP 8.1–8.5. It verifies all 10 adapters, Contact Form 7 lifecycle integration, single-use token atomicity, replay attacks, browser autofill discrimination, proxy spoofing protection, and REST API throttling:

```bash
./vendor/bin/phpunit --testdox
```

**Results:**
```
OK (84 tests, 444 assertions)
```

---

## 👨‍💻 Author & Attribution

- **Developer:** Amal S Kumar
- **Website:** [https://amalskumar.dev](https://amalskumar.dev)
- **Plugin:** BotFence
- **License:** GPL-2.0-or-later

=== BotFence ===
Contributors: amalskumar
Donate link: https://amalskumar.dev/
Tags: anti-spam, captcha, spam protection, form security, forms
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Autonomous, universal invisible anti-spam and automated CAPTCHA engine protecting all WordPress forms without third-party subscriptions. Built by Amal S Kumar.

== Description ==

**BotFence** is an enterprise-grade, privacy-conscious WordPress security plugin designed to eliminate automated form spam, fake inquiries, credential stuffing, and bot submissions without frustrating legitimate users with visual puzzles.

Built by **Amal S Kumar** ([amalskumar.dev](https://amalskumar.dev)) with an extensible adapter architecture, BotFence automatically detects and secures all major form builders—including Contact Form 7, WPForms, Fluent Forms, Gravity Forms, Formidable Forms, Ninja Forms, Elementor Pro Forms, WooCommerce checkout/registration, and native WordPress comments—without requiring manual API keys or per-form widget setup.

### Core Protection Layers

* **Cryptographic Short-Lived Tokens:** HMAC-SHA256 signed tokens bound to visitor fingerprints with replay and tampering prevention.
* **Full Caching Plugin Compatibility:** Dynamic REST API (`/wp-json/botfence/v1/token`) and AJAX token refresh ensures pages cached by LiteSpeed, WP Rocket, Cloudflare, and W3 Total Cache always submit fresh tokens.
* **Accessible Dynamic Honeypot:** Rotating concealed trap fields invisible to humans and screen readers, but instantly triggering bot traps.
* **Behaviour & Interaction Telemetry:** Lightweight (<6KB) client-side telemetry measuring form completion time, mouse movement, keystrokes, and synthetic script detection (`isTrusted`, `webdriver`).
* **Multi-Signal Risk Scoring Engine:** Server-side evaluation combining multiple behavioral anomalies into a composite risk score (0–100).
* **Multi-Tier Rate Limiting:** Per-IP, per-hour, per-session, and repeated identical message abuse prevention with temporary quarantine blocks.
* **Executive Security Dashboard:** Live hero metric showing spams blocked till now, time-windowed threat analytics (today, 7 days, 30 days), form-by-form protection breakdown, and one-click AJAX telemetry refresh.
* **GDPR & Privacy Compliant:** Zero tracking cookies, zero external API calls, zero transmission of personal information, and cryptographically hashed IPs.

### Universal Compatibility

1. **Contact Form 7** (wpcf7_spam, REST feedback, mail abort)
2. **WPForms** (wpforms_process_initial_errors, frontend output)
3. **Fluent Forms** (fluentform/validate_submission, form_element_end)
4. **Gravity Forms** (gform_validation, gform_entry_is_spam)
5. **Formidable Forms** (frm_validate_entry, after_opening_tag)
6. **Ninja Forms** (ninja_forms_submit_data)
7. **Elementor Pro Forms** (elementor_pro/forms/validation)
8. **WooCommerce Forms** (Checkout, Customer Registration, Login)
9. **WordPress Comments** (preprocess_comment)
10. **Custom HTML Forms & Developer API** (apply_filters('botfence_verify_submission'))

== Installation ==

1. Upload the `botfence` folder to `/wp-content/plugins/` or upload the `BotFence.zip` archive via **Plugins > Add New > Upload Plugin**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Protection begins automatically! Navigate to **BotFence** in your admin dashboard to review metrics, inspect audit logs, and customize risk thresholds.

== Frequently Asked Questions ==

= Does this require an external API key or paid subscription? =
No. BotFence is 100% self-hosted and autonomous. All cryptographic signing, risk scoring, and rate limiting run directly on your WordPress server.

= Will this work with page caching plugins? =
Yes! When static caching is detected, the plugin asynchronously queries `/wp-json/botfence/v1/token` upon user arrival, updating form inputs before submission.

= Does this block accessibility tools or screen readers? =
No. Honeypot fields use standard `aria-hidden="true"` and off-screen positioning rather than brittle CSS flags. Additionally, an accessible fallback verification challenge is provided for users without JavaScript.

== Changelog ==

= 1.0.0 =
* Initial production release of BotFence.
* Built by Amal S Kumar (https://amalskumar.dev).
* Implemented multi-signal risk scoring engine.
* Added support for all 10 major form builders.
* Added reverse proxy detection and privacy IP hashing.
* Included comprehensive PHPUnit test suite.

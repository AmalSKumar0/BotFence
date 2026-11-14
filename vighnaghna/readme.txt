=== Vighnaghna ===
Contributors: amalskumar
Donate link: https://amalskumar.dev/
Tags: anti-spam, captcha, spam protection, form security, forms
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, invisible anti-spam protection for WordPress forms. No CAPTCHA, no external requests.

== Description ==

**Vighnaghna** (विघ्नघ्न — *Destroyer of Obstacles*) is a lightweight, privacy-focused WordPress security plugin designed to eliminate automated form spam, bot submissions, and abusive requests without visual puzzles, CAPTCHA challenges, or external third-party requests.

**Tagline:** *Intelligent Form Protection. Invisible to Your Visitors.*

Built by **Amal S Kumar** ([amalskumar.dev](https://amalskumar.dev)), Vighnaghna operates autonomously on your server. It secures Contact Form 7, WPForms, Fluent Forms, Gravity Forms, Formidable Forms, Ninja Forms, Elementor Pro Forms, WooCommerce checkout and registration, and native WordPress comments without transmitting data outside your website.

### Core Protection Layers

* **Cryptographic Form Tokens:** HMAC-SHA256 signed single-use tokens with replay protection and nonce expiration. Designed with a safe proxy and mobile IP policy to prevent false positives when mobile carriers change IP addresses mid-session.
* **Graceful Token Lifecycle:** Expired tokens are treated as lifecycle events rather than malicious attacks, offering visitors a clear, friendly retry path.
* **Full Caching Compatibility:** Dynamic REST API (`/wp-json/vighnaghna/v1/token`) and AJAX token refresh ensure static caching plugins (LiteSpeed Cache, WP Rocket, Cloudflare, W3 Total Cache) always deliver fresh tokens.
* **Accessible Dynamic Honeypot:** Rotating concealed trap fields invisible to humans and screen readers (`aria-hidden="true"`), instantly intercepting automated bot crawlers.
* **Behavior Telemetry:** Client-side telemetry measuring form interaction duration, mouse dynamics, keystrokes, and synthetic script execution signals (`isTrusted`, `webdriver`).
* **Multi-Signal Risk Scoring:** Server-side engine evaluating composite scores (0–100) with fully configurable point weights for headless browsers, synthetic events, honeypot traps, and rate thresholds.
* **Multi-Tier Rate Limiting:** Per-IP, per-minute, and per-hour frequency controls with temporary quarantine blocks.
* **Executive Security Dashboard:** Live hero counter showing spams blocked till now, time-windowed threat analytics (today, 7 days, 30 days), form-by-form protection breakdown, and interactive simulator.
* **Privacy & GDPR Compliance:** Zero third-party API calls, zero tracking cookies, zero external telemetry, masked visitor IP addresses, and no retention of personal form content.

### Universal Compatibility

1. **Contact Form 7** (`wpcf7_spam`, mail abort, and AJAX feedback)
2. **WPForms** (`wpforms_process_initial_errors`, frontend field injection)
3. **Fluent Forms** (`fluentform/validate_submission`, form rendering)
4. **Gravity Forms** (`gform_validation`, `gform_entry_is_spam`)
5. **Formidable Forms** (`frm_validate_entry`, opening tag hooks)
6. **Ninja Forms** (`ninja_forms_submit_data`)
7. **Elementor Pro Forms** (`elementor_pro/forms/validation`)
8. **WooCommerce Forms** (Checkout, Customer Registration, Account)
9. **WordPress Comments** (`preprocess_comment`)
10. **Custom HTML Forms & Developer API** (`apply_filters('vighnaghna_verify_submission')`)

== Installation ==

1. Upload the `vighnaghna` folder to the `/wp-content/plugins/` directory, or install via **Plugins > Add New > Upload Plugin** with `vighnaghna.zip`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Form protection begins automatically for all active form builders.
4. Navigate to **Vighnaghna** in your admin dashboard to configure risk thresholds, inspect logs, or run diagnostic simulations.

== Frequently Asked Questions ==

= Does this require an external API key or third-party service? =
No. Vighnaghna is 100% self-hosted and autonomous. All cryptographic signing, risk scoring, and rate limiting execute entirely on your WordPress server. No external requests are made.

= Will this work with page caching plugins? =
Yes. On cached pages, the client script asynchronously fetches a fresh token via `/wp-json/vighnaghna/v1/token` upon visitor arrival before form submission occurs.

= How does the plugin handle mobile networks or dynamic IP addresses? =
Vighnaghna uses a safe IP comparison policy. It records IP telemetry for diagnostic logs without strictly rejecting legitimate submissions if a mobile carrier or proxy switches IP addresses between page load and submission.

= What happens when a user leaves a form open for a long time? =
If a token expires, Vighnaghna handles it gracefully. It applies a low diagnostic risk weight and presents a clear, user-friendly prompt to refresh and retry, ensuring genuine users are not misclassified as malicious spammers.

= Does this block screen readers or assistive technologies? =
No. Honeypot decoy fields use standard accessibility attributes (`aria-hidden="true"`, `tabindex="-1"`) and off-screen positioning so assistive devices safely skip them. Users without JavaScript can also complete an accessible fallback challenge.

= Is Vighnaghna compliant with WordPress.org Plugin Directory guidelines? =
Yes. Vighnaghna is licensed under GPLv2 or later, makes zero external network requests, avoids modifying WordPress core options (like `active_plugins`), properly enqueues all scripts and styles, uses standard WordPress APIs, and secures all administrative actions with nonces and capability checks.

== Screenshots ==

1. Executive Security Dashboard displaying live spams blocked hero counter, time-windowed threat analytics, and form-by-form protection breakdown.
2. Form Builder Integrations panel showing automatic detection and protection status for all supported form builders.
3. Risk Scoring & Security Weights configuration matrix.
4. Privacy-first audit logs with cryptographically masked IP addresses and CSV export.
5. Interactive threat simulator and diagnostic test runner.

== Changelog ==

= 1.0.0 =
* Complete rebrand to Vighnaghna (विघ्नघ्न — Destroyer of Obstacles).
* Added configurable weights for Headless Browser (Selenium/webdriver) and Untrusted Synthetic Event detection.
* Separated expired token handling from malicious token tampering with safe retry prompts.
* Implemented safe IP policy preventing false positives on mobile networks and dynamic proxies.
* Fully transitioned to WordPress script and style enqueue APIs; removed inline script execution.
* Removed legacy `active_plugins` modification routines.
* Implemented safe, idempotent database migration from legacy installations.
* Comprehensive WordPress.org plugin directory compliance remediation.

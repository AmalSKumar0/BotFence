# BotFence - Configuration & Tuning Guide

**BotFence** is built by **Amal S Kumar** ([amalskumar.dev](https://amalskumar.dev)).

**BotFence** works immediately out of the box with intelligent defaults. However, every website has unique traffic profiles, form lengths, and infrastructure setups. This guide covers how to fine-tune every module.

---

## 1. Form Builder Protections (Integrations Tab)

The **Form Integrations** tab lists all 10 supported form plugins with real-time detection badges:

- **Contact Form 7:** Hooks into `wpcf7_spam` filter and REST API feedback endpoint.
- **WPForms:** Hooks into `wpforms_process_initial_errors` to halt entry processing and email notifications.
- **Fluent Forms:** Hooks into `fluentform/validate_submission` and form rendering actions.
- **Gravity Forms:** Hooks into `gform_validation` and `gform_entry_is_spam`.
- **Formidable Forms:** Hooks into `frm_validate_entry`.
- **Ninja Forms:** Hooks into `ninja_forms_submit_data`.
- **Elementor Pro Forms:** Hooks into `elementor_pro/forms/validation` via the AJAX handler.
- **WooCommerce:** Hooks into Checkout (`woocommerce_checkout_process`), Registration (`woocommerce_register_post`), and Login.
- **WordPress Comments:** Hooks into `preprocess_comment` to block automated comment spam with a standard HTTP 403 response.
- **Custom HTML Forms:** Universally injects into `<form method="post">` blocks in post content.

### Form Exclusions (Whitelist)

If you have an internal admin form or an automated webhook that receives external POST submissions, add its ID to the **Excluded Forms** textarea (one per line):
```
order_webhook
cf7:123
wpforms:45
```

---

## 2. Risk Engine & Thresholds (Risk & Security Tab)

Submissions are evaluated on a composite risk scale from **0 to 100 points**:

### Recommended Thresholds

- **Allow Threshold (0–29):** Submissions below 30 are considered human and proceed without hindrance.
- **Challenge Threshold (30–59):** Submissions scoring between 30 and 59 trigger an accessible interactive verification step (e.g., accessible calculation puzzle).
- **Block Threshold (60–100):** Submissions scoring 60 or higher are terminated before emails are sent or entries saved.

### Minimum Submission Time

- Default: `2.5` seconds.
- Forms filled and submitted faster than this time are assigned risk points (default: `+30`).
- **Tuning Tip:** If you have very short 1-field newsletter signup forms, you may lower this to `1.5s`. For multi-field contact forms, `2.5s` to `3.0s` is ideal.

### Token Lifetime

- Default: `600` seconds (10 minutes).
- Defines how long a cryptographically signed token remains valid after page load.
- If a user leaves the tab open for longer, the client script will automatically refresh the token on interaction or cached page load.

---

## 3. Rate Limiting & Abuse Prevention (Rate Limiting Tab)

### Submission Frequency Limits

- **Submissions per Minute per IP:** Default: `5`. Prevents automated rapid-fire script flooding.
- **Submissions per Hour per IP:** Default: `30`. Prevents distributed trickle bots.
- **Quarantine Duration:** Default: `900` seconds (15 minutes). If an IP exceeds 2x the per-minute threshold, it is placed into temporary quarantine, where all subsequent submissions are instantly rejected with 100 risk points.

### Reverse Proxy & CDN Configuration

If your site is hosted behind Cloudflare, AWS Application Load Balancer, Fastly, or a local Nginx reverse proxy, client IP addresses in `REMOTE_ADDR` will show the proxy's IP.

To configure safely:
1. Enable **Trust Reverse Proxies**.
2. In the **Trusted Proxy IPs / CIDRs** field, list the IP addresses or CIDR ranges of your trusted proxies:
   ```
   127.0.0.1
   10.0.0.0/8
   172.16.0.0/12
   192.168.0.0/16
   ```
   *(For Cloudflare, enter Cloudflare's published IP ranges).*

> [!CAUTION]
> Never enable "Trust Reverse Proxies" with empty or `0.0.0.0/0` entries. Doing so would allow malicious clients to spoof their IP address by injecting arbitrary `X-Forwarded-For` HTTP headers!

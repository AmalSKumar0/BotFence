# BotFence - Troubleshooting Guide

**BotFence** is built by **Amal S Kumar** ([amalskumar.dev](https://amalskumar.dev)).

This guide provides step-by-step diagnostics for common scenarios, false-positive investigations, and caching setups.

---

## 1. Submissions Blocked with "Expired Token" on Cached Pages

### Cause
Static page caching plugins (such as WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache, or Cloudflare HTML Edge Caching) serve cached HTML pages. If a page was cached hours ago, the cryptographic token embedded in the original HTML is expired.

### Solution
BotFence has native support for cached pages via background REST API handshake (`/wp-json/botfence/v1/token`) and AJAX fallback (`botfence_refresh_token`).

If expired token blocks persist:
1. Verify that the REST API is accessible on your site:
   - Visit `https://your-domain.com/wp-json/botfence/v1/token` in your browser.
   - It should return a valid JSON payload with `{"token":"...","expires_at":...}`.
2. If your security plugin or Cloudflare firewall blocks the REST API, whitelist the `/wp-json/botfence/v1/` route.
3. Check your browser developer console for JavaScript errors on form pages that might stop `botfence.js` from executing.

---

## 2. Legitimate Users Blocked by Fast Submission Trigger

### Cause
Autofill features in browsers (like 1Password, Bitwarden, Apple Keychain, or Chrome Autofill) can fill multi-field forms instantly. If the user clicks "Submit" immediately after loading the page (under 2.5 seconds), the submission receives `30` risk points.

### Solution
1. In **BotFence > Risk & Security**, adjust **Minimum Submission Time** from `2.5` to `1.5` seconds.
2. Verify that **Adaptive Verification** is enabled. A fast human submission (scoring 30) will receive an interactive verification challenge rather than an outright block, allowing the visitor to proceed smoothly.

---

## 3. Investigating Submissions via Logs

To analyze why a submission was blocked:
1. Navigate to **BotFence > Logs & Activity**.
2. Locate the submission by date/time or search by the form ID or masked IP.
3. Click **View Signals** on the row to expand the signal breakdown:
   - Example 1: `Honeypot field filled (+100 pts)` -> Confirms automated bot populated hidden trap input.
   - Example 2: `Invalid or replayed token (+100 pts)` -> Form was submitted without a valid signature or token was reused.
   - Example 3: `Excessive URLs in form content (+25 pts)` -> Body contained 3+ links.

---

## 4. Running the Diagnostics Test Simulator

To test the internal risk scoring engine without submitting live frontend forms:
1. Navigate to **BotFence > Diagnostics & Simulator**.
2. Select a scenario from the dropdown:
   - **Normal Human:** Valid duration, natural keystrokes and mouse movements.
   - **Automated Bot (Honeypot):** Populates the dynamic hidden field.
   - **Malicious Bot (Invalid Token):** Tampered HMAC signature.
   - **Rapid Script:** 0.25s completion time.
   - **Headless Automation:** Selenium webdriver active.
3. Click **Run Test Simulation**.
4. Review the returned decision (`ALLOW`, `CHALLENGE`, or `BLOCK`), risk score, and signal breakdown.

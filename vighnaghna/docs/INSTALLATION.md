# Vighnaghna - Installation & Requirements Guide

**Vighnaghna** is built by **Amal S Kumar** ([amalskumar.dev](https://amalskumar.dev)).

---

## System Requirements

- **PHP:** 8.1 or later (Fully tested and compatible with PHP 8.1, 8.2, 8.3, 8.4, and 8.5).
- **WordPress:** 5.8 or later (Tested up to current stable WP 6.7+).
- **MySQL / MariaDB:** MySQL 5.7+ or MariaDB 10.3+ with InnoDB support.
- **PHP Extensions:** `json`, `mbstring`, `openssl`, `filter`, `hash`.

---

## Installation Methods

### Method 1: WordPress Admin Upload (Recommended)

1. Download the `Vighnaghna.zip` archive from the releases page or project build.
2. In your WordPress administration panel, navigate to **Plugins > Add New Plugin**.
3. Click the **Upload Plugin** button at the top of the screen.
4. Select `Vighnaghna.zip` and click **Install Now**.
5. Once uploaded, click **Activate Plugin**.

### Method 2: Manual SFTP / SSH Installation

1. Extract the `Vighnaghna.zip` archive on your local computer.
2. Connect to your server via SFTP or SSH.
3. Upload the unzipped `vighnaghna` folder into the WordPress plugins directory:
   ```
   /wp-content/plugins/vighnaghna/
   ```
4. Verify file permissions:
   - Directories: `755`
   - Files: `644`
5. Go to **Plugins > Installed Plugins** in WordPress and click **Activate** under **Vighnaghna**.

### Method 3: WP-CLI Installation

```bash
wp plugin install /path/to/Vighnaghna.zip --activate
```

---

## Post-Activation Verification

Upon activation, Vighnaghna automatically:
1. Creates two high-performance indexed database tables:
   - `wp_vighnaghna_logs` (Stores privacy-conscious submission logs)
   - `wp_vighnaghna_rate_limits` (Atomic submission counters and quarantine blocks)
   *(Backwards compatibility: Existing legacy `wp_kl_antispam_*` tables are automatically detected and preserved).*
2. Generates a unique, high-entropy 256-bit cryptographic HMAC secret key.
3. Discovers all active form builder plugins on your site and automatically attaches protection hooks.
4. Schedules an hourly maintenance cron (`vighnaghna_cleanup_cron`) to prune expired rate limits and prune old audit logs according to your retention settings.

To confirm protection status, open **Vighnaghna** in your WordPress sidebar:
- Check that the header badge shows **Active & Protecting**.
- Click the **Diagnostics & Simulator** tab and click **Run Test Simulation** to verify that the multi-layer risk engine is operating properly.

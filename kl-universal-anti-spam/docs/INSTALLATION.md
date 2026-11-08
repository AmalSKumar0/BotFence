# Installation & Requirements Guide

## System Requirements

- **PHP:** 8.1 or later (Fully tested and compatible with PHP 8.1, 8.2, 8.3, 8.4, and 8.5).
- **WordPress:** 5.8 or later (Tested up to current stable WP 6.7+).
- **MySQL / MariaDB:** MySQL 5.7+ or MariaDB 10.3+ with InnoDB support.
- **PHP Extensions:** `json`, `mbstring`, `openssl`, `filter`, `hash`.

---

## Installation Methods

### Method 1: WordPress Admin Upload (Recommended)

1. Download the `kl-universal-anti-spam.zip` archive from the releases page or project build.
2. In your WordPress administration panel, navigate to **Plugins > Add New Plugin**.
3. Click the **Upload Plugin** button at the top of the screen.
4. Select `kl-universal-anti-spam.zip` and click **Install Now**.
5. Once uploaded, click **Activate Plugin**.

### Method 2: Manual SFTP / SSH Installation

1. Extract the `kl-universal-anti-spam.zip` archive on your local computer.
2. Connect to your server via SFTP or SSH.
3. Upload the unzipped `kl-universal-anti-spam` folder into the WordPress plugins directory:
   ```
   /wp-content/plugins/kl-universal-anti-spam/
   ```
4. Verify file permissions:
   - Directories: `755`
   - Files: `644`
5. Go to **Plugins > Installed Plugins** in WordPress and click **Activate** under **KL Universal Anti-Spam**.

### Method 3: WP-CLI Installation

```bash
wp plugin install /path/to/kl-universal-anti-spam.zip --activate
```

---

## Post-Activation Verification

Upon activation, the plugin automatically:
1. Creates two high-performance indexed database tables:
   - `wp_kl_antispam_logs` (Stores privacy-conscious submission logs)
   - `wp_kl_antispam_rate_limits` (Atomic submission counters and quarantine blocks)
2. Generates a unique, high-entropy 256-bit cryptographic HMAC secret key.
3. Discovers all active form builder plugins on your site and applies protection.
4. Schedules an hourly maintenance cron (`kl_antispam_cleanup_cron`) to prune expired rate limits and prune old audit logs.

To confirm protection status, open **KL Anti-Spam** in your WordPress sidebar:
- Check that the header badge shows **Active & Protecting**.
- Click **Diagnostics & Simulator** and click **Run Test Simulation** to ensure the risk engine is operating properly.

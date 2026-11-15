import React from 'react';
import { Settings, Sliders, Shield, Zap, Lock, Database, ArrowRight, Check } from 'lucide-react';
import { SEO } from '../components/SEO';

interface PageProps {
  onOpenDownload: () => void;
  onNavigate: (path: string) => void;
}

export const ConfigurationPage: React.FC<PageProps> = ({ onOpenDownload, onNavigate }) => {
  const structuredData = {
    '@context': 'https://schema.org',
    '@type': 'TechArticle',
    'headline': 'Vighnaghna Configuration & Settings Tuning Guide',
    'description': 'Comprehensive administrator manual for configuring Vighnaghna: risk scoring weights, rate limiting, proxy headers, and form builder toggles.',
    'url': 'https://vighnaghna.amalskumar.dev/docs/configuration',
    'author': {
      '@type': 'Person',
      'name': 'Amal S Kumar',
      'url': 'https://amalskumar.dev'
    },
    'breadcrumb': {
      '@type': 'BreadcrumbList',
      'itemListElement': [
        { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://vighnaghna.amalskumar.dev/' },
        { '@type': 'ListItem', 'position': 2, 'name': 'Documentation', 'item': 'https://vighnaghna.amalskumar.dev/docs' },
        { '@type': 'ListItem', 'position': 3, 'name': 'Configuration', 'item': 'https://vighnaghna.amalskumar.dev/docs/configuration' }
      ]
    }
  };

  const tabs = [
    {
      title: '1. Executive Dashboard Tab',
      desc: 'Provides real-time threat intelligence without third-party tracking scripts. Displays total spams blocked, hourly threat frequency, and form-by-form protection metrics.',
      fields: [
        { name: 'Live Threat Counter', default: 'Real-time tally', info: 'Displays lifetime spams neutralized across all forms.' },
        { name: 'Threat Breakdown', default: 'Aggregated stats', info: 'Shows spam distribution across Contact Form 7, WPForms, and comments.' },
        { name: 'Threat Simulator', default: 'Interactive runner', info: 'Simulates attacks (fast submit, honeypot trap, headless) to verify server readiness.' }
      ]
    },
    {
      title: '2. General Settings Tab',
      desc: 'Controls global plugin status, token lifetime windows, and minimum human interaction thresholds.',
      fields: [
        { name: 'Global Protection', default: 'Enabled (1)', info: 'Master switch to toggle protection across all WordPress forms.' },
        { name: 'Minimum Submission Time', default: '2.5 seconds', info: 'Threshold below which a submission is flagged as unrealistically fast.' },
        { name: 'Token Lifetime Window', default: '600 seconds (10 min)', info: 'Cryptographic token expiration time. Cached pages refresh automatically via REST API.' },
        { name: 'Polymorphic Honeypot', default: 'Enabled (1)', info: 'Generates random decoy input fields with strict accessibility attributes.' },
        { name: 'Adaptive Human Challenge', default: 'Enabled (1)', info: 'Presents an accessible arithmetic verification challenge if risk score is in medium challenge range (30-59).' }
      ]
    },
    {
      title: '3. Risk Scoring & Weights Tab',
      desc: 'Vighnaghna evaluates 10 distinct threat signals. Submissions scoring >= 80 points are blocked; 30-79 points trigger adaptive verification; < 30 points are allowed seamlessly.',
      fields: [
        { name: 'Honeypot Triggered', default: '100 points', info: 'Instant block when a hidden decoy field is populated.' },
        { name: 'Invalid / Forged Token', default: '100 points', info: 'Instant block when HMAC signature is invalid or replayed.' },
        { name: 'Expired Token', default: '80 points', info: 'Blocks stale session and offers a friendly, safe retry prompt to refresh the page.' },
        { name: 'Automated Headless Browser', default: '50 points', info: 'Triggered when navigator.webdriver or automation flags are detected.' },
        { name: 'Synthetic Submit Event', default: '45 points', info: 'Triggered when event.isTrusted is false (programmatic script click).' },
        { name: 'Rate Limit Exceeded', default: '40 points', info: 'Penalty applied when IP exceeds per-minute or per-hour thresholds.' },
        { name: 'Repeated Message Content', default: '35 points', info: 'Identifies identical spam text submitted repeatedly.' },
        { name: 'Unrealistically Fast Submit', default: '30 points', info: 'Penalizes form completion under the minimum submission threshold.' },
        { name: 'Excessive URLs', default: '25 points', info: 'Applied when submitted text contains more than 2 web links.' },
        { name: 'Suspicious Zero Interaction', default: '20 points', info: 'Applied if form contains substantial text with 0 mouse/keyboard events.' }
      ]
    },
    {
      title: '4. Rate Limiting & Proxy Trust Tab',
      desc: 'Mitigates distributed brute-force attacks and POST flooding while safely supporting reverse proxies (Cloudflare, AWS CloudFront, Sucuri).',
      fields: [
        { name: 'Submissions Per Minute', default: '5 submissions', info: 'Maximum form submissions permitted from a single IP per minute.' },
        { name: 'Submissions Per Hour', default: '30 submissions', info: 'Hourly rate limit cap per IP identifier.' },
        { name: 'Quarantine Duration', default: '900 seconds (15 min)', info: 'Temporary cooling-off period for IPs exceeding rate limits.' },
        { name: 'Trust Reverse Proxies', default: 'Disabled (0)', info: 'Enable if your site is behind Cloudflare, NGINX reverse proxy, or AWS ALB.' },
        { name: 'Trusted Proxy IP List', default: 'Empty (Configurable)', info: 'Comma-separated CIDR blocks trusted to forward client IPs via HTTP_X_FORWARDED_FOR.' }
      ]
    },
    {
      title: '5. Form Builder Integrations Tab',
      desc: 'One-click toggles allowing you to selectively enable or disable protection per form builder.',
      fields: [
        { name: 'Contact Form 7', default: 'Enabled (1)', info: 'Hooks into wpcf7_form_elements, wpcf7_spam, and wpcf7_skip_mail.' },
        { name: 'WPForms', default: 'Enabled (1)', info: 'Hooks into wpforms_process and wpforms_frontend_output.' },
        { name: 'Fluent Forms', default: 'Enabled (1)', info: 'Hooks into fluentform_before_insert_payment_form and validation.' },
        { name: 'Gravity Forms', default: 'Enabled (1)', info: 'Hooks into gform_field_input and gform_validation.' },
        { name: 'WooCommerce Checkout & Register', default: 'Enabled (1)', info: 'Protects customer registration, checkout, and account login.' },
        { name: 'WordPress Native Comments', default: 'Enabled (1)', info: 'Secures wp_insert_comment against comment spam bots.' }
      ]
    },
    {
      title: '6. Privacy Audit Logs Tab',
      desc: 'Real-time security log inspection with cryptographic data minimization.',
      fields: [
        { name: 'Log Retention Days', default: '30 days', info: 'Logs older than this threshold are purged automatically by hourly cron.' },
        { name: 'Masked IP Display', default: 'Subnet Masked', info: 'IPs are displayed as 192.0.2.xxx to protect visitor privacy under GDPR.' },
        { name: 'CSV Export Defense', default: 'Active', info: 'Prepends single quotes to formulas (=, +, -, @) to prevent spreadsheet code injection.' }
      ]
    }
  ];

  return (
    <div className="w-full bg-[#0A0A0A] text-white font-sans text-left">
      <SEO
        title="Configuration Guide — Tuning Vighnaghna Anti-Spam Settings"
        description="Detailed administrator manual for configuring Vighnaghna: risk scoring weights, rate limiting, proxy headers, and form builder toggles."
        canonicalUrl="https://vighnaghna.amalskumar.dev/docs/configuration"
        structuredData={structuredData}
      />

      {/* Header */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 pt-20 pb-12">
        <div className="space-y-4 max-w-3xl">
          <div className="flex items-center gap-2 text-xs font-mono text-[#8E8E8E] uppercase tracking-wider">
            <a href="/docs" onClick={(e) => { e.preventDefault(); onNavigate('/docs'); }} className="hover:text-white transition-colors">
              Docs
            </a>
            <span>/</span>
            <span className="text-[#FF4D6D]">Configuration</span>
          </div>
          <h1 className="text-4xl sm:text-5xl font-[850] tracking-tight text-white leading-[1.1]">
            Configuration &amp; Tuning Guide
          </h1>
          <p className="text-sm sm:text-base text-[#8E8E8E] leading-relaxed">
            Vighnaghna is designed to work immediately upon activation with optimal production defaults. Use this reference to fine-tune sensitivity, rate limits, and proxy trust.
          </p>
        </div>
      </section>

      {/* Settings Tabs Walkthrough */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-8 space-y-12">
        {tabs.map((tab, idx) => (
          <div key={idx} className="card-dark-glass p-8 sm:p-10 space-y-6 border border-[#222222]">
            <div className="space-y-2 border-b border-[#222222] pb-4">
              <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight">
                {tab.title}
              </h2>
              <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
                {tab.desc}
              </p>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs border-collapse">
                <thead>
                  <tr className="border-b border-[#222222] text-[#8E8E8E] uppercase tracking-wider text-[11px]">
                    <th className="py-3 px-4 font-semibold">Setting Name</th>
                    <th className="py-3 px-4 font-semibold">Production Default</th>
                    <th className="py-3 px-4 font-semibold">Technical Behavior &amp; Purpose</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#1C1C1C] text-neutral-300">
                  {tab.fields.map((field, fIdx) => (
                    <tr key={fIdx} className="hover:bg-[#141414] transition-colors">
                      <td className="py-3 px-4 font-medium text-white">{field.name}</td>
                      <td className="py-3 px-4 font-mono text-[#FF4D6D]">{field.default}</td>
                      <td className="py-3 px-4 text-[#8E8E8E]">{field.info}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        ))}
      </section>

      {/* Recommended Tuning Callout */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-12">
        <div className="card-dark-glass p-8 sm:p-10 space-y-4 border border-[#2B2B2B]">
          <h3 className="text-lg font-bold text-white flex items-center gap-2">
            <Sliders className="w-5 h-5 text-[#FF4D6D]" />
            <span>High-Traffic Site Tuning Advice</span>
          </h3>
          <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
            For e-commerce stores running Flash Sales or sites behind Cloudflare Railgun/HTML Edge caching:
          </p>
          <ul className="space-y-2 text-xs text-neutral-300 list-disc list-inside leading-relaxed">
            <li>Increase <code className="text-white font-mono">Rate Limit IP Per Minute</code> from 5 to 15 if multiple corporate office users share one NAT IP.</li>
            <li>Enable <code className="text-white font-mono">Trust Reverse Proxies</code> and enter Cloudflare IP CIDRs if you notice server IP addresses logged instead of visitor IPs.</li>
            <li>Ensure client JavaScript <code className="text-white font-mono">vighnaghna.min.js</code> is not deferred past form submission.</li>
          </ul>
        </div>
      </section>
    </div>
  );
};

import React from 'react';
import { ShieldCheck, Lock, Eye, Database, FileText, CheckCircle2 } from 'lucide-react';
import { SEO } from '../components/SEO';

interface PageProps {
  onOpenDownload: () => void;
  onNavigate: (path: string) => void;
}

export const PrivacyPage: React.FC<PageProps> = ({ onOpenDownload, onNavigate }) => {
  const structuredData = {
    '@context': 'https://schema.org',
    '@type': 'WebPage',
    'name': 'Privacy & Security Architecture — Vighnaghna WordPress Anti-Spam',
    'description': 'Technical privacy specification for Vighnaghna: zero tracking cookies, zero external API requests, cryptographic IP hashing, and native GDPR compliance.',
    'url': 'https://vighnaghna.amalskumar.dev/privacy',
    'breadcrumb': {
      '@type': 'BreadcrumbList',
      'itemListElement': [
        { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://vighnaghna.amalskumar.dev/' },
        { '@type': 'ListItem', 'position': 2, 'name': 'Privacy & Security', 'item': 'https://vighnaghna.amalskumar.dev/privacy' }
      ]
    }
  };

  return (
    <div className="w-full bg-[#0A0A0A] text-white font-sans text-left">
      <SEO
        title="Privacy Architecture & GDPR Compliance — Vighnaghna"
        description="Comprehensive technical privacy declaration for Vighnaghna: 100% self-hosted, zero tracking cookies, zero external network requests, and cryptographic IP masking."
        canonicalUrl="https://vighnaghna.amalskumar.dev/privacy"
        structuredData={structuredData}
      />

      {/* Header */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 pt-20 pb-12">
        <div className="space-y-4 max-w-3xl">
          <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#FF4D6D] inline-block">
            Data Sovereignty &amp; Privacy
          </span>
          <h1 className="text-4xl sm:text-5xl font-[850] tracking-tight text-white leading-[1.1]">
            Privacy &amp; Security Architecture
          </h1>
          <p className="text-sm sm:text-base text-[#8E8E8E] leading-relaxed">
            Form security should never compromise visitor privacy. Vighnaghna is engineered from the ground up to protect WordPress websites without external tracking, third-party cookies, or surveillance telemetry.
          </p>
        </div>
      </section>

      {/* Core Privacy Guarantees */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-8">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          
          <div className="card-dark-glass p-8 sm:p-10 space-y-4 border border-[#262626]">
            <div className="w-10 h-10 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D]">
              <Lock className="w-5 h-5" />
            </div>
            <h2 className="text-xl font-bold text-white tracking-tight">Zero External Network Requests</h2>
            <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
              Unlike Google reCAPTCHA, hCaptcha, or Akismet, Vighnaghna makes <strong>zero outbound HTTP requests</strong>. All honeypots, tokens, behavioral signals, and rate limits are verified entirely on your own WordPress server. Your visitors' IP addresses and form submissions never leave your infrastructure.
            </p>
          </div>

          <div className="card-dark-glass p-8 sm:p-10 space-y-4 border border-[#262626]">
            <div className="w-10 h-10 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D]">
              <Eye className="w-5 h-5" />
            </div>
            <h2 className="text-xl font-bold text-white tracking-tight">Zero Tracking Cookies</h2>
            <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
              Vighnaghna sets <strong>zero cookies</strong> in the visitor's browser. It creates no cross-site advertising IDs, browsing histories, or persistent tracking tokens. Because no non-essential cookies are set, Vighnaghna does not trigger EU ePrivacy Directive cookie consent requirements.
            </p>
          </div>

          <div className="card-dark-glass p-8 sm:p-10 space-y-4 border border-[#262626]">
            <div className="w-10 h-10 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D]">
              <Database className="w-5 h-5" />
            </div>
            <h2 className="text-xl font-bold text-white tracking-tight">Cryptographic IP Hashing &amp; Masking</h2>
            <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
              For rate limiting, client IP addresses are hashed using SHA-256 with a unique, server-generated secret salt. In security audit logs, IP addresses are subnet-masked (<code className="text-white font-mono">192.0.2.xxx</code> or <code className="text-white font-mono">2001:db8::xxxx</code>), preventing the storage of raw personal data while preserving security diagnostics.
            </p>
          </div>

          <div className="card-dark-glass p-8 sm:p-10 space-y-4 border border-[#262626]">
            <div className="w-10 h-10 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D]">
              <ShieldCheck className="w-5 h-5" />
            </div>
            <h2 className="text-xl font-bold text-white tracking-tight">Automated Data Minimization &amp; Purging</h2>
            <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
              Vighnaghna complies strictly with GDPR Article 5(1)(c) data minimization. Ephemeral single-use tokens are purged upon consumption. Security audit logs and rate-limiting buckets older than your configured retention window (default: 30 days) are permanently pruned via automated hourly cron.
            </p>
          </div>

        </div>
      </section>

      {/* Regulatory Compliance Breakdown */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-12">
        <div className="card-dark-glass p-8 sm:p-12 space-y-8 border border-[#2B2B2B]">
          <div className="space-y-2">
            <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#8E8E8E]">
              Regulatory Mapping
            </span>
            <h2 className="text-2xl sm:text-3xl font-[850] tracking-tight text-white">
              GDPR &amp; CCPA Compliance Details
            </h2>
          </div>

          <div className="space-y-4 text-xs sm:text-sm text-neutral-300">
            <div className="p-5 rounded-xl bg-[#141414] border border-[#222222] space-y-2">
              <h3 className="font-bold text-white text-base">GDPR (General Data Protection Regulation — EU)</h3>
              <p className="text-[#8E8E8E] leading-relaxed">
                Under European Union GDPR, IP addresses are classified as personal data. Third-party CAPTCHA widgets transmit visitor IP addresses to US-based cloud servers, which requires user consent under Schrems II rulings. Vighnaghna avoids cross-border data transfers entirely because all processing occurs on your hosting infrastructure.
              </p>
            </div>

            <div className="p-5 rounded-xl bg-[#141414] border border-[#222222] space-y-2">
              <h3 className="font-bold text-white text-base">CCPA / CPRA (California Consumer Privacy Act)</h3>
              <p className="text-[#8E8E8E] leading-relaxed">
                Vighnaghna does not sell, share, or monetize visitor personal information. It does not construct user profiles or perform cross-context behavioral advertising. Site owners can confidently deploy Vighnaghna without updating "Do Not Sell My Info" disclosures.
              </p>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

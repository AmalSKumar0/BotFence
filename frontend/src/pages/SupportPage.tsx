import React from 'react';
import { LifeBuoy, Bug, ShieldAlert, ArrowUpRight, CheckCircle2, AlertCircle } from 'lucide-react';
import { SEO } from '../components/SEO';

interface PageProps {
  onOpenDownload: () => void;
  onNavigate: (path: string) => void;
}

export const SupportPage: React.FC<PageProps> = ({ onOpenDownload, onNavigate }) => {
  const structuredData = {
    '@context': 'https://schema.org',
    '@type': 'ContactPage',
    'name': 'Vighnaghna Support & Diagnostic Assistance',
    'description': 'Technical assistance, troubleshooting diagnostics, false-positive resolution, and issue reporting for the Vighnaghna WordPress plugin.',
    'url': 'https://vighnaghna.amalskumar.dev/support',
    'breadcrumb': {
      '@type': 'BreadcrumbList',
      'itemListElement': [
        { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://vighnaghna.amalskumar.dev/' },
        { '@type': 'ListItem', 'position': 2, 'name': 'Support', 'item': 'https://vighnaghna.amalskumar.dev/support' }
      ]
    }
  };

  return (
    <div className="w-full bg-[#0A0A0A] text-white font-sans text-left">
      <SEO
        title="Support & Troubleshooting Guide — Vighnaghna"
        description="Technical support resources, diagnostic checks, false-positive resolution, and GitHub issue reporting for the Vighnaghna WordPress anti-spam plugin."
        canonicalUrl="https://vighnaghna.amalskumar.dev/support"
        structuredData={structuredData}
      />

      {/* Header */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 pt-20 pb-12">
        <div className="space-y-4 max-w-3xl">
          <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#FF4D6D] inline-block">
            Help &amp; Diagnostics
          </span>
          <h1 className="text-4xl sm:text-5xl font-[850] tracking-tight text-white leading-[1.1]">
            Vighnaghna Support Hub
          </h1>
          <p className="text-sm sm:text-base text-[#8E8E8E] leading-relaxed">
            Need assistance resolving false positives, configuring reverse proxies, or reporting an edge-case bug? Follow our diagnostic checklist below.
          </p>
        </div>
      </section>

      {/* Support Channels Grid */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-8">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          
          <div className="card-dark-glass p-8 space-y-4 border border-[#222222]">
            <div className="w-10 h-10 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D]">
              <Bug className="w-5 h-5" />
            </div>
            <h2 className="text-lg font-bold text-white tracking-tight">GitHub Issue Tracker</h2>
            <p className="text-xs text-[#8E8E8E] leading-relaxed">
              Report reproducible software bugs, feature requests, and compatibility observations on our public GitHub repository.
            </p>
            <div className="pt-2">
              <a
                href="https://github.com/AmalSKumar0/BotFence/issues"
                target="_blank"
                rel="noopener noreferrer"
                className="text-xs text-white font-semibold hover:text-[#FF4D6D] transition-colors inline-flex items-center gap-1.5"
              >
                <span>Open Issue on GitHub</span>
                <ArrowUpRight className="w-3.5 h-3.5" />
              </a>
            </div>
          </div>

          <div className="card-dark-glass p-8 space-y-4 border border-[#222222]">
            <div className="w-10 h-10 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D]">
              <LifeBuoy className="w-5 h-5" />
            </div>
            <h2 className="text-lg font-bold text-white tracking-tight">WordPress.org Community</h2>
            <p className="text-xs text-[#8E8E8E] leading-relaxed">
              Engage with other WordPress site owners and administrators on the official WordPress.org plugin support forum.
            </p>
            <div className="pt-2">
              <a
                href="https://wordpress.org/support/"
                target="_blank"
                rel="noopener noreferrer"
                className="text-xs text-white font-semibold hover:text-[#FF4D6D] transition-colors inline-flex items-center gap-1.5"
              >
                <span>WordPress.org Forum</span>
                <ArrowUpRight className="w-3.5 h-3.5" />
              </a>
            </div>
          </div>

          <div className="card-dark-glass p-8 space-y-4 border border-[#222222]">
            <div className="w-10 h-10 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D]">
              <ShieldAlert className="w-5 h-5" />
            </div>
            <h2 className="text-lg font-bold text-white tracking-tight">Security Vulnerability Disclosure</h2>
            <p className="text-xs text-[#8E8E8E] leading-relaxed">
              If you identify a security issue, please contact lead developer Amal S Kumar privately before public disclosure.
            </p>
            <div className="pt-2">
              <a
                href="https://amalskumar.dev"
                target="_blank"
                rel="noopener noreferrer"
                className="text-xs text-white font-semibold hover:text-[#FF4D6D] transition-colors inline-flex items-center gap-1.5"
              >
                <span>Contact Lead Developer</span>
                <ArrowUpRight className="w-3.5 h-3.5" />
              </a>
            </div>
          </div>

        </div>
      </section>

      {/* Troubleshooting Checklist */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-12">
        <div className="card-dark-glass p-8 sm:p-12 space-y-6 border border-[#2B2B2B]">
          <h2 className="text-2xl font-bold text-white tracking-tight">
            Self-Service Troubleshooting Checklist
          </h2>
          <div className="space-y-4 text-xs sm:text-sm text-neutral-300">
            <div className="p-4 rounded-xl bg-[#141414] border border-[#222222] space-y-1">
              <div className="font-bold text-white">1. Are legitimate users being blocked by "Session Expired"?</div>
              <p className="text-[#8E8E8E] leading-relaxed">
                If your site uses static page caching (WP Rocket, LiteSpeed), ensure <code className="text-white font-mono">vighnaghna.min.js</code> is not excluded from loading. On page load, this script automatically reaches out to <code className="text-white font-mono">/wp-json/vighnaghna/v1/token</code> to refresh expired cached tokens.
              </p>
            </div>

            <div className="p-4 rounded-xl bg-[#141414] border border-[#222222] space-y-1">
              <div className="font-bold text-white">2. Are submissions flagged as "Fast Submission"?</div>
              <p className="text-[#8E8E8E] leading-relaxed">
                If users frequently submit concise 1-field newsletter signups, lower <code className="text-white font-mono">Minimum Submission Time</code> from 2.5s to 1.0s in <strong>Vighnaghna &rarr; General Settings</strong>.
              </p>
            </div>

            <div className="p-4 rounded-xl bg-[#141414] border border-[#222222] space-y-1">
              <div className="font-bold text-white">3. Are all visitors logging the exact same Cloudflare IP?</div>
              <p className="text-[#8E8E8E] leading-relaxed">
                Navigate to <strong>Vighnaghna &rarr; Rate Limiting</strong> and toggle <code className="text-white font-mono">Trust Reverse Proxies</code> to Enabled. Vighnaghna will inspect <code className="text-white font-mono">HTTP_CF_CONNECTING_IP</code> and restore true visitor IP rate limiting.
              </p>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

import React from 'react';
import { GitCommit, Tag, Calendar, CheckCircle2 } from 'lucide-react';
import { SEO } from '../components/SEO';

interface PageProps {
  onOpenDownload: () => void;
  onNavigate: (path: string) => void;
}

export const ChangelogPage: React.FC<PageProps> = ({ onOpenDownload, onNavigate }) => {
  const structuredData = {
    '@context': 'https://schema.org',
    '@type': 'WebPage',
    'name': 'Vighnaghna Release Changelog & Version History',
    'description': 'Official release notes, technical improvements, and version history for the Vighnaghna WordPress anti-spam plugin.',
    'url': 'https://vighnaghna.amalskumar.dev/changelog',
    'breadcrumb': {
      '@type': 'BreadcrumbList',
      'itemListElement': [
        { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://vighnaghna.amalskumar.dev/' },
        { '@type': 'ListItem', 'position': 2, 'name': 'Changelog', 'item': 'https://vighnaghna.amalskumar.dev/changelog' }
      ]
    }
  };

  const releases = [
    {
      version: '1.0.1',
      date: 'November 15, 2026',
      tag: 'Latest Stable Release',
      summary: 'Brand identity refresh, custom dynamic SVG sidebar icon, and admin notice layout stabilization.',
      changes: [
        'Added custom SVG emblem for the WordPress Admin sidebar menu using an inline Base64 data URI compatible with svg-painter.js.',
        'Generated and bundled official vector repository icon (assets/icon.svg) and high-resolution icons (128x128, 256x256).',
        'Resolved admin notices layout bug by anchoring notices beneath the dashboard header card with semantic wp-header-end.',
        'Added defensive DOM-ready notice relocation in admin JavaScript to safeguard the header card layout.',
        'Updated website and documentation branding with crisp vector emblems and multi-resolution adaptive favicons.'
      ]
    },
    {
      version: '1.0.0',
      date: 'November 14, 2026',
      tag: 'Initial Release',
      summary: 'Production launch and complete compliance audit for WordPress.org Plugin Directory submission.',
      changes: [
        'Complete rebrand to Vighnaghna (विघ्नघ्न), text domain "vighnaghna", PHP prefix "vighnaghna_", namespace "Vighnaghna".',
        'Implemented Database::migrate_from_legacy() with explicit return result, failure capture, and strict flag setting.',
        'Preserved all legacy database tables (wp_botfence_logs, wp_botfence_rate_limits) with full idempotence.',
        'Standardized expired-token default configuration to 80 across activation, admin settings, and risk scoring.',
        'Separated expired tokens from malicious tampering with friendly retry prompt: "Your form session has expired. Please refresh the page and try again."',
        'Implemented safe IP comparison policy: authentic HMAC tokens are never rejected purely due to mobile CGNAT cell-tower changes.',
        'Exposed configurable risk weights for headless browsers (navigator.webdriver) and synthetic submit events (isTrusted === false).',
        'Certified 100% compatibility with WordPress 6.7 and PHP 8.1+ (101 unit and regression tests passing with 581 assertions).'
      ]
    },
    {
      version: '0.9.5',
      date: 'November 8, 2026',
      tag: 'Pre-Release Milestone',
      summary: 'Modularized form detector architecture and high-performance in-memory verification engine.',
      changes: [
        'Decoupled form builder adapters into standalone modular classes implementing Form_Adapter_Interface.',
        'Added native integration adapters for Contact Form 7, WPForms, Fluent Forms, and Gravity Forms.',
        'Introduced single-use cryptographic token manager with atomic MySQL insertion.',
        'Implemented client telemetry analyzer for mouse micro-jitter, keystroke timings, and paste events.',
        'Added executive security dashboard with real-time spam counter and threat simulator.'
      ]
    }
  ];

  return (
    <div className="w-full bg-[#0A0A0A] text-white font-sans text-left">
      <SEO
        title="Changelog & Version History — Vighnaghna"
        description="Official release notes, technical improvements, and architectural milestones for the Vighnaghna WordPress anti-spam plugin."
        canonicalUrl="https://vighnaghna.amalskumar.dev/changelog"
        structuredData={structuredData}
      />

      {/* Header */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 pt-20 pb-12">
        <div className="space-y-4 max-w-3xl">
          <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#FF4D6D] inline-block">
            Version History
          </span>
          <h1 className="text-4xl sm:text-5xl font-[850] tracking-tight text-white leading-[1.1]">
            Release Changelog
          </h1>
          <p className="text-sm sm:text-base text-[#8E8E8E] leading-relaxed">
            Detailed log of releases, architectural enhancements, security hardening, and compliance updates for Vighnaghna.
          </p>
        </div>
      </section>

      {/* Timeline */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-8">
        <div className="space-y-12 max-w-4xl">
          {releases.map((rel, idx) => (
            <div
              key={rel.version}
              className="card-dark-glass p-8 sm:p-10 space-y-6 border border-[#222222]"
            >
              <div className="flex flex-wrap items-center justify-between gap-4 border-b border-[#222222] pb-6">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D]">
                    <Tag className="w-5 h-5" />
                  </div>
                  <div>
                    <h2 className="text-2xl font-[850] text-white tracking-tight">
                      Version {rel.version}
                    </h2>
                    <div className="flex items-center gap-2 text-xs text-[#8E8E8E] pt-0.5">
                      <Calendar className="w-3.5 h-3.5" />
                      <span>{rel.date}</span>
                    </div>
                  </div>
                </div>

                <span className="px-3 py-1 rounded-full bg-[#1C1C1C] border border-[#2B2B2B] text-xs font-mono font-medium text-[#FF4D6D]">
                  {rel.tag}
                </span>
              </div>

              <p className="text-xs sm:text-sm text-neutral-300 font-medium">
                {rel.summary}
              </p>

              <div className="space-y-2.5 pt-2">
                {rel.changes.map((item, i) => (
                  <div key={i} className="flex items-start gap-2.5 text-xs text-[#8E8E8E] leading-relaxed">
                    <CheckCircle2 className="w-4 h-4 text-green-400 shrink-0 mt-0.5" />
                    <span>{item}</span>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
};

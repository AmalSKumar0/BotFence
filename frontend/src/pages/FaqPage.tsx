import React, { useState } from 'react';
import { ChevronDown, HelpCircle, ArrowUpRight } from 'lucide-react';
import { SEO } from '../components/SEO';

interface PageProps {
  onOpenDownload: () => void;
  onNavigate: (path: string) => void;
}

export const FaqPage: React.FC<PageProps> = ({ onOpenDownload, onNavigate }) => {
  const [openIdx, setOpenIdx] = useState<number | null>(0);

  const faqs = [
    {
      q: 'Does Vighnaghna require API keys from Google or Cloudflare?',
      a: 'No. Vighnaghna operates 100% self-hosted on your WordPress server using local cryptographic tokens, polymorphic honeypots, and client behavioral heuristics. No third-party accounts, API credentials, or external cloud subscriptions are ever required.'
    },
    {
      q: 'Will legitimate visitors ever see visual puzzles, audio challenges, or CAPTCHA widgets?',
      a: 'Never. Vighnaghna is completely invisible to human visitors. Legitimate users fill and submit forms naturally without selecting fire hydrants or deciphering distorted characters, preserving optimal form conversion rates.'
    },
    {
      q: 'Which WordPress form builders are supported out of the box?',
      a: 'Vighnaghna includes automatic, zero-configuration adapters for Contact Form 7, WPForms, Fluent Forms, Gravity Forms, Formidable Forms, Ninja Forms, Elementor Pro Forms, WooCommerce checkout and customer registration, and native WordPress comments.'
    },
    {
      q: 'How does Vighnaghna handle static page caching (WP Rocket, LiteSpeed, Cloudflare)?',
      a: 'Static caching plugins serve pre-rendered HTML containing expired timestamps. Vighnaghna solves this cleanly: on DOMContentLoaded, its lightweight client script automatically contacts the WordPress REST API (/wp-json/vighnaghna/v1/token) to obtain a fresh, valid cryptographic token before form submission.'
    },
    {
      q: 'What happens when a user leaves a form open in an idle tab for hours?',
      a: 'If a token exceeds its lifetime window (default: 600s), Vighnaghna treats the event as a session timeout rather than a malicious bot attack. The visitor receives a friendly, non-accusatory prompt: "Your form session has expired. Please refresh the page and try again." Upon refreshing, a fresh token is loaded and submission succeeds.'
    },
    {
      q: 'How does Vighnaghna prevent false positives when mobile users change cell towers or IP addresses?',
      a: 'On cellular CGNAT networks, mobile carriers frequently rotate IP addresses between page load and form submission. Vighnaghna implements a safe IP comparison policy: while IP mismatches are recorded for diagnostic telemetry, an authentic HMAC-SHA256 token is never rejected purely due to an IP switch.'
    },
    {
      q: 'Does Vighnaghna work with WooCommerce checkout, login, and registration?',
      a: 'Yes. Vighnaghna hooks natively into woocommerce_checkout_process, woocommerce_register_post, and wp_authenticate. It stops card testing bots and credential stuffing without causing checkout abandonment.'
    },
    {
      q: 'How does Vighnaghna ensure accessibility for screen readers and WCAG compliance?',
      a: 'Decoy honeypot inputs use standard W3C accessibility attributes: aria-hidden="true", tabindex="-1", and off-screen CSS positioning. Screen readers and assistive tools automatically ignore these fields, ensuring blind or visually impaired users are never penalized.'
    },
    {
      q: 'Can developers protect custom HTML forms or bespoke PHP endpoints?',
      a: 'Yes. Developers can protect any form using two simple WordPress hooks: do_action("vighnaghna_render_fields", "my_form_id") to output hidden tokens, and apply_filters("vighnaghna_verify_submission", array(), $_POST, "my_form_id") to evaluate the submission.'
    },
    {
      q: 'How does Vighnaghna detect advanced headless scrapers (Puppeteer, Playwright, Selenium)?',
      a: 'Client telemetry inspects browser execution contexts for automation flags (e.g. navigator.webdriver), synthetic submit dispatches where event.isTrusted is false, and unnatural sub-second completion times with zero mouse jitter or keystrokes.'
    },
    {
      q: 'What server resources and database overhead does Vighnaghna require?',
      a: 'Vighnaghna is engineered for minimal footprint. Server-side verification runs in-memory in ~1.8ms on PHP 8.1+. Custom database tables use efficient indexes, and an automated hourly cron purges aged audit logs to keep your database lean.'
    },
    {
      q: 'Is Vighnaghna compliant with European GDPR and California CCPA privacy regulations?',
      a: 'Yes, 100%. Vighnaghna sets zero tracking cookies, builds no cross-site behavioral profiles, hashes IP addresses using SHA-256 salts, and sends zero data to external third parties.'
    },
    {
      q: 'What is the software license and pricing for Vighnaghna?',
      a: 'Vighnaghna is 100% free and open-source software licensed under GNU General Public License v2.0 or later (GPL-2.0+). You are free to use, modify, and deploy it across personal and commercial WordPress websites without fee.'
    }
  ];

  const structuredData = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    'mainEntity': faqs.map((f) => ({
      '@type': 'Question',
      'name': f.q,
      'acceptedAnswer': {
        '@type': 'Answer',
        'text': f.a
      }
    }))
  };

  return (
    <div className="w-full bg-[#0A0A0A] text-white font-sans text-left">
      <SEO
        title="Frequently Asked Questions (FAQ) — Vighnaghna WordPress Anti-Spam"
        description="Comprehensive technical answers about Vighnaghna: zero-CAPTCHA verification, page caching compatibility, mobile IP safety, WCAG accessibility, and GDPR."
        canonicalUrl="https://vighnaghna.amalskumar.dev/faq"
        structuredData={structuredData}
      />

      {/* Header */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 pt-20 pb-12">
        <div className="space-y-4 max-w-3xl">
          <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#FF4D6D] inline-block">
            Knowledge Base
          </span>
          <h1 className="text-4xl sm:text-5xl font-[850] tracking-tight text-white leading-[1.1]">
            Frequently Asked Questions
          </h1>
          <p className="text-sm sm:text-base text-[#8E8E8E] leading-relaxed">
            Detailed answers regarding Vighnaghna's zero-CAPTCHA verification, compatibility with caching plugins, mobile network behavior, and privacy architecture.
          </p>
        </div>
      </section>

      {/* FAQ Accordion List */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-8">
        <div className="space-y-4 max-w-4xl">
          {faqs.map((faq, idx) => {
            const isOpen = openIdx === idx;
            return (
              <div
                key={idx}
                className="card-dark-glass overflow-hidden border border-[#222222] transition-colors"
              >
                <button
                  type="button"
                  onClick={() => setOpenIdx(isOpen ? null : idx)}
                  className="w-full p-6 sm:p-7 flex items-center justify-between gap-4 text-left cursor-pointer focus:outline-none"
                  aria-expanded={isOpen}
                >
                  <span className="text-base sm:text-lg font-bold text-white tracking-tight">
                    {faq.q}
                  </span>
                  <div className={`w-8 h-8 rounded-full bg-[#1C1C1C] border border-[#2B2B2B] flex items-center justify-center shrink-0 text-[#8E8E8E] transition-transform duration-200 ${isOpen ? 'rotate-180 text-white' : ''}`}>
                    <ChevronDown className="w-4 h-4" />
                  </div>
                </button>

                {isOpen && (
                  <div className="px-6 sm:px-7 pb-7 pt-1 text-xs sm:text-sm text-[#8E8E8E] leading-relaxed border-t border-[#1C1C1C]">
                    <p>{faq.a}</p>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </section>

      {/* Still Have Questions CTA */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-16">
        <div className="card-dark-glass p-8 sm:p-12 text-center space-y-4 max-w-4xl">
          <h2 className="text-xl sm:text-2xl font-bold text-white">
            Have a Specific Integration or Architecture Question?
          </h2>
          <p className="text-xs sm:text-sm text-[#8E8E8E] max-w-md mx-auto leading-relaxed">
            Explore our developer documentation or connect with author Amal S Kumar on GitHub.
          </p>
          <div className="pt-2 flex flex-wrap items-center justify-center gap-4">
            <button
              type="button"
              onClick={() => onNavigate('/docs')}
              className="px-5 py-2.5 rounded-full bg-white text-black font-semibold text-xs tracking-wider hover:bg-neutral-200 transition-all cursor-pointer"
            >
              Developer Docs
            </button>
            <button
              type="button"
              onClick={() => onNavigate('/support')}
              className="px-5 py-2.5 rounded-full border border-[#2E2E2E] bg-[#141414] text-white font-medium text-xs tracking-wider hover:border-white transition-all cursor-pointer"
            >
              Support Channel
            </button>
          </div>
        </div>
      </section>
    </div>
  );
};

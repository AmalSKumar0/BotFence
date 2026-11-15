import React from 'react';
import { ShieldCheck, CheckCircle2, AlertTriangle, FileCode2, ArrowRight, Zap, RefreshCw } from 'lucide-react';
import { SEO } from '../components/SEO';

interface PageProps {
  onOpenDownload: () => void;
  onNavigate: (path: string) => void;
}

export const ContactForm7Page: React.FC<PageProps> = ({ onOpenDownload, onNavigate }) => {
  const structuredData = {
    '@context': 'https://schema.org',
    '@type': 'TechArticle',
    'headline': 'Contact Form 7 Spam Protection Without CAPTCHA — Vighnaghna Integration Guide',
    'description': 'The definitive technical guide to stopping Contact Form 7 spam in WordPress without puzzles, eliminating mail abort errors, and supporting cached pages.',
    'url': 'https://vighnaghna.amalskumar.dev/integrations/contact-form-7',
    'author': {
      '@type': 'Person',
      'name': 'Amal S Kumar',
      'url': 'https://amalskumar.dev'
    },
    'breadcrumb': {
      '@type': 'BreadcrumbList',
      'itemListElement': [
        { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://vighnaghna.amalskumar.dev/' },
        { '@type': 'ListItem', 'position': 2, 'name': 'Integrations', 'item': 'https://vighnaghna.amalskumar.dev/features' },
        { '@type': 'ListItem', 'position': 3, 'name': 'Contact Form 7', 'item': 'https://vighnaghna.amalskumar.dev/integrations/contact-form-7' }
      ]
    }
  };

  return (
    <div className="w-full bg-[#0A0A0A] text-white font-sans text-left">
      <SEO
        title="Contact Form 7 Spam Protection Without CAPTCHA — Vighnaghna"
        description="Stop Contact Form 7 spam bots without visual puzzles or broken forms. Eliminate 'Sending mail has been aborted' errors with native wpcf7_skip_mail integration."
        canonicalUrl="https://vighnaghna.amalskumar.dev/integrations/contact-form-7"
        structuredData={structuredData}
      />

      {/* Header */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 pt-20 pb-12">
        <div className="space-y-4 max-w-3xl">
          <div className="flex items-center gap-2 text-xs font-mono text-[#8E8E8E] uppercase tracking-wider">
            <a href="/features" onClick={(e) => { e.preventDefault(); onNavigate('/features'); }} className="hover:text-white transition-colors">
              Integrations
            </a>
            <span>/</span>
            <span className="text-[#FF4D6D]">Contact Form 7</span>
          </div>
          <h1 className="text-4xl sm:text-5xl font-[850] tracking-tight text-white leading-[1.1]">
            Contact Form 7 Spam Protection Without CAPTCHA
          </h1>
          <p className="text-sm sm:text-base text-[#8E8E8E] leading-relaxed">
            Contact Form 7 powers over 5 million WordPress websites. Stop spam floods, botnet submissions, and broken email notifications without forcing human visitors to solve crosswalk puzzles.
          </p>
        </div>
      </section>

      {/* The Problem with CF7 & reCAPTCHA */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-6">
        <div className="card-dark-glass p-8 sm:p-10 border border-[#2B2B2B] bg-[#121212] space-y-6">
          <h2 className="text-xl sm:text-2xl font-bold text-white flex items-center gap-2.5">
            <AlertTriangle className="w-5 h-5 text-[#FF4D6D]" />
            <span>Why Conventional CF7 Anti-Spam Fails</span>
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs text-neutral-300">
            <div className="space-y-2 bg-[#1A1A1A] p-5 rounded-xl border border-[#2A2A2A]">
              <div className="text-white font-bold text-sm">The "Mail Aborted" Error</div>
              <p className="text-[#8E8E8E] leading-relaxed">
                Many anti-spam plugins hook into <code className="text-neutral-300">wpcf7_before_send_mail</code> and set <code className="text-red-400">$abort = true</code>. This triggers Contact Form 7's dreaded orange border message: <em>"There was an error trying to send your message. Sending mail has been aborted."</em>
              </p>
            </div>
            <div className="space-y-2 bg-[#1A1A1A] p-5 rounded-xl border border-[#2A2A2A]">
              <div className="text-white font-bold text-sm">reCAPTCHA v3 False Positives</div>
              <p className="text-[#8E8E8E] leading-relaxed">
                Google reCAPTCHA v3 scores visitors based on browsing history. Users with privacy extensions, ad blockers, or anonymous browsers receive low trust scores and are silently blocked without recourse.
              </p>
            </div>
            <div className="space-y-2 bg-[#1A1A1A] p-5 rounded-xl border border-[#2A2A2A]">
              <div className="text-white font-bold text-sm">Page Caching Incompatibilities</div>
              <p className="text-[#8E8E8E] leading-relaxed">
                Static HTML caching plugins (WP Rocket, LiteSpeed, Cloudflare) cache embedded security nonces. When visitors submit forms hours later, expired nonces cause legitimate submissions to fail.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* How Vighnaghna Fixes CF7 */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-12 space-y-8">
        <div className="space-y-3">
          <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#8E8E8E]">
            Native WordPress Architecture
          </span>
          <h2 className="text-2xl sm:text-3xl font-[850] tracking-tight text-white">
            How Vighnaghna Secures Contact Form 7
          </h2>
          <p className="text-xs sm:text-sm text-[#8E8E8E] max-w-2xl leading-relaxed">
            Vighnaghna integrates cleanly with Contact Form 7's official filter and action pipeline.
          </p>
        </div>

        <div className="space-y-6">
          {/* Step 1 */}
          <div className="card-dark-glass p-6 sm:p-8 space-y-3">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 rounded-lg bg-[#1F1F1F] border border-[#2E2E2E] flex items-center justify-center font-mono font-bold text-xs text-[#FF4D6D]">
                01
              </div>
              <h3 className="text-lg font-bold text-white">
                Invisible Decoy &amp; Token Injection (<code className="text-sm font-mono text-[#FF4D6D]">wpcf7_form_elements</code>)
              </h3>
            </div>
            <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed pl-11">
              Vighnaghna automatically injects single-use HMAC security tokens and polymorphic honeypot fields into the form HTML. No manual shortcodes or form tags (<code className="text-white font-mono">[honeypot]</code>) are needed. Decoy inputs are hidden with off-screen CSS and accessibility attributes (<code className="text-white font-mono">aria-hidden="true"</code>, <code className="text-white font-mono">tabindex="-1"</code>).
            </p>
          </div>

          {/* Step 2 */}
          <div className="card-dark-glass p-6 sm:p-8 space-y-3">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 rounded-lg bg-[#1F1F1F] border border-[#2E2E2E] flex items-center justify-center font-mono font-bold text-xs text-[#FF4D6D]">
                02
              </div>
              <h3 className="text-lg font-bold text-white">
                Spam Evaluation via Native Filter (<code className="text-sm font-mono text-[#FF4D6D]">wpcf7_spam</code>)
              </h3>
            </div>
            <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed pl-11">
              When a visitor submits the form, Vighnaghna intercepts the submission at Priority 9 before default WordPress checks. If the risk engine scores the submission as spam, it returns <code className="text-white font-mono">true</code> to CF7's native spam mechanism, displaying a clean spam prompt without crashing the pipeline.
            </p>
          </div>

          {/* Step 3 */}
          <div className="card-dark-glass p-6 sm:p-8 space-y-3">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 rounded-lg bg-[#1F1F1F] border border-[#2E2E2E] flex items-center justify-center font-mono font-bold text-xs text-[#FF4D6D]">
                03
              </div>
              <h3 className="text-lg font-bold text-white">
                Clean Mail Suppression (<code className="text-sm font-mono text-[#FF4D6D]">wpcf7_skip_mail</code>)
              </h3>
            </div>
            <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed pl-11">
              Crucially, Vighnaghna suppresses email transmission via <code className="text-white font-mono">add_filter('wpcf7_skip_mail', ...)</code> rather than setting <code className="text-white font-mono">$abort = true</code>. This prevents CF7 from generating internal abort errors and keeps server mail queues completely clean.
            </p>
          </div>

          {/* Step 4 */}
          <div className="card-dark-glass p-6 sm:p-8 space-y-3">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 rounded-lg bg-[#1F1F1F] border border-[#2E2E2E] flex items-center justify-center font-mono font-bold text-xs text-[#FF4D6D]">
                04
              </div>
              <h3 className="text-lg font-bold text-white">
                Dynamic Cached Page Token Refresh via REST API
              </h3>
            </div>
            <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed pl-11">
              If your site uses WP Rocket, LiteSpeed Cache, or Cloudflare HTML caching, Vighnaghna's zero-dependency client script detects cached pages on DOM ready and automatically fetches a fresh cryptographic token from <code className="text-white font-mono">/wp-json/vighnaghna/v1/token</code>. Legitimate visitors on cached pages never experience false-positive token expiration blocks.
            </p>
          </div>
        </div>
      </section>

      {/* Setup Instructions */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-12">
        <div className="card-dark-glass p-8 sm:p-10 space-y-6 border border-[#2B2B2B]">
          <h2 className="text-xl sm:text-2xl font-bold text-white">
            Activating Protection for Contact Form 7
          </h2>
          <div className="space-y-3 text-xs sm:text-sm text-neutral-300 leading-relaxed">
            <div className="flex items-start gap-2.5">
              <CheckCircle2 className="w-4 h-4 text-green-400 shrink-0 mt-0.5" />
              <span>Install and activate Vighnaghna on your WordPress website.</span>
            </div>
            <div className="flex items-start gap-2.5">
              <CheckCircle2 className="w-4 h-4 text-green-400 shrink-0 mt-0.5" />
              <span>Navigate to <strong>Vighnaghna &rarr; Integrations</strong> in your admin dashboard.</span>
            </div>
            <div className="flex items-start gap-2.5">
              <CheckCircle2 className="w-4 h-4 text-green-400 shrink-0 mt-0.5" />
              <span>Confirm that <strong>Contact Form 7</strong> is toggled to Enabled (active by default).</span>
            </div>
            <div className="flex items-start gap-2.5">
              <CheckCircle2 className="w-4 h-4 text-green-400 shrink-0 mt-0.5" />
              <span>That's it! Every Contact Form 7 instance on your site is now automatically protected. Zero form edits required.</span>
            </div>
          </div>
        </div>
      </section>

      {/* Bottom CTA */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-16">
        <div className="card-dark-glass p-8 sm:p-12 text-center space-y-6">
          <h2 className="text-2xl sm:text-3xl font-[850] tracking-tight text-white">
            Stop CF7 Form Spam Right Now
          </h2>
          <p className="text-xs sm:text-sm text-[#8E8E8E] max-w-lg mx-auto leading-relaxed">
            Download the free, open-source Vighnaghna plugin and protect your contact forms in under 60 seconds.
          </p>
          <div className="pt-2 flex flex-wrap items-center justify-center gap-4">
            <button
              type="button"
              onClick={onOpenDownload}
              className="px-6 py-3 rounded-full bg-white text-black font-bold text-xs uppercase tracking-wider hover:bg-neutral-200 transition-all cursor-pointer"
            >
              Download Vighnaghna (.zip)
            </button>
            <button
              type="button"
              onClick={() => onNavigate('/faq')}
              className="px-6 py-3 rounded-full border border-[#2E2E2E] bg-[#141414] text-white font-medium text-xs tracking-wider hover:border-white transition-all cursor-pointer"
            >
              Frequently Asked Questions
            </button>
          </div>
        </div>
      </section>
    </div>
  );
};

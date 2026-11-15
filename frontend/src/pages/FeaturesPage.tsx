import React from 'react';
import { 
  Zap, 
  Lock, 
  Layers, 
  Cpu, 
  Database, 
  ShieldCheck, 
  Check, 
  X, 
  ArrowUpRight, 
  Code2, 
  EyeOff, 
  Server, 
  Activity 
} from 'lucide-react';
import { SEO } from '../components/SEO';

interface PageProps {
  onOpenDownload: () => void;
  onNavigate: (path: string) => void;
}

export const FeaturesPage: React.FC<PageProps> = ({ onOpenDownload, onNavigate }) => {
  const structuredData = {
    '@context': 'https://schema.org',
    '@type': 'WebPage',
    'name': 'Vighnaghna Features — Six Layers of Invisible WordPress Form Spam Protection',
    'description': 'Explore the technical architecture of Vighnaghna: polymorphic honeypots, cryptographic HMAC tokens, sub-2ms in-memory scoring, and zero-SaaS privacy.',
    'url': 'https://vighnaghna.amalskumar.dev/features',
    'breadcrumb': {
      '@type': 'BreadcrumbList',
      'itemListElement': [
        {
          '@type': 'ListItem',
          'position': 1,
          'name': 'Home',
          'item': 'https://vighnaghna.amalskumar.dev/'
        },
        {
          '@type': 'ListItem',
          'position': 2,
          'name': 'Features',
          'item': 'https://vighnaghna.amalskumar.dev/features'
        }
      ]
    }
  };

  const layers = [
    {
      num: '01',
      icon: EyeOff,
      title: 'Polymorphic Honeypot Traps',
      subtitle: 'Dynamic decoy fields invisible to humans and screen readers',
      desc: 'Spam bots blindly parse HTML and inject data into every input field they encounter. Vighnaghna creates randomly generated decoy input fields that are hidden from human eyes using CSS and ARIA accessibility markup (aria-hidden="true", tabindex="-1"). Human visitors never see or interact with them, while automated scrapers populate them and are blocked immediately.',
      techPoints: [
        'Decoy input names change dynamically to prevent bot hardcoding',
        'Strict accessibility compliance ensures screen readers safely skip honeypots',
        'Instant 100-point penalty on fill drops malicious requests on arrival'
      ]
    },
    {
      num: '02',
      icon: Lock,
      title: 'HMAC-SHA256 Cryptographic Tokens',
      subtitle: 'Single-use cryptographic nonces with mobile IP tolerance',
      desc: 'Every protected form is issued an ephemeral security token signed with HMAC-SHA256 using a site-specific cryptographically secure key. The token payload encodes issuance timestamps, form identifiers, and network fingerprints. Replayed tokens and cross-form substitutions are rejected.',
      techPoints: [
        'Single-use atomic consumption prevents automated replay attacks',
        'Configurable lifetime window (default: 600s) with 60-second future drift tolerance',
        'Safe proxy and mobile IP policy: Mobile users switching cell towers are never falsely blocked'
      ]
    },
    {
      num: '03',
      icon: Activity,
      title: 'Micro-Behavioral Client Telemetry',
      subtitle: 'Passive interaction heuristics without intrusive tracking cookies',
      desc: 'Vighnaghna collects passive, zero-PII client metrics as visitors interact with the page: mouse movement coordinates, keystroke cadence, focus shifts, and touch events. Forms submitted in under 2.5 seconds with zero interaction signals are flagged as automated.',
      techPoints: [
        'Detects synthetic programmatic submit events where event.isTrusted is false',
        'Recognizes legitimate browser autofill and clipboard paste actions to eliminate false positives',
        'Zero tracking cookies or cross-site fingerprinting; GDPR Article 5 compliant'
      ]
    },
    {
      num: '04',
      icon: Server,
      title: 'Headless Browser & WebDriver Detection',
      subtitle: 'Neutralizing Puppeteer, Playwright, and Selenium scraper farms',
      desc: 'Advanced botnets use automated Chromium instances to bypass basic honeypots. Vighnaghna probes client execution environments for navigator.webdriver flags, synthetic browser automation prototypes, and unnatural execution timings.',
      techPoints: [
        'Configurable risk penalty (default: 50 points) triggers verification challenge',
        'Detects headless execution fingerprints and phantom browser environments',
        'Tunable via WordPress admin dashboard under Risk & Security Settings'
      ]
    },
    {
      num: '05',
      icon: Cpu,
      title: 'Sub-2ms In-Memory Verification',
      subtitle: 'Server-side evaluation before database query execution',
      desc: 'High-volume spam floods can degrade WordPress database performance. Vighnaghna runs its entire verification pipeline in-memory in under 2 milliseconds on standard PHP hosting, discarding spam submissions before WordPress executes database inserts into wp_posts or wp_comments.',
      techPoints: [
        'Executes in ~1.8ms on typical PHP 8.1+ hosting configurations',
        'Drastically reduces database query load during distributed spam floods',
        'Zero external API network latency: no Google or Cloudflare roundtrips'
      ]
    },
    {
      num: '06',
      icon: ShieldCheck,
      title: 'Universal Form Builder Integration',
      subtitle: 'Native hooks into Contact Form 7, WPForms, Gravity Forms, and more',
      desc: 'Rather than requiring manual shortcodes or form rebuilds, Vighnaghna integrates with your existing form plugins. It hooks into native filter pipelines (e.g., wpcf7_spam, wpforms_process) and gracefully skips mail delivery without triggering confusing abort errors.',
      techPoints: [
        'Out-of-the-box support for Contact Form 7, WPForms, Fluent Forms, Gravity Forms, and WooCommerce',
        'Eliminates Contact Form 7 "Sending mail has been aborted" errors via wpcf7_skip_mail',
        'Custom form developers can protect any HTML form with two simple WordPress hooks'
      ]
    }
  ];

  return (
    <div className="w-full bg-[#0A0A0A] text-white font-sans">
      <SEO
        title="Vighnaghna Features — Six Layers of Invisible WordPress Form Spam Protection"
        description="Comprehensive technical overview of Vighnaghna's zero-CAPTCHA architecture: polymorphic honeypots, HMAC-SHA256 tokens, headless bot detection, and sub-2ms in-memory verification."
        canonicalUrl="https://vighnaghna.amalskumar.dev/features"
        structuredData={structuredData}
      />

      {/* Hero Section */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 pt-20 pb-16 text-left">
        <div className="space-y-4 max-w-3xl">
          <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#FF4D6D] inline-block">
            Architectural Deep Dive
          </span>
          <h1 className="text-4xl sm:text-6xl font-[850] tracking-tight text-white leading-[1.05]">
            Six Layers of Invisible Protection. Zero Third-Party SaaS.
          </h1>
          <p className="text-sm sm:text-base text-[#8E8E8E] leading-relaxed pt-2">
            Most WordPress anti-spam solutions outsource form verification to external advertising giants or subject legitimate human visitors to annoying visual puzzles. Vighnaghna runs 100% on your server using multi-layered heuristic signals.
          </p>
        </div>
      </section>

      {/* Six Technical Layers */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-12">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          {layers.map((layer) => {
            const Icon = layer.icon;
            return (
              <div
                key={layer.num}
                className="card-dark-glass p-8 sm:p-10 flex flex-col justify-between text-left space-y-6"
              >
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div className="w-10 h-10 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D]">
                      <Icon className="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <span className="font-mono text-xs text-neutral-500 font-bold tracking-widest">
                      LAYER {layer.num}
                    </span>
                  </div>

                  <div>
                    <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight">
                      {layer.title}
                    </h2>
                    <p className="text-xs text-[#FF4D6D] font-medium pt-1">
                      {layer.subtitle}
                    </p>
                  </div>

                  <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
                    {layer.desc}
                  </p>
                </div>

                <div className="pt-4 border-t border-[#1F1F1F] space-y-2.5">
                  {layer.techPoints.map((pt, i) => (
                    <div key={i} className="flex items-start gap-2.5 text-xs text-neutral-300">
                      <Check className="w-4 h-4 text-[#FF4D6D] shrink-0 mt-0.5" />
                      <span>{pt}</span>
                    </div>
                  ))}
                </div>
              </div>
            );
          })}
        </div>
      </section>

      {/* Comprehensive Comparison Matrix */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-20 text-left">
        <div className="space-y-4 pb-10">
          <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#8E8E8E]">
            Direct Comparison
          </span>
          <h2 className="text-3xl sm:text-4xl font-[850] tracking-tight text-white">
            How Vighnaghna Compares to Conventional Solutions
          </h2>
          <p className="text-xs sm:text-sm text-[#8E8E8E] max-w-2xl leading-relaxed">
            Compare architectural differences, privacy compliance, and visitor friction between Vighnaghna and mainstream alternatives.
          </p>
        </div>

        <div className="overflow-x-auto border border-[#222222] rounded-2xl bg-[#121212]">
          <table className="w-full text-left text-xs sm:text-sm border-collapse">
            <thead>
              <tr className="border-b border-[#222222] bg-[#161616] text-[#8E8E8E] text-[11px] uppercase tracking-wider">
                <th className="py-4 px-6 font-semibold">Evaluation Criteria</th>
                <th className="py-4 px-6 text-white font-bold bg-[#1C1C1C]">Vighnaghna</th>
                <th className="py-4 px-6 font-semibold">Google reCAPTCHA v2/v3</th>
                <th className="py-4 px-6 font-semibold">Cloudflare Turnstile</th>
                <th className="py-4 px-6 font-semibold">Akismet</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#1F1F1F] text-neutral-300">
              <tr>
                <td className="py-4 px-6 font-medium text-white">Visitor Friction / Puzzles</td>
                <td className="py-4 px-6 text-[#FF4D6D] font-bold bg-[#141414]">Zero (100% Invisible)</td>
                <td className="py-4 px-6 text-neutral-400">Audio/Image Challenges</td>
                <td className="py-4 px-6 text-neutral-400">Interactive Widget</td>
                <td className="py-4 px-6 text-neutral-400">Zero (Invisible)</td>
              </tr>
              <tr>
                <td className="py-4 px-6 font-medium text-white">External Network Requests</td>
                <td className="py-4 px-6 text-green-400 font-bold bg-[#141414]">0 (Fully Self-Hosted)</td>
                <td className="py-4 px-6 text-red-400">Calls Google Servers</td>
                <td className="py-4 px-6 text-red-400">Calls Cloudflare CDN</td>
                <td className="py-4 px-6 text-red-400">Sends Content to Automattic</td>
              </tr>
              <tr>
                <td className="py-4 px-6 font-medium text-white">GDPR & CCPA Native Compliance</td>
                <td className="py-4 px-6 text-green-400 font-bold bg-[#141414]">100% Compliant (No Cookies)</td>
                <td className="py-4 px-6 text-yellow-400">Requires Cookie Banner Consent</td>
                <td className="py-4 px-6 text-yellow-400">Third-Party Data Transfer</td>
                <td className="py-4 px-6 text-yellow-400">PII Transmission to USA</td>
              </tr>
              <tr>
                <td className="py-4 px-6 font-medium text-white">Server Verification Latency</td>
                <td className="py-4 px-6 text-green-400 font-bold bg-[#141414]">&lt; 2 milliseconds</td>
                <td className="py-4 px-6 text-neutral-400">180ms – 450ms (HTTP Roundtrip)</td>
                <td className="py-4 px-6 text-neutral-400">120ms – 300ms</td>
                <td className="py-4 px-6 text-neutral-400">200ms – 600ms</td>
              </tr>
              <tr>
                <td className="py-4 px-6 font-medium text-white">API Keys & Account Setup</td>
                <td className="py-4 px-6 text-green-400 font-bold bg-[#141414]">Zero Keys Required</td>
                <td className="py-4 px-6 text-red-400">Google Cloud Console Account</td>
                <td className="py-4 px-6 text-red-400">Cloudflare Dashboard Account</td>
                <td className="py-4 px-6 text-red-400">WordPress.com API Key</td>
              </tr>
              <tr>
                <td className="py-4 px-6 font-medium text-white">Software License</td>
                <td className="py-4 px-6 text-white font-bold bg-[#141414]">GPLv2+ Open Source</td>
                <td className="py-4 px-6 text-neutral-400">Proprietary Closed Source</td>
                <td className="py-4 px-6 text-neutral-400">Proprietary Closed Source</td>
                <td className="py-4 px-6 text-neutral-400">Freemium Commercial SaaS</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      {/* Conversion Banner */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-16">
        <div className="card-dark-glass p-10 sm:p-14 text-center space-y-6 border border-[#2E2E2E]">
          <h2 className="text-2xl sm:text-4xl font-[850] tracking-tight text-white">
            Ready to Protect Your WordPress Forms Without Puzzles?
          </h2>
          <p className="text-xs sm:text-sm text-[#8E8E8E] max-w-xl mx-auto leading-relaxed">
            Download the official Vighnaghna package and enjoy enterprise-grade form security in less than 60 seconds.
          </p>
          <div className="pt-2 flex flex-wrap items-center justify-center gap-4">
            <button
              type="button"
              onClick={onOpenDownload}
              className="px-6 py-3 rounded-full bg-white text-black font-bold text-xs uppercase tracking-wider hover:bg-neutral-200 transition-all cursor-pointer flex items-center gap-2"
            >
              <span>Download Free Plugin (.zip)</span>
              <ArrowUpRight className="w-4 h-4" />
            </button>
            <button
              type="button"
              onClick={() => onNavigate('/docs/installation')}
              className="px-6 py-3 rounded-full border border-[#2E2E2E] bg-[#141414] text-white font-medium text-xs tracking-wider hover:border-white transition-all cursor-pointer"
            >
              Read Installation Guide
            </button>
          </div>
        </div>
      </section>
    </div>
  );
};

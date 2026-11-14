import React, { useState } from 'react';
import { Download, ChevronDown, Check, ArrowRight, ShieldCheck } from 'lucide-react';

interface FaqAndInstallSectionProps {
  onOpenDownload: () => void;
}

const FAQS = [
  {
    q: 'Does Vighnaghna require API keys from Google or Cloudflare?',
    a: 'No. Vighnaghna operates independently on your server using local behavioral heuristics and polymorphic honeypots. No third-party accounts or API credentials are required.',
  },
  {
    q: 'Will legitimate visitors ever see puzzles or CAPTCHAs?',
    a: 'Never. Vighnaghna is entirely invisible to humans. Legitimate visitors submit forms seamlessly without audio puzzles or selecting crosswalk images, boosting conversion rates.',
  },
  {
    q: 'Which WordPress form builders are supported out of the box?',
    a: 'Vighnaghna automatically integrates with Contact Form 7, WPForms, Gravity Forms, Elementor Forms, Fluent Forms, Formidable Forms, WooCommerce checkout, and wp-login.',
  },
  {
    q: 'Does Vighnaghna impact website load times or speed?',
    a: 'No. The client-side asset is under 3.2KB and loads asynchronously. Server-side verification takes under 2 milliseconds on typical hosting, saving database resources by dropping spam early.',
  },
  {
    q: 'Is Vighnaghna compliant with GDPR, CCPA, and European privacy regulations?',
    a: 'Yes, 100%. Vighnaghna sets no third-party tracking cookies, collects no personally identifiable browsing profiles, and sends zero data to external third parties.',
  },
];

export const FaqAndInstallSection: React.FC<FaqAndInstallSectionProps> = ({ onOpenDownload }) => {
  const [openFaq, setOpenFaq] = useState<number | null>(0);

  return (
    <section className="relative w-full bg-[#0A0A0A] py-20 sm:py-28 border-t border-[#1C1C1C] text-white">
      <div className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 space-y-24">
        
        {/* 1. 3-Step Setup Area */}
        <div id="installation">
          <div className="text-left mb-12">
            <span className="text-[11px] font-semibold text-[#8E8E8E] uppercase tracking-widest mb-3 inline-block">
              Rapid Setup
            </span>
            <h2 className="text-3xl sm:text-5xl font-[850] tracking-tight text-white leading-[1.1]">
              Up and running in three simple steps.
            </h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            {/* Step 1 */}
            <div className="card-dark-glass p-8 text-left space-y-4">
              <div className="w-10 h-10 rounded-full bg-[#1F1F1F] border border-[#2E2E2E] text-white font-mono font-bold text-sm flex items-center justify-center">
                01
              </div>
              <h3 className="text-lg font-bold text-white tracking-tight">
                Download plugin (.zip)
              </h3>
              <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
                Download the official open-source package directly to your computer.
              </p>
              <div className="pt-2">
                <button
                  type="button"
                  onClick={onOpenDownload}
                  className="pill-button px-4 py-2 text-xs font-semibold bg-white text-black hover:bg-neutral-200 transition-all cursor-pointer inline-flex items-center gap-1.5"
                >
                  <span>Download .zip</span>
                  <Download className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            {/* Step 2 */}
            <div className="card-dark-glass p-8 text-left space-y-4">
              <div className="w-10 h-10 rounded-full bg-[#1F1F1F] border border-[#2E2E2E] text-white font-mono font-bold text-sm flex items-center justify-center">
                02
              </div>
              <h3 className="text-lg font-bold text-white tracking-tight">
                Upload to WordPress
              </h3>
              <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
                In your WordPress Admin, go to <span className="text-white font-medium">Plugins ➔ Add New ➔ Upload</span> and select the file.
              </p>
            </div>

            {/* Step 3 */}
            <div className="card-dark-glass p-8 text-left space-y-4">
              <div className="w-10 h-10 rounded-full bg-[#1F1F1F] border border-[#2E2E2E] text-white font-mono font-bold text-sm flex items-center justify-center">
                03
              </div>
              <h3 className="text-lg font-bold text-white tracking-tight">
                Activate and relax
              </h3>
              <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
                Click Activate. Vighnaghna immediately protects all existing forms across your website automatically.
              </p>
            </div>

          </div>
        </div>

        {/* 2. Comparison Table */}
        <div id="comparison" className="card-dark-glass p-7 sm:p-10 text-left">
          <div className="mb-8">
            <h3 className="text-2xl sm:text-4xl font-[850] text-white tracking-tight">
              Vighnaghna vs. traditional solutions.
            </h3>
            <p className="text-xs sm:text-sm text-[#8E8E8E] mt-1.5">
              Engineered to eliminate friction without sacrificing security.
            </p>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs sm:text-sm">
              <thead>
                <tr className="border-b border-[#222222]">
                  <th className="py-3.5 px-4 font-semibold text-xs text-[#8E8E8E]">Capability</th>
                  <th className="py-3.5 px-4 font-semibold text-xs text-white bg-[#1F1F1F] rounded-t-xl">
                    Vighnaghna (WP)
                  </th>
                  <th className="py-3.5 px-4 font-semibold text-xs text-[#8E8E8E]">reCAPTCHA v2/v3</th>
                  <th className="py-3.5 px-4 font-semibold text-xs text-[#8E8E8E]">Basic Honeypots</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#222222]">
                <tr>
                  <td className="py-4 px-4 font-medium text-white">Zero puzzles or image clicks</td>
                  <td className="py-4 px-4 bg-[#181818] font-semibold text-white flex items-center gap-2">
                    <Check className="w-4 h-4 text-emerald-400" /> 100% Invisible
                  </td>
                  <td className="py-4 px-4 text-[#8E8E8E]">Puzzles required</td>
                  <td className="py-4 px-4 text-[#8E8E8E]">Invisible</td>
                </tr>
                <tr>
                  <td className="py-4 px-4 font-medium text-white">Blocks headless Puppeteer scrapers</td>
                  <td className="py-4 px-4 bg-[#181818] font-semibold text-white flex items-center gap-2">
                    <Check className="w-4 h-4 text-emerald-400" /> Advanced Heuristics
                  </td>
                  <td className="py-4 px-4 text-[#8E8E8E]">Partially bypassed</td>
                  <td className="py-4 px-4 text-[#8E8E8E]">Easily bypassed</td>
                </tr>
                <tr>
                  <td className="py-4 px-4 font-medium text-white">Zero external tracking cookies (GDPR)</td>
                  <td className="py-4 px-4 bg-[#181818] font-semibold text-white flex items-center gap-2">
                    <Check className="w-4 h-4 text-emerald-400" /> Zero Cookies
                  </td>
                  <td className="py-4 px-4 text-[#8E8E8E]">Sets tracking cookies</td>
                  <td className="py-4 px-4 text-[#8E8E8E]">Zero cookies</td>
                </tr>
                <tr>
                  <td className="py-4 px-4 font-medium text-white">Zero API keys or third-party accounts</td>
                  <td className="py-4 px-4 bg-[#181818] font-semibold text-white rounded-b-xl flex items-center gap-2">
                    <Check className="w-4 h-4 text-emerald-400" /> Self-contained
                  </td>
                  <td className="py-4 px-4 text-[#8E8E8E]">Requires Google account</td>
                  <td className="py-4 px-4 text-[#8E8E8E]">Self-contained</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        {/* 3. FAQ Area */}
        <div id="faq" className="text-left space-y-4">
          <div className="mb-4">
            <span className="text-[11px] font-semibold text-[#8E8E8E] uppercase tracking-widest mb-2 inline-block">
              Frequently Asked Questions
            </span>
            <h3 className="text-3xl sm:text-4xl font-[850] text-white tracking-tight">
              Everything you need to know.
            </h3>
          </div>

          <div className="space-y-3">
            {FAQS.map((faq, idx) => {
              const isOpen = openFaq === idx;
              return (
                <div
                  key={idx}
                  className="card-dark-glass overflow-hidden"
                >
                  <button
                    type="button"
                    onClick={() => setOpenFaq(isOpen ? null : idx)}
                    className="w-full flex items-center justify-between p-6 text-left font-bold text-sm sm:text-base text-white hover:text-[#FF4D6D] transition-colors cursor-pointer"
                  >
                    <span>{faq.q}</span>
                    <ChevronDown
                      className={`w-4 h-4 shrink-0 transition-transform duration-200 text-[#8E8E8E] ${
                        isOpen ? 'rotate-180 text-white' : ''
                      }`}
                    />
                  </button>
                  {isOpen && (
                    <div className="px-6 pb-6 text-xs sm:text-sm text-[#8E8E8E] leading-relaxed border-t border-[#222222] pt-4">
                      {faq.a}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>

        {/* 4. Minimal Studio CTA Callout */}
        <div className="relative rounded-[28px] overflow-hidden border border-[#222222] bg-[#121212] p-8 sm:p-14 text-center space-y-4">
          <div className="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.06),transparent_70%)] pointer-events-none" />

          <h2 className="relative z-10 text-2xl sm:text-4xl font-[850] tracking-tight text-white">
            We prevent bots from spamming your forms.
          </h2>
          <p className="relative z-10 text-xs sm:text-sm text-[#8E8E8E] max-w-md mx-auto leading-relaxed">
            100% free, privacy-first, zero-configuration WordPress plugin.
          </p>
          <div className="relative z-10 pt-2">
            <button
              type="button"
              onClick={onOpenDownload}
              className="pill-button inline-flex items-center gap-2 bg-white text-black font-semibold text-xs sm:text-sm px-7 py-3.5 hover:bg-neutral-200 cursor-pointer shadow-md transition-all"
            >
              <span>Download Vighnaghna (.zip)</span>
              <ArrowRight className="w-4 h-4" />
            </button>
          </div>
        </div>

      </div>
    </section>
  );
};

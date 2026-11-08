import React, { useState } from 'react';
import { Download, ChevronDown, Check, HelpCircle } from 'lucide-react';

interface FaqAndInstallSectionProps {
  onOpenDownload: () => void;
}

const FAQS = [
  {
    q: 'Does BotFence require API keys from Google or Cloudflare?',
    a: 'No. BotFence operates independently on your server using local behavioral heuristics and polymorphic honeypots. No third-party accounts or API credentials are required.',
  },
  {
    q: 'Will legitimate visitors ever see puzzles or CAPTCHAs?',
    a: 'Never. BotFence is entirely invisible to humans. Legitimate visitors submit forms seamlessly without audio puzzles or selecting crosswalk images, boosting conversion rates.',
  },
  {
    q: 'Which WordPress form builders are supported out of the box?',
    a: 'BotFence automatically integrates with Contact Form 7, WPForms, Gravity Forms, Elementor Forms, Fluent Forms, Formidable Forms, WooCommerce checkout, and wp-login.',
  },
  {
    q: 'Does BotFence impact website load times or speed?',
    a: 'No. The client-side asset is under 3.2KB and loads asynchronously. Server-side verification takes under 2 milliseconds on typical hosting, saving database resources by dropping spam early.',
  },
  {
    q: 'Is BotFence compliant with GDPR, CCPA, and European privacy regulations?',
    a: 'Yes, 100%. BotFence sets no third-party tracking cookies, collects no personally identifiable browsing profiles, and sends zero data to external third parties.',
  },
];

export const FaqAndInstallSection: React.FC<FaqAndInstallSectionProps> = ({ onOpenDownload }) => {
  const [openFaq, setOpenFaq] = useState<number | null>(0);

  return (
    <section className="relative w-full bg-[#ffffff] py-16 sm:py-24 border-t border-[#f0f0f0]">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-20">
        
        {/* 3-Step Setup (Mobbin 24px cards) */}
        <div>
          <div className="text-left mb-10">
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#f3f3f3] text-xs font-semibold text-[#141414] mb-4">
              <span>Installation</span>
            </div>
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-[650] tracking-tight text-[#141414] leading-[1.13]">
              Up and running in three simple steps.
            </h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="bg-[#f3f3f3] rounded-[24px] p-7 text-left space-y-3">
              <div className="w-8 h-8 rounded-full bg-[#141414] text-[#ffffff] font-semibold text-xs flex items-center justify-center">
                1
              </div>
              <h3 className="text-lg font-[650] text-[#141414]">
                Download plugin (.zip)
              </h3>
              <p className="text-sm font-[450] text-[#707070] leading-relaxed">
                Download the official open-source package directly to your computer.
              </p>
              <div className="pt-2">
                <button
                  type="button"
                  onClick={onOpenDownload}
                  className="pill-button px-4 py-2 text-xs font-semibold bg-[#ffffff] text-[#141414] border border-[#e0e0e0] hover:bg-[#f3f3f3]"
                >
                  Download .zip
                </button>
              </div>
            </div>

            <div className="bg-[#f3f3f3] rounded-[24px] p-7 text-left space-y-3">
              <div className="w-8 h-8 rounded-full bg-[#141414] text-[#ffffff] font-semibold text-xs flex items-center justify-center">
                2
              </div>
              <h3 className="text-lg font-[650] text-[#141414]">
                Upload to WordPress
              </h3>
              <p className="text-sm font-[450] text-[#707070] leading-relaxed">
                In your WordPress Admin, go to <span className="text-[#141414] font-medium">Plugins ➔ Add New ➔ Upload</span> and select the file.
              </p>
            </div>

            <div className="bg-[#f3f3f3] rounded-[24px] p-7 text-left space-y-3">
              <div className="w-8 h-8 rounded-full bg-[#141414] text-[#ffffff] font-semibold text-xs flex items-center justify-center">
                3
              </div>
              <h3 className="text-lg font-[650] text-[#141414]">
                Activate and relax
              </h3>
              <p className="text-sm font-[450] text-[#707070] leading-relaxed">
                Click Activate. BotFence immediately protects all existing forms across your website automatically.
              </p>
            </div>
          </div>
        </div>

        {/* Comparison Table (Mobbin compare-table primitive) */}
        <div className="rounded-[24px] border border-[#f0f0f0] p-6 sm:p-8 text-left">
          <div className="mb-6">
            <h3 className="text-2xl font-[650] text-[#141414] tracking-tight">
              BotFence vs. traditional solutions.
            </h3>
            <p className="text-sm text-[#707070] mt-1">
              Engineered to eliminate friction without sacrificing security.
            </p>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-[#f0f0f0]">
                  <th className="py-3 px-4 font-semibold text-xs text-[#707070]">Capability</th>
                  <th className="py-3 px-4 font-semibold text-xs text-[#141414] bg-[#f3f3f3] rounded-t-xl">
                    BotFence (WP)
                  </th>
                  <th className="py-3 px-4 font-semibold text-xs text-[#707070]">reCAPTCHA v2/v3</th>
                  <th className="py-3 px-4 font-semibold text-xs text-[#707070]">Basic Honeypots</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#f0f0f0]">
                <tr>
                  <td className="py-3.5 px-4 font-medium text-[#141414]">Zero puzzles or image clicks</td>
                  <td className="py-3.5 px-4 bg-[#f3f3f3] font-medium text-[#141414] flex items-center gap-1.5">
                    <Check className="w-4 h-4 text-[#141414]" /> 100% Invisible
                  </td>
                  <td className="py-3.5 px-4 text-[#707070]">Puzzles required</td>
                  <td className="py-3.5 px-4 text-[#707070]">Invisible</td>
                </tr>
                <tr>
                  <td className="py-3.5 px-4 font-medium text-[#141414]">Blocks headless Puppeteer scrapers</td>
                  <td className="py-3.5 px-4 bg-[#f3f3f3] font-medium text-[#141414] flex items-center gap-1.5">
                    <Check className="w-4 h-4 text-[#141414]" /> Advanced Heuristics
                  </td>
                  <td className="py-3.5 px-4 text-[#707070]">Partially bypassed</td>
                  <td className="py-3.5 px-4 text-[#707070]">Easily bypassed</td>
                </tr>
                <tr>
                  <td className="py-3.5 px-4 font-medium text-[#141414]">Zero external tracking cookies (GDPR)</td>
                  <td className="py-3.5 px-4 bg-[#f3f3f3] font-medium text-[#141414] flex items-center gap-1.5">
                    <Check className="w-4 h-4 text-[#141414]" /> Zero Cookies
                  </td>
                  <td className="py-3.5 px-4 text-[#707070]">Sets tracking cookies</td>
                  <td className="py-3.5 px-4 text-[#707070]">Zero cookies</td>
                </tr>
                <tr>
                  <td className="py-3.5 px-4 font-medium text-[#141414]">Zero API keys or third-party accounts</td>
                  <td className="py-3.5 px-4 bg-[#f3f3f3] font-medium text-[#141414] rounded-b-xl flex items-center gap-1.5">
                    <Check className="w-4 h-4 text-[#141414]" /> Self-contained
                  </td>
                  <td className="py-3.5 px-4 text-[#707070]">Requires Google account</td>
                  <td className="py-3.5 px-4 text-[#707070]">Self-contained</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        {/* Mobbin FAQ Rows (16px rounded bars) */}
        <div className="text-left space-y-4">
          <div className="mb-2">
            <h3 className="text-2xl font-[650] text-[#141414] tracking-tight">
              Frequently asked questions.
            </h3>
          </div>

          <div className="space-y-3">
            {FAQS.map((faq, idx) => {
              const isOpen = openFaq === idx;
              return (
                <div
                  key={idx}
                  className="bg-[#f3f3f3] rounded-[16px] overflow-hidden transition-colors"
                >
                  <button
                    type="button"
                    onClick={() => setOpenFaq(isOpen ? null : idx)}
                    className="w-full flex items-center justify-between p-5 text-left font-[650] text-base text-[#141414] cursor-pointer"
                  >
                    <span>{faq.q}</span>
                    <ChevronDown
                      className={`w-4 h-4 shrink-0 transition-transform duration-200 text-[#707070] ${
                        isOpen ? 'rotate-180' : ''
                      }`}
                    />
                  </button>
                  {isOpen && (
                    <div className="px-5 pb-5 font-[450] text-sm text-[#707070] leading-relaxed">
                      {faq.a}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>

        {/* Bottom CTA Card */}
        <div className="bg-[#f3f3f3] rounded-[24px] p-8 sm:p-12 text-center space-y-4">
          <h2 className="text-2xl sm:text-3xl md:text-4xl font-[650] tracking-tight text-[#141414]">
            Ready to protect your WordPress forms?
          </h2>
          <p className="text-base text-[#707070] max-w-lg mx-auto">
            Download the plugin now. Takes less than a minute to install with zero configuration needed.
          </p>
          <div className="pt-2">
            <button
              type="button"
              onClick={onOpenDownload}
              className="pill-button inline-flex items-center gap-2 bg-[#141414] text-[#ffffff] font-semibold text-base px-7 py-3.5 hover:bg-[#262626] cursor-pointer"
            >
              <Download className="w-4 h-4" />
              <span>Download BotFence (.zip)</span>
            </button>
          </div>
        </div>

      </div>
    </section>
  );
};

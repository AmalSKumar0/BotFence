import React, { useState } from 'react';
import { Download, ChevronDown, Check, ArrowRight, Shield } from 'lucide-react';

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
    <section className="relative w-full bg-white py-16 sm:py-24 border-t border-[#ECE5DA]">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-20">
        
        {/* 3-Step Setup Area */}
        <div id="installation">
          <div className="text-left mb-10">
            <span className="px-3 py-1 rounded-full bg-[#FAF7F2] border border-[#ECE5DA] text-[#121110] text-xs font-semibold mb-3 inline-block">
              Installation
            </span>
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-[850] tracking-tight text-[#121110] leading-[1.1]">
              Up and running in three simple steps.
            </h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="bg-[#FAF7F2] rounded-[28px] p-7 text-left space-y-3.5 border border-[#ECE5DA] shadow-xs hover:border-[#121110]/30 transition-all">
              <div className="w-9 h-9 rounded-full bg-[#121110] text-white font-bold text-xs flex items-center justify-center shadow-xs">
                1
              </div>
              <h3 className="text-base font-bold text-[#121110]">
                Download plugin (.zip)
              </h3>
              <p className="text-xs text-[#7C7872] leading-relaxed">
                Download the official open-source package directly to your computer.
              </p>
              <div className="pt-2">
                <button
                  type="button"
                  onClick={onOpenDownload}
                  className="pill-button px-4 py-2 text-xs font-semibold bg-white text-[#121110] border border-[#ECE5DA] hover:bg-[#121110] hover:text-white transition-all cursor-pointer shadow-xs inline-flex items-center gap-1.5 rounded-full"
                >
                  <span>Download .zip</span>
                  <Download className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <div className="bg-[#FAF7F2] rounded-[28px] p-7 text-left space-y-3.5 border border-[#ECE5DA] shadow-xs hover:border-[#121110]/30 transition-all">
              <div className="w-9 h-9 rounded-full bg-[#121110] text-white font-bold text-xs flex items-center justify-center shadow-xs">
                2
              </div>
              <h3 className="text-base font-bold text-[#121110]">
                Upload to WordPress
              </h3>
              <p className="text-xs text-[#7C7872] leading-relaxed">
                In your WordPress Admin, go to <span className="text-[#121110] font-semibold">Plugins ➔ Add New ➔ Upload</span> and select the file.
              </p>
            </div>

            <div className="bg-[#FAF7F2] rounded-[28px] p-7 text-left space-y-3.5 border border-[#ECE5DA] shadow-xs hover:border-[#121110]/30 transition-all">
              <div className="w-9 h-9 rounded-full bg-[#121110] text-white font-bold text-xs flex items-center justify-center shadow-xs">
                3
              </div>
              <h3 className="text-base font-bold text-[#121110]">
                Activate and relax
              </h3>
              <p className="text-xs text-[#7C7872] leading-relaxed">
                Click Activate. Vighnaghna immediately protects all existing forms across your website automatically.
              </p>
            </div>
          </div>
        </div>

        {/* Comparison Table */}
        <div id="comparison" className="rounded-[28px] border border-[#ECE5DA] p-6 sm:p-8 text-left bg-[#FAF7F2] shadow-xs">
          <div className="mb-6">
            <h3 className="text-2xl sm:text-3xl font-[850] text-[#121110] tracking-tight">
              Vighnaghna vs. traditional solutions.
            </h3>
            <p className="text-xs text-[#7C7872] mt-1 font-medium">
              Engineered to eliminate friction without sacrificing security.
            </p>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs sm:text-sm">
              <thead>
                <tr className="border-b border-[#ECE5DA]">
                  <th className="py-3 px-4 font-semibold text-xs text-[#7C7872]">Capability</th>
                  <th className="py-3 px-4 font-semibold text-xs text-white bg-[#121110] rounded-t-xl shadow-xs">
                    Vighnaghna (WP)
                  </th>
                  <th className="py-3 px-4 font-semibold text-xs text-[#7C7872]">reCAPTCHA v2/v3</th>
                  <th className="py-3 px-4 font-semibold text-xs text-[#7C7872]">Basic Honeypots</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#ECE5DA]">
                <tr>
                  <td className="py-3.5 px-4 font-medium text-[#121110]">Zero puzzles or image clicks</td>
                  <td className="py-3.5 px-4 bg-white font-semibold text-[#121110] flex items-center gap-1.5">
                    <Check className="w-4 h-4 text-emerald-600" /> 100% Invisible
                  </td>
                  <td className="py-3.5 px-4 text-[#7C7872]">Puzzles required</td>
                  <td className="py-3.5 px-4 text-[#7C7872]">Invisible</td>
                </tr>
                <tr>
                  <td className="py-3.5 px-4 font-medium text-[#121110]">Blocks headless Puppeteer scrapers</td>
                  <td className="py-3.5 px-4 bg-white font-semibold text-[#121110] flex items-center gap-1.5">
                    <Check className="w-4 h-4 text-emerald-600" /> Advanced Heuristics
                  </td>
                  <td className="py-3.5 px-4 text-[#7C7872]">Partially bypassed</td>
                  <td className="py-3.5 px-4 text-[#7C7872]">Easily bypassed</td>
                </tr>
                <tr>
                  <td className="py-3.5 px-4 font-medium text-[#121110]">Zero external tracking cookies (GDPR)</td>
                  <td className="py-3.5 px-4 bg-white font-semibold text-[#121110] flex items-center gap-1.5">
                    <Check className="w-4 h-4 text-emerald-600" /> Zero Cookies
                  </td>
                  <td className="py-3.5 px-4 text-[#7C7872]">Sets tracking cookies</td>
                  <td className="py-3.5 px-4 text-[#7C7872]">Zero cookies</td>
                </tr>
                <tr>
                  <td className="py-3.5 px-4 font-medium text-[#121110]">Zero API keys or third-party accounts</td>
                  <td className="py-3.5 px-4 bg-white font-semibold text-[#121110] rounded-b-xl flex items-center gap-1.5">
                    <Check className="w-4 h-4 text-emerald-600" /> Self-contained
                  </td>
                  <td className="py-3.5 px-4 text-[#7C7872]">Requires Google account</td>
                  <td className="py-3.5 px-4 text-[#7C7872]">Self-contained</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        {/* FAQ Area */}
        <div id="faq" className="text-left space-y-4">
          <div className="mb-2">
            <span className="px-3 py-1 rounded-full bg-[#FAF7F2] border border-[#ECE5DA] text-[#121110] text-xs font-semibold mb-3 inline-block">
              Answers
            </span>
            <h3 className="text-3xl font-[850] text-[#121110] tracking-tight">
              Frequently asked questions.
            </h3>
          </div>

          <div className="space-y-3">
            {FAQS.map((faq, idx) => {
              const isOpen = openFaq === idx;
              return (
                <div
                  key={idx}
                  className="bg-[#FAF7F2] rounded-[20px] overflow-hidden border border-[#ECE5DA] shadow-xs transition-colors"
                >
                  <button
                    type="button"
                    onClick={() => setOpenFaq(isOpen ? null : idx)}
                    className="w-full flex items-center justify-between p-5 text-left font-bold text-sm sm:text-base text-[#121110] hover:bg-white transition-colors cursor-pointer"
                  >
                    <span>{faq.q}</span>
                    <ChevronDown
                      className={`w-4 h-4 shrink-0 transition-transform duration-200 text-[#7C7872] ${
                        isOpen ? 'rotate-180 text-[#121110]' : ''
                      }`}
                    />
                  </button>
                  {isOpen && (
                    <div className="px-5 pb-5 font-normal text-xs sm:text-sm text-[#7C7872] leading-relaxed border-t border-[#ECE5DA] pt-3 bg-white">
                      {faq.a}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>

        {/* Bottom CTA Card in Warm Editorial Sand Tone */}
        <div className="rounded-[32px] bg-[#FAF7F2] border border-[#ECE5DA] p-8 sm:p-14 text-center space-y-4 shadow-sm">
          <div className="w-12 h-12 rounded-2xl bg-[#121110] text-white flex items-center justify-center mx-auto shadow-md">
            <Shield className="w-6 h-6 stroke-[2.2]" />
          </div>

          <h2 className="text-3xl sm:text-4xl md:text-5xl font-[850] tracking-tight text-[#121110]">
            Ready to protect your WordPress forms?
          </h2>
          <p className="text-xs sm:text-sm text-[#7C7872] max-w-md mx-auto leading-relaxed">
            Download the free plugin package now. Takes under a minute to install with zero configuration needed.
          </p>
          <div className="pt-2">
            <button
              type="button"
              onClick={onOpenDownload}
              className="pill-button inline-flex items-center gap-2 bg-[#121110] text-white font-semibold text-sm px-8 py-4 hover:bg-[#2B2927] cursor-pointer shadow-md transition-all rounded-full"
            >
              <Download className="w-4 h-4" />
              <span>Download Vighnaghna (.zip)</span>
              <ArrowRight className="w-4 h-4" />
            </button>
          </div>
        </div>

      </div>
    </section>
  );
};

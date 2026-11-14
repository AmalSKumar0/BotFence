import React, { useState } from 'react';
import { Download, ChevronDown, Check } from 'lucide-react';

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
    <section className="relative w-full bg-[#F7F1ED] py-20 sm:py-28 border-t border-[#E7DCD7]">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-20">
        
        {/* 3-Step Setup Area */}
        <div id="installation">
          <div className="text-left mb-10">
            <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#FFFFFF] border border-[#E7DCD7] text-xs font-semibold text-[#38142A] mb-4 shadow-xs">
              <span className="w-2 h-2 rounded-full bg-[#38142A]"></span>
              <span>Installation</span>
            </div>
            <h2 className="text-3xl sm:text-4xl md:text-5xl font-[650] tracking-tight text-[#210918] leading-[1.13]">
              Up and running in three simple steps.
            </h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="bg-[#FFFFFF] rounded-[24px] p-7 text-left space-y-3.5 border border-[#E7DCD7] shadow-[0_2px_8px_rgba(33,9,24,0.03)] hover:border-[#DCC9BE] transition-all">
              <div className="w-9 h-9 rounded-full bg-[#38142A] text-[#FFFFFF] font-semibold text-xs flex items-center justify-center shadow-xs">
                1
              </div>
              <h3 className="text-lg font-[650] text-[#210918]">
                Download plugin (.zip)
              </h3>
              <p className="text-sm font-[400] text-[#74656C] leading-relaxed">
                Download the official open-source package directly to your computer.
              </p>
              <div className="pt-2">
                <button
                  type="button"
                  onClick={onOpenDownload}
                  className="pill-button px-4 py-2 text-xs font-semibold bg-[#FFFFFF] text-[#38142A] border border-[#E7DCD7] hover:border-[#DCC9BE] hover:bg-[#F7F1ED] transition-all cursor-pointer shadow-xs"
                >
                  Download .zip
                </button>
              </div>
            </div>

            <div className="bg-[#FFFFFF] rounded-[24px] p-7 text-left space-y-3.5 border border-[#E7DCD7] shadow-[0_2px_8px_rgba(33,9,24,0.03)] hover:border-[#DCC9BE] transition-all">
              <div className="w-9 h-9 rounded-full bg-[#38142A] text-[#FFFFFF] font-semibold text-xs flex items-center justify-center shadow-xs">
                2
              </div>
              <h3 className="text-lg font-[650] text-[#210918]">
                Upload to WordPress
              </h3>
              <p className="text-sm font-[400] text-[#74656C] leading-relaxed">
                In your WordPress Admin, go to <span className="text-[#210918] font-medium">Plugins ➔ Add New ➔ Upload</span> and select the file.
              </p>
            </div>

            <div className="bg-[#FFFFFF] rounded-[24px] p-7 text-left space-y-3.5 border border-[#E7DCD7] shadow-[0_2px_8px_rgba(33,9,24,0.03)] hover:border-[#DCC9BE] transition-all">
              <div className="w-9 h-9 rounded-full bg-[#38142A] text-[#FFFFFF] font-semibold text-xs flex items-center justify-center shadow-xs">
                3
              </div>
              <h3 className="text-lg font-[650] text-[#210918]">
                Activate and relax
              </h3>
              <p className="text-sm font-[400] text-[#74656C] leading-relaxed">
                Click Activate. Vighnaghna immediately protects all existing forms across your website automatically.
              </p>
            </div>
          </div>
        </div>

        {/* Comparison Table */}
        <div id="comparison" className="rounded-[24px] border border-[#E7DCD7] p-6 sm:p-8 text-left bg-[#FFFFFF] shadow-[0_2px_8px_rgba(33,9,24,0.03)]">
          <div className="mb-6">
            <h3 className="text-2xl font-[650] text-[#210918] tracking-tight">
              Vighnaghna vs. traditional solutions.
            </h3>
            <p className="text-sm text-[#74656C] mt-1">
              Engineered to eliminate friction without sacrificing security.
            </p>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-[#E7DCD7]">
                  <th className="py-3 px-4 font-semibold text-xs text-[#74656C]">Capability</th>
                  <th className="py-3 px-4 font-semibold text-xs text-[#FFFFFF] bg-[#38142A] rounded-t-xl shadow-xs">
                    Vighnaghna (WP)
                  </th>
                  <th className="py-3 px-4 font-semibold text-xs text-[#74656C]">reCAPTCHA v2/v3</th>
                  <th className="py-3 px-4 font-semibold text-xs text-[#74656C]">Basic Honeypots</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E7DCD7]">
                <tr>
                  <td className="py-3.5 px-4 font-medium text-[#210918]">Zero puzzles or image clicks</td>
                  <td className="py-3.5 px-4 bg-[#F7F1ED] font-medium text-[#210918] flex items-center gap-1.5">
                    <Check className="w-4 h-4 text-[#38142A]" /> 100% Invisible
                  </td>
                  <td className="py-3.5 px-4 text-[#74656C]">Puzzles required</td>
                  <td className="py-3.5 px-4 text-[#74656C]">Invisible</td>
                </tr>
                <tr>
                  <td className="py-3.5 px-4 font-medium text-[#210918]">Blocks headless Puppeteer scrapers</td>
                  <td className="py-3.5 px-4 bg-[#F7F1ED] font-medium text-[#210918] flex items-center gap-1.5">
                    <Check className="w-4 h-4 text-[#38142A]" /> Advanced Heuristics
                  </td>
                  <td className="py-3.5 px-4 text-[#74656C]">Partially bypassed</td>
                  <td className="py-3.5 px-4 text-[#74656C]">Easily bypassed</td>
                </tr>
                <tr>
                  <td className="py-3.5 px-4 font-medium text-[#210918]">Zero external tracking cookies (GDPR)</td>
                  <td className="py-3.5 px-4 bg-[#F7F1ED] font-medium text-[#210918] flex items-center gap-1.5">
                    <Check className="w-4 h-4 text-[#38142A]" /> Zero Cookies
                  </td>
                  <td className="py-3.5 px-4 text-[#74656C]">Sets tracking cookies</td>
                  <td className="py-3.5 px-4 text-[#74656C]">Zero cookies</td>
                </tr>
                <tr>
                  <td className="py-3.5 px-4 font-medium text-[#210918]">Zero API keys or third-party accounts</td>
                  <td className="py-3.5 px-4 bg-[#F7F1ED] font-medium text-[#210918] rounded-b-xl flex items-center gap-1.5">
                    <Check className="w-4 h-4 text-[#38142A]" /> Self-contained
                  </td>
                  <td className="py-3.5 px-4 text-[#74656C]">Requires Google account</td>
                  <td className="py-3.5 px-4 text-[#74656C]">Self-contained</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        {/* FAQ Area */}
        <div id="faq" className="text-left space-y-4">
          <div className="mb-2">
            <h3 className="text-2xl font-[650] text-[#210918] tracking-tight">
              Frequently asked questions.
            </h3>
          </div>

          <div className="space-y-3">
            {FAQS.map((faq, idx) => {
              const isOpen = openFaq === idx;
              return (
                <div
                  key={idx}
                  className="bg-[#FFFFFF] rounded-[16px] overflow-hidden border border-[#E7DCD7] shadow-xs transition-colors"
                >
                  <button
                    type="button"
                    onClick={() => setOpenFaq(isOpen ? null : idx)}
                    className="w-full flex items-center justify-between p-5 text-left font-[650] text-base text-[#210918] hover:bg-[#F7F1ED]/50 transition-colors cursor-pointer"
                  >
                    <span>{faq.q}</span>
                    <ChevronDown
                      className={`w-4 h-4 shrink-0 transition-transform duration-200 text-[#74656C] ${
                        isOpen ? 'rotate-180 text-[#38142A]' : ''
                      }`}
                    />
                  </button>
                  {isOpen && (
                    <div className="px-5 pb-5 font-[400] text-sm text-[#74656C] leading-relaxed border-t border-[#E7DCD7]/60 pt-3">
                      {faq.a}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>

        {/* Bottom CTA Card */}
        <div className="bg-[#FFFFFF] rounded-[24px] p-8 sm:p-12 text-center space-y-4 border border-[#E7DCD7] shadow-[0_4px_16px_rgba(33,9,24,0.04)]">
          <h2 className="text-2xl sm:text-3xl md:text-4xl font-[650] tracking-tight text-[#210918]">
            Ready to protect your WordPress forms?
          </h2>
          <p className="text-base text-[#74656C] max-w-lg mx-auto">
            Download the plugin now. Takes less than a minute to install with zero configuration needed.
          </p>
          <div className="pt-2">
            <button
              type="button"
              onClick={onOpenDownload}
              className="pill-button inline-flex items-center gap-2 bg-[#38142A] text-[#FFFFFF] font-semibold text-base px-8 py-3.5 hover:bg-[#4E223C] cursor-pointer shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-[#38142A] focus:ring-offset-2"
            >
              <Download className="w-4 h-4" />
              <span>Download Vighnaghna (.zip)</span>
            </button>
          </div>
        </div>

      </div>
    </section>
  );
};

import React from 'react';
import { Shield } from 'lucide-react';

interface FooterSectionProps {
  onOpenDownload: () => void;
}

export const FooterSection: React.FC<FooterSectionProps> = ({ onOpenDownload }) => {
  return (
    <footer className="w-full bg-[#210918] text-[#FFFFFF] rounded-t-[32px] pt-16 pb-12 text-left font-sans border-t border-[#4E223C]">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Top Grid: Brand & Description on Left, Columns on Right */}
        <div className="grid grid-cols-1 md:grid-cols-12 gap-10 lg:gap-14 pb-14">
          
          {/* Brand & Description Column (Left) */}
          <div className="md:col-span-6 lg:col-span-6 space-y-4">
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 squircle-icon bg-[#38142A] flex items-center justify-center text-[#DCC9BE] border border-[#4E223C]">
                <Shield className="w-5 h-5 stroke-[2.2]" />
              </div>
              <div className="text-xl font-bold tracking-tight text-[#FFFFFF]">
                VIGHNAGHNA <span className="font-serif text-[#DCC9BE] font-normal text-sm ml-1">विघ्नघ्न</span>
              </div>
            </div>
            <p className="font-mono text-xs sm:text-sm text-[#DCC9BE]/80 leading-relaxed max-w-sm">
              Lightweight, invisible anti-spam protection for WordPress forms. Blocks automated bots across CF7, WPForms, Gravity Forms, and WooCommerce with zero external requests.
            </p>
          </div>

          {/* Right Columns Grid (PLATFORM & COMPANY) */}
          <div className="md:col-span-6 lg:col-span-6 grid grid-cols-2 gap-8 sm:gap-12">
            
            {/* Column 1: PLATFORM */}
            <div className="space-y-4">
              <h3 className="font-mono text-xs font-semibold uppercase tracking-wider text-[#DCC9BE]">
                PLATFORM
              </h3>
              <ul className="space-y-3 font-mono text-xs sm:text-sm text-[#F7F1ED]/80">
                <li>
                  <button
                    type="button"
                    onClick={onOpenDownload}
                    className="hover:text-[#FFFFFF] transition-colors cursor-pointer text-left"
                  >
                    Download .zip
                  </button>
                </li>
                <li>
                  <a
                    href="#features"
                    className="hover:text-[#FFFFFF] transition-colors"
                  >
                    Form Protection
                  </a>
                </li>
                <li>
                  <a
                    href="#installation"
                    className="hover:text-[#FFFFFF] transition-colors"
                  >
                    Installation
                  </a>
                </li>
                <li>
                  <a
                    href="#comparison"
                    className="hover:text-[#FFFFFF] transition-colors"
                  >
                    Comparison
                  </a>
                </li>
              </ul>
            </div>

            {/* Column 2: COMPANY */}
            <div className="space-y-4">
              <h3 className="font-mono text-xs font-semibold uppercase tracking-wider text-[#DCC9BE]">
                RESOURCES
              </h3>
              <ul className="space-y-3 font-mono text-xs sm:text-sm text-[#F7F1ED]/80">
                <li>
                  <a
                    href="#faq"
                    className="hover:text-[#FFFFFF] transition-colors"
                  >
                    FAQ
                  </a>
                </li>
                <li>
                  <a
                    href="https://amalskumar.dev"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="hover:text-[#FFFFFF] transition-colors"
                  >
                    Developer
                  </a>
                </li>
                <li>
                  <span className="text-[#B49F99]/60">
                    Privacy First (GDPR)
                  </span>
                </li>
                <li>
                  <span className="text-[#B49F99]/60">
                    GPL-2.0+ License
                  </span>
                </li>
              </ul>
            </div>

          </div>

        </div>

        {/* Divider Line */}
        <div className="w-full border-t border-[#38142A]" />

        {/* Bottom Bar: Copyright & Attribution on Left, Socials on Right */}
        <div className="pt-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 font-mono text-xs text-[#B49F99]">
          
          <div className="flex flex-wrap items-center gap-2">
            <span>&copy; {new Date().getFullYear()} VIGHNAGHNA. All rights reserved.</span>
            <span className="text-[#4E223C] hidden sm:inline">|</span>
            <span>
              Developed by{' '}
              <a
                href="https://amalskumar.dev"
                target="_blank"
                rel="noopener noreferrer"
                className="text-[#DCC9BE] hover:text-[#FFFFFF] transition-colors underline underline-offset-4 decoration-[#B49F99] hover:decoration-[#DCC9BE]"
              >
                Amal S Kumar
              </a>
            </span>
          </div>

          <div className="flex items-center gap-6 sm:gap-8 text-xs text-[#DCC9BE]">
            <a
              href="https://github.com/AmalSKumar0/BotFence"
              target="_blank"
              rel="noopener noreferrer"
              className="hover:text-[#FFFFFF] transition-colors"
            >
              GitHub Repository
            </a>
            <a
              href="https://amalskumar.dev"
              target="_blank"
              rel="noopener noreferrer"
              className="hover:text-[#FFFFFF] transition-colors"
            >
              amalskumar.dev
            </a>
          </div>

        </div>

      </div>
    </footer>
  );
};

import React from 'react';
import { Shield, ArrowUpRight } from 'lucide-react';

interface FooterSectionProps {
  onOpenDownload: () => void;
}

export const FooterSection: React.FC<FooterSectionProps> = ({ onOpenDownload }) => {
  return (
    <footer className="w-full bg-[#FAF9FD] text-[#0D0C15] pt-16 pb-12 text-left font-sans border-t border-[#ECEAF5]">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Top Grid: Brand & Description on Left, Columns on Right */}
        <div className="grid grid-cols-1 md:grid-cols-12 gap-10 lg:gap-14 pb-14">
          
          {/* Brand & Description Column (Left) */}
          <div className="md:col-span-6 lg:col-span-6 space-y-4">
            <div className="flex items-center gap-2.5">
              <div className="w-8 h-8 squircle-icon bg-[#5B42F3] flex items-center justify-center text-white shadow-xs">
                <Shield className="w-4 h-4 stroke-[2.2]" />
              </div>
              <div className="text-xl font-[800] tracking-tight text-[#0D0C15]">
                VIGHNAGHNA <span className="font-serif text-[#7B68EE] font-medium text-xs ml-1">विघ्नघ्न</span>
              </div>
            </div>
            <p className="text-xs sm:text-sm text-[#6C6A7B] leading-relaxed max-w-sm">
              Lightweight, invisible anti-spam protection for WordPress forms. Blocks automated bots across CF7, WPForms, Gravity Forms, and WooCommerce with zero external requests.
            </p>
          </div>

          {/* Right Columns Grid (PLATFORM & RESOURCES) */}
          <div className="md:col-span-6 lg:col-span-6 grid grid-cols-2 gap-8 sm:gap-12">
            
            {/* Column 1: PLATFORM */}
            <div className="space-y-3.5">
              <h3 className="text-xs font-bold uppercase tracking-wider text-[#0D0C15]">
                PLATFORM
              </h3>
              <ul className="space-y-2.5 text-xs text-[#6C6A7B]">
                <li>
                  <button
                    type="button"
                    onClick={onOpenDownload}
                    className="hover:text-[#5B42F3] transition-colors cursor-pointer text-left"
                  >
                    Download .zip
                  </button>
                </li>
                <li>
                  <a
                    href="#why-vighnaghna"
                    className="hover:text-[#5B42F3] transition-colors"
                  >
                    Why Vighnaghna
                  </a>
                </li>
                <li>
                  <a
                    href="#how-it-works"
                    className="hover:text-[#5B42F3] transition-colors"
                  >
                    How it works
                  </a>
                </li>
                <li>
                  <a
                    href="#comparison"
                    className="hover:text-[#5B42F3] transition-colors"
                  >
                    Comparison Matrix
                  </a>
                </li>
              </ul>
            </div>

            {/* Column 2: RESOURCES */}
            <div className="space-y-3.5">
              <h3 className="text-xs font-bold uppercase tracking-wider text-[#0D0C15]">
                RESOURCES
              </h3>
              <ul className="space-y-2.5 text-xs text-[#6C6A7B]">
                <li>
                  <a
                    href="#faq"
                    className="hover:text-[#5B42F3] transition-colors"
                  >
                    FAQ
                  </a>
                </li>
                <li>
                  <a
                    href="https://amalskumar.dev"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="hover:text-[#5B42F3] transition-colors inline-flex items-center gap-1"
                  >
                    <span>Developer</span>
                    <ArrowUpRight className="w-3 h-3" />
                  </a>
                </li>
                <li>
                  <span className="text-[#6C6A7B]/70">
                    Privacy First (GDPR)
                  </span>
                </li>
                <li>
                  <span className="text-[#6C6A7B]/70">
                    GPL-2.0+ License
                  </span>
                </li>
              </ul>
            </div>

          </div>

        </div>

        {/* Divider Line */}
        <div className="w-full border-t border-[#ECEAF5]" />

        {/* Bottom Bar: Copyright & Attribution on Left, Socials on Right */}
        <div className="pt-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-xs text-[#6C6A7B]">
          
          <div className="flex flex-wrap items-center gap-2">
            <span>&copy; {new Date().getFullYear()} VIGHNAGHNA. All rights reserved.</span>
            <span className="text-[#D6D1F0] hidden sm:inline">|</span>
            <span>
              Developed by{' '}
              <a
                href="https://amalskumar.dev"
                target="_blank"
                rel="noopener noreferrer"
                className="text-[#0D0C15] hover:text-[#5B42F3] transition-colors font-medium underline underline-offset-4 decoration-[#D6D1F0] hover:decoration-[#5B42F3]"
              >
                Amal S Kumar
              </a>
            </span>
          </div>

          <div className="flex items-center gap-6 text-xs text-[#6C6A7B]">
            <a
              href="https://github.com/AmalSKumar0/BotFence"
              target="_blank"
              rel="noopener noreferrer"
              className="hover:text-[#5B42F3] transition-colors inline-flex items-center gap-1"
            >
              <span>GitHub</span>
              <ArrowUpRight className="w-3 h-3" />
            </a>
            <a
              href="https://amalskumar.dev"
              target="_blank"
              rel="noopener noreferrer"
              className="hover:text-[#5B42F3] transition-colors"
            >
              amalskumar.dev
            </a>
          </div>

        </div>

      </div>
    </footer>
  );
};

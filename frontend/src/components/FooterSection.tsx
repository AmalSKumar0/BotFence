import React from 'react';
import { Shield, ArrowUpRight } from 'lucide-react';

interface FooterSectionProps {
  onOpenDownload: () => void;
}

export const FooterSection: React.FC<FooterSectionProps> = ({ onOpenDownload }) => {
  return (
    <footer className="w-full bg-[#0A0A0A] text-white pt-20 pb-14 text-left font-sans border-t border-[#1C1C1C]">
      <div className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16">
        
        {/* Top Grid: Brand & Description on Left, Navigation on Right */}
        <div className="grid grid-cols-1 md:grid-cols-12 gap-12 lg:gap-16 pb-16">
          
          {/* Brand Column (Left) */}
          <div className="md:col-span-6 space-y-4">
            <div className="flex items-center gap-2.5">
              <div className="w-8 h-8 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D]">
                <Shield className="w-4 h-4 stroke-[2.2]" />
              </div>
              <div className="text-xl font-[800] tracking-[0.2em] uppercase text-white">
                VIGHNAGHNA <span className="font-serif italic font-normal text-xs text-[#8E8E8E] normal-case ml-1">(विघ्नघ्न)</span>
              </div>
            </div>
            <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed max-w-sm">
              Lightweight, invisible anti-spam protection for WordPress forms. Blocks automated bots across CF7, WPForms, Gravity Forms, and WooCommerce with zero external requests.
            </p>
          </div>

          {/* Right Columns Grid */}
          <div className="md:col-span-6 grid grid-cols-2 gap-8 sm:gap-12">
            
            {/* Column 1: PLATFORM */}
            <div className="space-y-4">
              <h3 className="text-xs font-bold uppercase tracking-widest text-[#8E8E8E]">
                PLATFORM
              </h3>
              <ul className="space-y-3 text-xs sm:text-sm text-neutral-400">
                <li>
                  <button
                    type="button"
                    onClick={onOpenDownload}
                    className="hover:text-white transition-colors cursor-pointer text-left"
                  >
                    Download .zip
                  </button>
                </li>
                <li>
                  <a href="#features" className="hover:text-white transition-colors">
                    Features
                  </a>
                </li>
                <li>
                  <a href="#installation" className="hover:text-white transition-colors">
                    Installation
                  </a>
                </li>
                <li>
                  <a href="#comparison" className="hover:text-white transition-colors">
                    Comparison
                  </a>
                </li>
              </ul>
            </div>

            {/* Column 2: RESOURCES */}
            <div className="space-y-4">
              <h3 className="text-xs font-bold uppercase tracking-widest text-[#8E8E8E]">
                RESOURCES
              </h3>
              <ul className="space-y-3 text-xs sm:text-sm text-neutral-400">
                <li>
                  <a href="#faq" className="hover:text-white transition-colors">
                    FAQ
                  </a>
                </li>
                <li>
                  <a
                    href="https://amalskumar.dev"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="hover:text-white transition-colors inline-flex items-center gap-1"
                  >
                    <span>Developer</span>
                    <ArrowUpRight className="w-3 h-3" />
                  </a>
                </li>
                <li>
                  <a
                    href="https://github.com/AmalSKumar0/BotFence"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="hover:text-white transition-colors inline-flex items-center gap-1"
                  >
                    <span>GitHub</span>
                    <ArrowUpRight className="w-3 h-3" />
                  </a>
                </li>
                <li>
                  <span className="text-neutral-500">
                    GPL-2.0+ License
                  </span>
                </li>
              </ul>
            </div>

          </div>

        </div>

        {/* Divider Line */}
        <div className="w-full border-t border-[#1C1C1C]" />

        {/* Bottom Bar: Copyright & Attribution */}
        <div className="pt-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-xs text-[#8E8E8E]">
          <div className="flex flex-wrap items-center gap-2">
            <span>&copy; {new Date().getFullYear()} VIGHNAGHNA. All rights reserved.</span>
            <span className="text-neutral-700 hidden sm:inline">|</span>
            <span>
              Developed by{' '}
              <a
                href="https://amalskumar.dev"
                target="_blank"
                rel="noopener noreferrer"
                className="text-white hover:underline transition-colors font-medium"
              >
                Amal S Kumar
              </a>
            </span>
          </div>

          <div className="flex items-center gap-6 text-xs text-[#8E8E8E]">
            <a
              href="https://wordpress.org"
              target="_blank"
              rel="noopener noreferrer"
              className="hover:text-white transition-colors"
            >
              WordPress.org
            </a>
            <a
              href="https://amalskumar.dev"
              target="_blank"
              rel="noopener noreferrer"
              className="hover:text-white transition-colors"
            >
              amalskumar.dev
            </a>
          </div>
        </div>

      </div>
    </footer>
  );
};

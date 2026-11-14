import React from 'react';

interface FooterSectionProps {
  onOpenDownload: () => void;
}

export const FooterSection: React.FC<FooterSectionProps> = ({ onOpenDownload }) => {
  return (
    <footer className="w-full bg-[#141414] text-[#ffffff] rounded-t-[28px] pt-16 pb-12 text-left font-sans">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Top Grid: Brand & Description on Left, Columns on Right (Matching Image Layout) */}
        <div className="grid grid-cols-1 md:grid-cols-12 gap-10 lg:gap-14 pb-14">
          
          {/* Brand & Description Column (Left) */}
          <div className="md:col-span-6 lg:col-span-6 space-y-4">
            <div className="text-xl font-bold tracking-tight text-[#ffffff]">
              VIGHNAGHNA
            </div>
            <p className="font-mono text-xs sm:text-sm text-[#8c8c8c] leading-relaxed max-w-sm">
              The invisible anti-bot plugin for WordPress. Completely blocks spam submissions across CF7, WPForms, Gravity Forms, and WooCommerce.
            </p>
          </div>

          {/* Right Columns Grid (PLATFORM & COMPANY) */}
          <div className="md:col-span-6 lg:col-span-6 grid grid-cols-2 gap-8 sm:gap-12">
            
            {/* Column 1: PLATFORM */}
            <div className="space-y-4">
              <h3 className="font-mono text-xs font-semibold uppercase tracking-wider text-[#ffffff]">
                PLATFORM
              </h3>
              <ul className="space-y-3 font-mono text-xs sm:text-sm text-[#8c8c8c]">
                <li>
                  <button
                    type="button"
                    onClick={onOpenDownload}
                    className="hover:text-[#ffffff] transition-colors cursor-pointer text-left"
                  >
                    Download .zip
                  </button>
                </li>
                <li>
                  <a
                    href="#features"
                    className="hover:text-[#ffffff] transition-colors"
                  >
                    Form Protection
                  </a>
                </li>
                <li>
                  <a
                    href="#how-it-works"
                    className="hover:text-[#ffffff] transition-colors"
                  >
                    Heuristic Engine
                  </a>
                </li>
                <li>
                  <span className="text-[#555555]">
                    WordPress Directory
                  </span>
                </li>
              </ul>
            </div>

            {/* Column 2: COMPANY */}
            <div className="space-y-4">
              <h3 className="font-mono text-xs font-semibold uppercase tracking-wider text-[#ffffff]">
                COMPANY
              </h3>
              <ul className="space-y-3 font-mono text-xs sm:text-sm text-[#8c8c8c]">
                <li>
                  <a
                    href="#faq"
                    className="hover:text-[#ffffff] transition-colors"
                  >
                    About Us
                  </a>
                </li>
                <li>
                  <a
                    href="https://amalskumar.dev"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="hover:text-[#ffffff] transition-colors"
                  >
                    Developer
                  </a>
                </li>
                <li>
                  <span className="text-[#555555]">
                    Privacy Policy
                  </span>
                </li>
                <li>
                  <span className="text-[#555555]">
                    GPLv2 License
                  </span>
                </li>
              </ul>
            </div>

          </div>

        </div>

        {/* Divider Line (Matching Image Layout) */}
        <div className="w-full border-t border-[#262626]" />

        {/* Bottom Bar: Copyright & Attribution on Left, Socials on Right (Matching Image Layout) */}
        <div className="pt-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 font-mono text-xs text-[#8c8c8c]">
          
          <div className="flex flex-wrap items-center gap-2">
            <span>&copy; {new Date().getFullYear()} VIGHNAGHNA. All rights reserved.</span>
            <span className="text-[#383838] hidden sm:inline">|</span>
            <span>
              Developed by{' '}
              <a
                href="https://amalskumar.dev"
                target="_blank"
                rel="noopener noreferrer"
                className="text-[#ffffff] hover:text-[#0066ff] transition-colors underline underline-offset-4 decoration-[#383838] hover:decoration-[#0066ff]"
              >
                Amal S Kumar
              </a>
            </span>
          </div>

          <div className="flex items-center gap-6 sm:gap-8 text-xs">
            <a
              href="https://twitter.com"
              target="_blank"
              rel="noopener noreferrer"
              className="hover:text-[#ffffff] transition-colors"
            >
              Twitter
            </a>
            <a
              href="https://github.com/AmalSKumar0/BotFence"
              target="_blank"
              rel="noopener noreferrer"
              className="hover:text-[#ffffff] transition-colors"
            >
              GitHub
            </a>
            <a
              href="https://amalskumar.dev"
              target="_blank"
              rel="noopener noreferrer"
              className="hover:text-[#ffffff] transition-colors"
            >
              amalskumar.dev
            </a>
          </div>

        </div>

      </div>
    </footer>
  );
};

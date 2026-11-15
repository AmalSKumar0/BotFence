import React from 'react';
import { Shield, ArrowUpRight } from 'lucide-react';

interface FooterSectionProps {
  onOpenDownload: () => void;
  onNavigate?: (path: string) => void;
}

export const FooterSection: React.FC<FooterSectionProps> = ({ onOpenDownload, onNavigate }) => {
  const handleLink = (e: React.MouseEvent<HTMLAnchorElement>, path: string) => {
    if (onNavigate) {
      e.preventDefault();
      onNavigate(path);
    }
  };

  return (
    <footer className="w-full bg-[#0A0A0A] text-white pt-20 pb-14 text-left font-sans border-t border-[#1C1C1C]">
      <div className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16">
        
        {/* Top Grid: Brand & Description on Left, Navigation on Right */}
        <div className="grid grid-cols-1 md:grid-cols-12 gap-12 lg:gap-16 pb-16">
          
          {/* Brand Column (Left) */}
          <div className="md:col-span-4 space-y-4">
            <div className="flex items-center gap-2.5">
              <div className="w-8 h-8 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D]">
                <Shield className="w-4 h-4 stroke-[2.2]" />
              </div>
              <div className="text-xl font-[800] tracking-[0.2em] uppercase text-white">
                VIGHNAGHNA <span className="font-serif italic font-normal text-xs text-[#8E8E8E] normal-case ml-1">(विघ्नघ्न)</span>
              </div>
            </div>
            <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed max-w-sm">
              Lightweight, invisible anti-spam protection for WordPress forms. Blocks automated bot submissions across Contact Form 7, WPForms, Gravity Forms, and WooCommerce with zero external requests.
            </p>
            <div className="pt-2">
              <button
                type="button"
                onClick={onOpenDownload}
                className="px-4 py-2 rounded-full border border-[#2B2B2B] bg-[#141414] hover:border-[#FF4D6D]/60 hover:text-[#FF4D6D] text-xs font-medium tracking-wider text-white transition-all cursor-pointer inline-flex items-center gap-1.5"
              >
                <span>Download v1.0.0</span>
                <ArrowUpRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          {/* Right Columns Grid */}
          <div className="md:col-span-8 grid grid-cols-2 sm:grid-cols-4 gap-8">
            
            {/* Column 1: PLATFORM */}
            <div className="space-y-4">
              <h3 className="text-xs font-bold uppercase tracking-widest text-[#8E8E8E]">
                PLATFORM
              </h3>
              <ul className="space-y-3 text-xs sm:text-sm text-neutral-400">
                <li>
                  <a href="/features" onClick={(e) => handleLink(e, '/features')} className="hover:text-white transition-colors">
                    Features
                  </a>
                </li>
                <li>
                  <a href="/download" onClick={(e) => handleLink(e, '/download')} className="hover:text-white transition-colors">
                    Download .zip
                  </a>
                </li>
                <li>
                  <a href="/changelog" onClick={(e) => handleLink(e, '/changelog')} className="hover:text-white transition-colors">
                    Changelog
                  </a>
                </li>
                <li>
                  <a href="/about" onClick={(e) => handleLink(e, '/about')} className="hover:text-white transition-colors">
                    About Plugin
                  </a>
                </li>
              </ul>
            </div>

            {/* Column 2: DOCUMENTATION */}
            <div className="space-y-4">
              <h3 className="text-xs font-bold uppercase tracking-widest text-[#8E8E8E]">
                DOCS
              </h3>
              <ul className="space-y-3 text-xs sm:text-sm text-neutral-400">
                <li>
                  <a href="/docs" onClick={(e) => handleLink(e, '/docs')} className="hover:text-white transition-colors">
                    Docs Hub
                  </a>
                </li>
                <li>
                  <a href="/docs/installation" onClick={(e) => handleLink(e, '/docs/installation')} className="hover:text-white transition-colors">
                    Installation
                  </a>
                </li>
                <li>
                  <a href="/docs/configuration" onClick={(e) => handleLink(e, '/docs/configuration')} className="hover:text-white transition-colors">
                    Configuration
                  </a>
                </li>
                <li>
                  <a href="/support" onClick={(e) => handleLink(e, '/support')} className="hover:text-white transition-colors">
                    Support
                  </a>
                </li>
              </ul>
            </div>

            {/* Column 3: INTEGRATIONS */}
            <div className="space-y-4">
              <h3 className="text-xs font-bold uppercase tracking-widest text-[#8E8E8E]">
                INTEGRATIONS
              </h3>
              <ul className="space-y-3 text-xs sm:text-sm text-neutral-400">
                <li>
                  <a href="/integrations/contact-form-7" onClick={(e) => handleLink(e, '/integrations/contact-form-7')} className="hover:text-white transition-colors">
                    Contact Form 7
                  </a>
                </li>
                <li>
                  <a href="/features#wpforms" onClick={(e) => handleLink(e, '/features')} className="hover:text-white transition-colors">
                    WPForms
                  </a>
                </li>
                <li>
                  <a href="/features#woocommerce" onClick={(e) => handleLink(e, '/features')} className="hover:text-white transition-colors">
                    WooCommerce
                  </a>
                </li>
                <li>
                  <a href="/features#fluentforms" onClick={(e) => handleLink(e, '/features')} className="hover:text-white transition-colors">
                    Fluent Forms
                  </a>
                </li>
              </ul>
            </div>

            {/* Column 4: RESOURCES & LEGAL */}
            <div className="space-y-4">
              <h3 className="text-xs font-bold uppercase tracking-widest text-[#8E8E8E]">
                RESOURCES
              </h3>
              <ul className="space-y-3 text-xs sm:text-sm text-neutral-400">
                <li>
                  <a href="/blog" onClick={(e) => handleLink(e, '/blog')} className="hover:text-white transition-colors">
                    Engineering Blog
                  </a>
                </li>
                <li>
                  <a href="/faq" onClick={(e) => handleLink(e, '/faq')} className="hover:text-white transition-colors">
                    FAQ
                  </a>
                </li>
                <li>
                  <a href="/privacy" onClick={(e) => handleLink(e, '/privacy')} className="hover:text-white transition-colors">
                    Privacy & GDPR
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
              </ul>
            </div>

          </div>

        </div>

        {/* Divider Line */}
        <div className="w-full border-t border-[#1C1C1C]" />

        {/* Bottom Bar: Copyright & Attribution */}
        <div className="pt-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-xs text-[#8E8E8E]">
          <div className="flex flex-wrap items-center gap-2">
            <span>&copy; {new Date().getFullYear()} VIGHNAGHNA. GPL-2.0+ Open Source.</span>
            <span className="text-neutral-700 hidden sm:inline">|</span>
            <span>
              Crafted by{' '}
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
              href="/privacy"
              onClick={(e) => handleLink(e, '/privacy')}
              className="hover:text-white transition-colors"
            >
              Zero-Tracking Policy
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

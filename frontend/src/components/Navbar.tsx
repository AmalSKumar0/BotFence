import React, { useState } from 'react';
import { Shield, Menu, X, ArrowUpRight } from 'lucide-react';

interface NavbarProps {
  currentPath: string;
  onNavigate: (path: string) => void;
}

export const Navbar: React.FC<NavbarProps> = ({ currentPath, onNavigate }) => {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const navLinks = [
    { label: 'Features', path: '/features' },
    { label: 'Docs', path: '/docs' },
    { label: 'Contact Form 7', path: '/integrations/contact-form-7' },
    { label: 'Privacy', path: '/privacy' },
    { label: 'FAQ', path: '/faq' },
    { label: 'Blog', path: '/blog' },
  ];

  const handleLinkClick = (e: React.MouseEvent<HTMLAnchorElement>, path: string) => {
    e.preventDefault();
    setMobileMenuOpen(false);
    onNavigate(path);
  };

  return (
    <header className="w-full bg-[#0A0A0A]/90 backdrop-blur-md border-b border-[#1C1C1C] sticky top-0 z-50 text-white select-none">
      <div className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 h-16 flex items-center justify-between">
        
        {/* Brand */}
        <div className="flex items-center gap-3">
          <a
            href="/"
            onClick={(e) => handleLinkClick(e, '/')}
            className="flex items-center gap-2.5 group"
          >
            <div className="w-8 h-8 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D] group-hover:border-[#FF4D6D]/40 transition-colors">
              <Shield className="w-4 h-4 stroke-[2.2]" />
            </div>
            <div className="flex items-center gap-1.5">
              <span className="font-bold tracking-[0.2em] text-sm uppercase text-white group-hover:text-neutral-200 transition-colors">
                VIGHNAGHNA
              </span>
              <span className="font-serif italic text-xs text-[#8E8E8E] hidden sm:inline">
                (विघ्नघ्न)
              </span>
            </div>
          </a>
        </div>

        {/* Desktop Navigation Links */}
        <nav className="hidden md:flex items-center gap-6 text-xs font-medium text-[#8E8E8E] tracking-wider">
          {navLinks.map((item) => {
            const isActive = currentPath === item.path;
            return (
              <a
                key={item.path}
                href={item.path}
                onClick={(e) => handleLinkClick(e, item.path)}
                className={`transition-colors py-1 hover:text-white ${
                  isActive ? 'text-white font-semibold border-b border-[#FF4D6D]' : ''
                }`}
              >
                {item.label}
              </a>
            );
          })}
        </nav>

        {/* Right Action: Download Button */}
        <div className="hidden sm:flex items-center gap-4">
          <a
            href="/download"
            onClick={(e) => handleLinkClick(e, '/download')}
            className="px-4 py-2 rounded-full border border-[#2B2B2B] bg-[#141414] hover:border-[#FF4D6D]/60 hover:text-[#FF4D6D] text-xs font-medium tracking-wider text-white transition-all cursor-pointer flex items-center gap-1.5"
          >
            <span>Download .zip</span>
            <ArrowUpRight className="w-3.5 h-3.5" />
          </a>
        </div>

        {/* Mobile Menu Button */}
        <button
          type="button"
          onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          className="md:hidden p-2 text-[#8E8E8E] hover:text-white focus:outline-none"
          aria-label="Toggle Navigation Menu"
        >
          {mobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
        </button>

      </div>

      {/* Mobile Drawer */}
      {mobileMenuOpen && (
        <div className="md:hidden bg-[#0D0D0D] border-b border-[#1C1C1C] px-6 py-6 space-y-4 animate-in fade-in duration-200">
          <nav className="flex flex-col space-y-3 text-sm">
            {navLinks.map((item) => (
              <a
                key={item.path}
                href={item.path}
                onClick={(e) => handleLinkClick(e, item.path)}
                className={`py-2 px-3 rounded-lg transition-colors ${
                  currentPath === item.path
                    ? 'bg-[#1C1C1C] text-white font-semibold'
                    : 'text-[#8E8E8E] hover:text-white hover:bg-[#141414]'
                }`}
              >
                {item.label}
              </a>
            ))}
          </nav>
          <div className="pt-2 border-t border-[#1C1C1C]">
            <a
              href="/download"
              onClick={(e) => handleLinkClick(e, '/download')}
              className="w-full py-2.5 rounded-full bg-[#1A1A1A] border border-[#2E2E2E] text-white hover:border-[#FF4D6D] text-xs font-medium flex items-center justify-center gap-2"
            >
              <span>Download Plugin (.zip)</span>
              <ArrowUpRight className="w-4 h-4" />
            </a>
          </div>
        </div>
      )}
    </header>
  );
};

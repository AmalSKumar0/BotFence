import React, { useState } from 'react';
import { Download, Shield, ArrowUpRight, Menu, X, ArrowRight } from 'lucide-react';
import { ThreeShieldScene } from './ThreeShieldScene';

interface HeroSectionProps {
  onOpenDownload: () => void;
}

export const HeroSection: React.FC<HeroSectionProps> = ({ onOpenDownload }) => {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  return (
    <section className="relative w-full bg-[#FAF9FD] overflow-hidden">
      {/* Subtle Ethereal Ambient Glow in Background */}
      <div 
        aria-hidden="true" 
        className="pointer-events-none absolute -top-40 right-1/4 w-[640px] h-[520px] rounded-full bg-gradient-to-br from-[#E0DBFF]/60 via-[#EFEAFF]/30 to-transparent blur-3xl" 
      />

      {/* 1. Serene-Style Minimalist Navbar */}
      <header className="sticky top-0 z-40 w-full bg-[#FAF9FD]/80 backdrop-blur-md border-b border-[#ECEAF5]/70 transition-all">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16 sm:h-20">
            {/* Logo */}
            <div className="flex items-center gap-2.5">
              <div className="w-9 h-9 squircle-icon bg-[#5B42F3] flex items-center justify-center text-white shadow-xs">
                <Shield className="w-5 h-5 stroke-[2.2]" />
              </div>
              <div className="flex items-baseline gap-1.5">
                <span className="font-[800] text-xl text-[#0D0C15] tracking-tight">
                  VIGHNAGHNA
                </span>
                <span className="text-[11px] font-serif text-[#7B68EE] font-medium hidden sm:inline">
                  विघ्नघ्न
                </span>
              </div>
            </div>

            {/* Desktop Navigation Links */}
            <nav className="hidden md:flex items-center gap-7">
              <a 
                href="#home" 
                className="text-xs font-medium text-[#6C6A7B] hover:text-[#0D0C15] transition-colors"
              >
                Home
              </a>
              <a 
                href="#why-vighnaghna" 
                className="text-xs font-medium text-[#6C6A7B] hover:text-[#0D0C15] transition-colors"
              >
                Why Vighnaghna
              </a>
              <a 
                href="#how-it-works" 
                className="text-xs font-medium text-[#6C6A7B] hover:text-[#0D0C15] transition-colors"
              >
                How it works
              </a>
              <a 
                href="#comparison" 
                className="text-xs font-medium text-[#6C6A7B] hover:text-[#0D0C15] transition-colors"
              >
                Comparison
              </a>
              <a 
                href="#faq" 
                className="text-xs font-medium text-[#6C6A7B] hover:text-[#0D0C15] transition-colors"
              >
                FAQ
              </a>
            </nav>

            {/* Header Right Action Pill */}
            <div className="hidden sm:flex items-center gap-3">
              <button
                type="button"
                onClick={onOpenDownload}
                className="pill-button px-5 py-2 text-xs font-semibold bg-[#5B42F3] text-white hover:bg-[#4933DC] cursor-pointer shadow-sm transition-all inline-flex items-center gap-1.5"
              >
                <span>Download Free</span>
                <ArrowRight className="w-3.5 h-3.5" />
              </button>
            </div>

            {/* Mobile Menu Button */}
            <div className="flex md:hidden items-center gap-2">
              <button
                type="button"
                onClick={onOpenDownload}
                className="pill-button px-3.5 py-1.5 text-xs font-semibold bg-[#5B42F3] text-white"
              >
                Download
              </button>
              <button
                type="button"
                onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                className="p-2 rounded-xl text-[#0D0C15] hover:bg-[#ECEAF5]"
                aria-label="Toggle navigation menu"
              >
                {isMobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
              </button>
            </div>
          </div>

          {/* Mobile Drawer */}
          {isMobileMenuOpen && (
            <div className="md:hidden py-4 border-t border-[#ECEAF5] space-y-2 text-left">
              <a
                href="#why-vighnaghna"
                onClick={() => setIsMobileMenuOpen(false)}
                className="block px-3 py-2 text-sm font-medium text-[#0D0C15] hover:bg-white rounded-xl transition-colors"
              >
                Why Vighnaghna
              </a>
              <a
                href="#how-it-works"
                onClick={() => setIsMobileMenuOpen(false)}
                className="block px-3 py-2 text-sm font-medium text-[#0D0C15] hover:bg-white rounded-xl transition-colors"
              >
                How it works
              </a>
              <a
                href="#comparison"
                onClick={() => setIsMobileMenuOpen(false)}
                className="block px-3 py-2 text-sm font-medium text-[#0D0C15] hover:bg-white rounded-xl transition-colors"
              >
                Comparison
              </a>
              <a
                href="#faq"
                onClick={() => setIsMobileMenuOpen(false)}
                className="block px-3 py-2 text-sm font-medium text-[#0D0C15] hover:bg-white rounded-xl transition-colors"
              >
                FAQ
              </a>
            </div>
          )}
        </div>
      </header>

      {/* 2. Hero Section with Massive Bold Typography and Floating Pill Badges */}
      <div id="home" className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 sm:pt-14 pb-14 sm:pb-20">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-8 items-center">
          
          {/* Left / Upper: Big Display Text */}
          <div className="lg:col-span-7 text-left space-y-4">
            
            {/* Massive Display Title */}
            <div className="relative select-none">
              
              {/* Row 1: STOP with embedded pill tags */}
              <div className="relative inline-block leading-none">
                <span className="text-6xl sm:text-8xl md:text-9xl lg:text-[116px] font-[850] text-[#0D0C15] tracking-tight uppercase block">
                  STOP
                </span>

                {/* Floating micro-badges like in reference */}
                <span className="absolute -top-3 left-4 sm:-top-4 sm:left-6 px-2.5 py-0.5 rounded-full bg-[#FFE4EC] text-[#D92662] text-[10px] sm:text-[11px] font-semibold shadow-xs flex items-center gap-1">
                  <span>🛡️</span> Zero CAPTCHA
                </span>

                <span className="absolute top-1/2 -right-2 sm:-right-8 -translate-y-1/2 px-2.5 py-0.5 rounded-full bg-[#E0F2FE] text-[#0369A1] text-[10px] sm:text-[11px] font-semibold shadow-xs flex items-center gap-1">
                  <span>🔒</span> 100% Invisible
                </span>
              </div>

              {/* Row 2: BOTS. with purple gradient */}
              <div className="relative leading-none -mt-2 sm:-mt-4">
                <span className="text-6xl sm:text-8xl md:text-9xl lg:text-[116px] font-[850] text-gradient-purple tracking-tight uppercase block">
                  BOTS.
                </span>

                <span className="absolute bottom-2 left-32 sm:left-48 px-2.5 py-0.5 rounded-full bg-[#FEF3C7] text-[#B45309] text-[10px] sm:text-[11px] font-semibold shadow-xs flex items-center gap-1">
                  <span>⚡</span> &lt; 2ms Latency
                </span>
              </div>
            </div>

          </div>

          {/* Right Hero Card: Serene Style Visual Card with 3D Core & Deflection Preview */}
          <div className="lg:col-span-5 w-full flex items-center justify-center">
            <div className="relative w-full max-w-sm rounded-[32px] bg-gradient-to-b from-[#EDE9FE]/70 via-[#FAF9FD] to-white p-5 border border-[#ECEAF5] shadow-[0_20px_50px_rgba(91,66,243,0.12)] text-left flex flex-col justify-between overflow-hidden min-h-[420px]">
              
              {/* Simulated Deflection Floating Toast */}
              <div className="space-y-2 z-10">
                <div className="bg-white/90 backdrop-blur-md rounded-2xl p-3 border border-[#ECEAF5] shadow-xs flex items-center justify-between">
                  <div className="flex items-center gap-2 text-xs">
                    <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                    <span className="font-semibold text-[#0D0C15]">Live Shield</span>
                    <span className="text-[#6C6A7B] text-[11px]">• 0 Puzzles</span>
                  </div>
                  <span className="px-2 py-0.5 bg-[#EDE9FE] text-[#5B42F3] text-[10px] font-semibold rounded-full">
                    Protected
                  </span>
                </div>

                <div className="bg-white/80 backdrop-blur-sm rounded-xl px-3 py-1.5 border border-[#ECEAF5] text-[11px] text-[#6C6A7B] flex items-center gap-1.5">
                  <span className="text-xs">⚡</span> Automated crawler neutralized on contact
                </div>
              </div>

              {/* 3D Kinetic Core Centerpiece */}
              <div className="w-full flex items-center justify-center -my-8 scale-90">
                <ThreeShieldScene />
              </div>

              {/* Bottom Card Footer with Circular Arrow CTA Button */}
              <div className="z-10 flex items-end justify-between pt-2">
                <div>
                  <div className="text-xs font-semibold text-[#0D0C15]">
                    Vighnaghna WordPress Core
                  </div>
                  <div className="text-[11px] text-[#6C6A7B]">
                    Self-hosted • No external requests
                  </div>
                </div>

                <button
                  type="button"
                  onClick={onOpenDownload}
                  aria-label="Download Vighnaghna plugin"
                  className="w-11 h-11 rounded-full bg-[#0D0C15] text-white flex items-center justify-center hover:bg-[#5B42F3] transition-colors cursor-pointer shadow-md"
                >
                  <ArrowUpRight className="w-5 h-5 stroke-[2.5]" />
                </button>
              </div>

            </div>
          </div>

        </div>

        {/* 3-Column Info & Action Row (Matching Serene layout) */}
        <div className="grid grid-cols-1 md:grid-cols-12 gap-6 pt-10 sm:pt-14 border-t border-[#ECEAF5] text-left items-center">
          
          <div className="md:col-span-4 space-y-1">
            <p className="text-xs font-semibold text-[#0D0C15]">
              Your invisible shield for WordPress forms.
            </p>
            <p className="text-xs text-[#6C6A7B] leading-relaxed">
              Eliminating automated spam and credential stuffing before touching your database.
            </p>
          </div>

          <div className="md:col-span-5 space-y-1">
            <p className="text-xs font-semibold text-[#0D0C15]">
              Zero visual puzzles. Zero third-party APIs.
            </p>
            <p className="text-xs text-[#6C6A7B] leading-relaxed">
              Secures CF7, WPForms, Gravity Forms, and WooCommerce with zero visitor friction.
            </p>
          </div>

          <div className="md:col-span-3 flex md:justify-end">
            <button
              type="button"
              onClick={onOpenDownload}
              data-testid="hero-download-btn"
              className="pill-button w-full sm:w-auto px-7 py-3.5 bg-[#0D0C15] text-white font-semibold text-xs hover:bg-[#262438] cursor-pointer shadow-md transition-all inline-flex items-center justify-center gap-2"
            >
              <span>Download Free</span>
              <ArrowRight className="w-4 h-4" />
            </button>
          </div>

        </div>
      </div>
    </section>
  );
};

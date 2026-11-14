import React, { useState } from 'react';
import { Download, Shield, Check, Menu, X } from 'lucide-react';
import { ThreeShieldScene } from './ThreeShieldScene';

interface HeroSectionProps {
  onOpenDownload: () => void;
}

export const HeroSection: React.FC<HeroSectionProps> = ({ onOpenDownload }) => {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  return (
    <section className="relative w-full bg-[#F7F1ED] overflow-hidden">
      {/* Top Header / Navigation Bar with Wine-Plum & Warm Ivory */}
      <header className="sticky top-0 z-40 w-full bg-[#F7F1ED]/90 backdrop-blur-md border-b border-[#E7DCD7] transition-all">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16 sm:h-20">
            {/* Elegant Logo Presentation */}
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 squircle-icon bg-[#38142A] flex items-center justify-center text-[#DCC9BE] shadow-xs">
                <Shield className="w-5 h-5 stroke-[2.2]" />
              </div>
              <div className="flex flex-col text-left">
                <div className="flex items-baseline gap-2">
                  <span className="font-[650] text-xl text-[#210918] tracking-tight">
                    Vighnaghna
                  </span>
                  <span className="text-xs font-serif text-[#38142A] font-medium hidden sm:inline">
                    विघ्नघ्न
                  </span>
                </div>
                <span className="text-[11px] font-medium text-[#74656C] tracking-wide">
                  Destroyer of Obstacles
                </span>
              </div>
            </div>

            {/* Desktop Navigation Links with Refined Transitions */}
            <nav className="hidden md:flex items-center gap-8">
              <a 
                href="#features" 
                className="text-sm font-medium text-[#74656C] hover:text-[#38142A] transition-colors relative py-1 hover:border-b-2 hover:border-[#DCC9BE]"
              >
                Features
              </a>
              <a 
                href="#installation" 
                className="text-sm font-medium text-[#74656C] hover:text-[#38142A] transition-colors relative py-1 hover:border-b-2 hover:border-[#DCC9BE]"
              >
                Installation
              </a>
              <a 
                href="#comparison" 
                className="text-sm font-medium text-[#74656C] hover:text-[#38142A] transition-colors relative py-1 hover:border-b-2 hover:border-[#DCC9BE]"
              >
                Comparison
              </a>
              <a 
                href="#faq" 
                className="text-sm font-medium text-[#74656C] hover:text-[#38142A] transition-colors relative py-1 hover:border-b-2 hover:border-[#DCC9BE]"
              >
                FAQ
              </a>
            </nav>

            {/* Desktop Header Action Button */}
            <div className="hidden sm:flex items-center gap-3">
              <button
                type="button"
                onClick={onOpenDownload}
                className="pill-button px-5 py-2 text-xs font-semibold bg-[#38142A] text-[#FFFFFF] hover:bg-[#4E223C] cursor-pointer shadow-xs transition-all focus:outline-none focus:ring-2 focus:ring-[#38142A] focus:ring-offset-2"
              >
                Get plugin (.zip)
              </button>
            </div>

            {/* Mobile Menu Toggle */}
            <div className="flex md:hidden items-center gap-2">
              <button
                type="button"
                onClick={onOpenDownload}
                className="pill-button px-3.5 py-1.5 text-xs font-semibold bg-[#38142A] text-[#FFFFFF] hover:bg-[#4E223C]"
              >
                Get plugin
              </button>
              <button
                type="button"
                onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                className="p-2 rounded-xl text-[#38142A] hover:bg-[#E7DCD7]/60 transition-colors"
                aria-label="Toggle navigation menu"
              >
                {isMobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
              </button>
            </div>
          </div>

          {/* Responsive Mobile Navigation Drawer */}
          {isMobileMenuOpen && (
            <div className="md:hidden py-4 border-t border-[#E7DCD7] space-y-2 text-left">
              <a
                href="#features"
                onClick={() => setIsMobileMenuOpen(false)}
                className="block px-3 py-2 text-sm font-medium text-[#210918] hover:bg-[#FFFFFF] rounded-xl transition-colors"
              >
                Features
              </a>
              <a
                href="#installation"
                onClick={() => setIsMobileMenuOpen(false)}
                className="block px-3 py-2 text-sm font-medium text-[#210918] hover:bg-[#FFFFFF] rounded-xl transition-colors"
              >
                Installation
              </a>
              <a
                href="#comparison"
                onClick={() => setIsMobileMenuOpen(false)}
                className="block px-3 py-2 text-sm font-medium text-[#210918] hover:bg-[#FFFFFF] rounded-xl transition-colors"
              >
                Comparison
              </a>
              <a
                href="#faq"
                onClick={() => setIsMobileMenuOpen(false)}
                className="block px-3 py-2 text-sm font-medium text-[#210918] hover:bg-[#FFFFFF] rounded-xl transition-colors"
              >
                FAQ
              </a>
            </div>
          )}
        </div>
      </header>

      {/* Main Hero Container */}
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-16 sm:pt-12 sm:pb-24 lg:pb-28">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
          
          {/* Left Column: Typography & Stadium Pill CTA */}
          <div className="lg:col-span-6 space-y-6 text-left">
            {/* Elegant Tag Highlight */}
            <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#FFFFFF] border border-[#E7DCD7] text-xs font-semibold text-[#38142A] shadow-xs">
              <span className="w-2 h-2 rounded-full bg-[#38142A]"></span>
              <span>Intelligent Form Protection</span>
              <span className="text-[#B49F99]">•</span>
              <span className="text-[#74656C] font-normal">WordPress 6.0+</span>
            </div>

            {/* Headline */}
            <h1 className="text-4xl sm:text-5xl md:text-6xl font-[650] tracking-tight text-[#210918] leading-[1.08]">
              Stop bots. <span className="text-[#38142A]">Protect every form.</span>
            </h1>

            {/* Subtitle */}
            <p className="text-lg sm:text-xl font-[350] text-[#74656C] max-w-xl leading-relaxed">
              Intelligent form protection. Invisible to your visitors. Completely blocks spam submissions across CF7, WPForms, Gravity Forms, and WooCommerce with zero CAPTCHA and no external requests.
            </p>

            {/* CTA Buttons Row */}
            <div className="pt-2 flex flex-wrap items-center gap-3">
              <button
                type="button"
                onClick={onOpenDownload}
                data-testid="hero-download-btn"
                className="pill-button inline-flex items-center justify-center gap-2.5 bg-[#38142A] text-[#FFFFFF] font-semibold text-base px-8 py-3.5 hover:bg-[#4E223C] cursor-pointer shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-[#38142A] focus:ring-offset-2"
              >
                <Download className="w-4 h-4 stroke-[2.2]" />
                <span>Download free plugin (.zip)</span>
              </button>
              <a
                href="#features"
                className="pill-button inline-flex items-center justify-center bg-[#FFFFFF] text-[#38142A] font-semibold text-base px-6 py-3.5 border border-[#E7DCD7] hover:border-[#DCC9BE] hover:bg-[#F7F1ED] cursor-pointer shadow-xs transition-all"
              >
                Explore features
              </a>
            </div>

            {/* Stats Bar */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-6 border-t border-[#E7DCD7]">
              <div>
                <div className="text-2xl font-[650] text-[#210918]">99.8%</div>
                <div className="text-xs text-[#74656C] mt-0.5 font-medium">Bot block rate</div>
              </div>
              <div>
                <div className="text-2xl font-[650] text-[#210918]">0</div>
                <div className="text-xs text-[#74656C] mt-0.5 font-medium">Puzzles or clicks</div>
              </div>
              <div>
                <div className="text-2xl font-[650] text-[#210918]">&lt; 2ms</div>
                <div className="text-xs text-[#74656C] mt-0.5 font-medium">Server latency</div>
              </div>
              <div>
                <div className="text-2xl font-[650] text-[#210918]">100%</div>
                <div className="text-xs text-[#74656C] mt-0.5 font-medium">GDPR compliant</div>
              </div>
            </div>

            {/* Native Form Builder Support */}
            <div className="flex flex-wrap items-center gap-x-3 gap-y-2 text-xs text-[#74656C] pt-2">
              <span className="text-[#210918] font-semibold">Auto-protects:</span>
              <span className="flex items-center gap-1 bg-[#FFFFFF] border border-[#E7DCD7] px-2.5 py-1 rounded-full text-[#38142A]">
                <Check className="w-3.5 h-3.5 text-[#38142A]" /> Contact Form 7
              </span>
              <span className="flex items-center gap-1 bg-[#FFFFFF] border border-[#E7DCD7] px-2.5 py-1 rounded-full text-[#38142A]">
                <Check className="w-3.5 h-3.5 text-[#38142A]" /> WPForms
              </span>
              <span className="flex items-center gap-1 bg-[#FFFFFF] border border-[#E7DCD7] px-2.5 py-1 rounded-full text-[#38142A]">
                <Check className="w-3.5 h-3.5 text-[#38142A]" /> Gravity Forms
              </span>
              <span className="flex items-center gap-1 bg-[#FFFFFF] border border-[#E7DCD7] px-2.5 py-1 rounded-full text-[#38142A]">
                <Check className="w-3.5 h-3.5 text-[#38142A]" /> WooCommerce
              </span>
            </div>
          </div>

          {/* Right Column: Three.js 3D Suspended Kinetic Defense Core */}
          <div className="lg:col-span-6 w-full flex items-center justify-center relative">
            <ThreeShieldScene />
          </div>

        </div>
      </div>
    </section>
  );
};

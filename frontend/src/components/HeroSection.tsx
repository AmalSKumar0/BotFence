import React from 'react';
import { Download, Shield, Check } from 'lucide-react';
import { ThreeShieldScene } from './ThreeShieldScene';

interface HeroSectionProps {
  onOpenDownload: () => void;
}

export const HeroSection: React.FC<HeroSectionProps> = ({ onOpenDownload }) => {
  return (
    <section className="relative w-full bg-[#ffffff] pt-8 pb-16 sm:pt-12 sm:pb-24 lg:pb-28 overflow-hidden">
      {/* Mobbin Brand Identifier (NO navbar as requested) */}
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mb-12 sm:mb-16">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 squircle-icon bg-[#141414] flex items-center justify-center text-white">
              <Shield className="w-5 h-5 stroke-[2.2]" />
            </div>
            <div>
              <span className="font-semibold text-lg text-[#141414] tracking-tight">
                BotFence
              </span>
              <span className="text-xs text-[#707070] ml-2 hidden sm:inline">
                WordPress Form Security
              </span>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <span className="hidden sm:inline-block px-3 py-1 rounded-full bg-[#f3f3f3] text-[#707070] text-xs font-medium">
              v1.0.0 • GPL-2.0-or-later
            </span>
            <button
              type="button"
              onClick={onOpenDownload}
              className="pill-button px-4 py-1.5 text-xs font-semibold bg-[#141414] text-[#ffffff] hover:bg-[#262626] cursor-pointer"
            >
              Get plugin
            </button>
          </div>
        </div>
      </div>

      {/* Main Hero Grid */}
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
          
          {/* Left Column: Mobbin Typography & Stadium Pill CTA */}
          <div className="lg:col-span-6 space-y-6 text-left">
            {/* Exact Headline with Mobbin 650 weight & tight line-height */}
            <h1 className="text-4xl sm:text-5xl md:text-6xl font-[650] tracking-tight text-[#141414] leading-[1.08]">
              Stop bots. Protect every form.
            </h1>

            {/* Subtitle with Mobbin 300 light weight */}
            <p className="text-lg sm:text-xl font-[300] text-[#707070] max-w-xl leading-relaxed">
              The invisible anti-bot plugin for WordPress. Completely blocks spam submissions across CF7, WPForms, Gravity Forms, and WooCommerce with zero user friction.
            </p>

            {/* Stadium Pill CTA Button that opens Modal */}
            <div className="pt-2 flex items-center">
              <button
                type="button"
                onClick={onOpenDownload}
                data-testid="hero-download-btn"
                className="pill-button inline-flex items-center justify-center gap-2.5 bg-[#141414] text-[#ffffff] font-semibold text-base px-8 py-3.5 hover:bg-[#262626] cursor-pointer shadow-none"
              >
                <Download className="w-4 h-4 stroke-[2.2]" />
                <span>Download free plugin (.zip)</span>
              </button>
            </div>

            {/* Neutral Tint Stats Bar (No shadows, hairline cards) */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-6 border-t border-[#f0f0f0]">
              <div>
                <div className="text-2xl font-[650] text-[#141414]">99.8%</div>
                <div className="text-xs text-[#707070] mt-0.5">Bot block rate</div>
              </div>
              <div>
                <div className="text-2xl font-[650] text-[#141414]">0</div>
                <div className="text-xs text-[#707070] mt-0.5">Puzzles or clicks</div>
              </div>
              <div>
                <div className="text-2xl font-[650] text-[#141414]">&lt; 2ms</div>
                <div className="text-xs text-[#707070] mt-0.5">Server latency</div>
              </div>
              <div>
                <div className="text-2xl font-[650] text-[#141414]">100%</div>
                <div className="text-xs text-[#707070] mt-0.5">GDPR compliant</div>
              </div>
            </div>

            {/* Native Form Builder Support */}
            <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-[#707070] pt-2">
              <span className="text-[#141414] font-medium">Auto-protects:</span>
              <span className="flex items-center gap-1">
                <Check className="w-3.5 h-3.5 text-[#141414]" /> Contact Form 7
              </span>
              <span className="flex items-center gap-1">
                <Check className="w-3.5 h-3.5 text-[#141414]" /> WPForms
              </span>
              <span className="flex items-center gap-1">
                <Check className="w-3.5 h-3.5 text-[#141414]" /> Gravity Forms
              </span>
              <span className="flex items-center gap-1">
                <Check className="w-3.5 h-3.5 text-[#141414]" /> WooCommerce
              </span>
            </div>
          </div>

          {/* Right Column: Three.js 3D Suspended Kinetic Core (Enlarged) */}
          <div className="lg:col-span-6 w-full flex items-center justify-center relative">
            <ThreeShieldScene />
          </div>

        </div>
      </div>
    </section>
  );
};

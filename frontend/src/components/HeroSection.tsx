import React from 'react';
import { ArrowUpRight, ArrowDown } from 'lucide-react';

interface HeroSectionProps {
  onOpenDownload: () => void;
}

export const HeroSection: React.FC<HeroSectionProps> = ({ onOpenDownload }) => {
  return (
    <section className="relative w-full min-h-[92vh] flex flex-col justify-between dark-studio-glow text-white px-6 sm:px-10 lg:px-16 pt-8 pb-16 select-none overflow-hidden">
      
      {/* 1. Top Minimalist Studio Navigation Bar */}
      <header className="w-full flex items-center justify-between text-xs tracking-wider z-20">
        
        {/* Left Sub Links */}
        <nav className="flex items-center gap-6 text-[#8E8E8E] font-medium">
          <a href="#features" className="hover:text-white transition-colors">Features</a>
          <a href="#showcase" className="hover:text-white transition-colors">Showcase</a>
        </nav>

        {/* Center: Brand Name in Tracked Minimalist Uppercase */}
        <div className="flex items-center gap-2">
          <a href="#" className="font-bold tracking-[0.25em] text-sm uppercase text-white hover:opacity-80 transition-opacity">
            VIGHNAGHNA
          </a>
          <span className="font-serif italic text-xs text-[#8E8E8E] hidden sm:inline">
            (विघ्नघ्न)
          </span>
        </div>

        {/* Right Sub Links & CTA */}
        <div className="flex items-center gap-6">
          <a href="#faq" className="text-[#8E8E8E] hover:text-white transition-colors hidden sm:inline">
            FAQ
          </a>
          <button
            type="button"
            onClick={onOpenDownload}
            data-testid="hero-download-btn"
            className="hover:text-[#FF4D6D] text-white transition-colors cursor-pointer font-medium"
          >
            Download
          </button>
        </div>

      </header>


      {/* 2. Hero Body: Micro Studio Annotation & Diagonal Arrow */}
      <div className="w-full max-w-7xl mx-auto my-auto pt-16 sm:pt-20 pb-10">
        
        {/* Top Annotation Row */}
        <div className="flex items-start justify-between pb-6 sm:pb-8">
          <div className="text-[11px] sm:text-xs text-[#8E8E8E] uppercase tracking-widest leading-relaxed">
            <div>WordPress</div>
            <div>Security</div>
          </div>

          <button
            type="button"
            onClick={onOpenDownload}
            aria-label="Download Vighnaghna Plugin"
            className="text-white hover:text-[#FF4D6D] hover:scale-110 transition-all cursor-pointer p-1"
          >
            <ArrowUpRight className="w-8 h-8 sm:w-10 sm:h-10 stroke-[2.2]" />
          </button>
        </div>

        {/* Massive Headline (Exact Match to Reference Layout) */}
        <div className="text-left space-y-1 sm:space-y-2">
          
          <div className="text-5xl sm:text-7xl md:text-8xl lg:text-[104px] font-[850] tracking-tight leading-[0.92] text-white">
            WE ARE
          </div>

          <div className="text-5xl sm:text-7xl md:text-8xl lg:text-[104px] font-[850] tracking-tight leading-[0.92] text-white">
            FULL–STACK
          </div>

          <div className="flex flex-col lg:flex-row lg:items-end justify-between gap-6 pt-1 sm:pt-2">
            
            {/* Serif Italic Accent Word */}
            <div className="font-serif italic text-5xl sm:text-7xl md:text-8xl lg:text-[104px] font-normal tracking-wide text-white leading-[0.92]">
              DEFENSE
            </div>

            {/* Micro Paragraph to the Right of Headline */}
            <div className="max-w-md text-xs sm:text-sm text-[#8E8E8E] leading-relaxed pb-2">
              The premier invisible WordPress anti-spam plugin integrating local heuristics, dynamic honeypots, and HMAC signatures to deliver best-in-class form protection.
            </div>

          </div>

        </div>

      </div>


      {/* 3. Centered Circular Rotating Stamp Badge (From Reference Image) */}
      <div className="w-full flex items-center justify-center pt-8 z-10">
        <a
          href="#features"
          aria-label="Scroll to features"
          className="relative w-28 h-28 flex items-center justify-center group cursor-pointer"
        >
          {/* Rotating Circular Text SVG */}
          <svg className="w-full h-full animate-spin-slow" viewBox="0 0 120 120">
            <defs>
              <path
                id="stampCirclePath"
                d="M 60, 60 m -44, 0 a 44,44 0 1,1 88,0 a 44,44 0 1,1 -88,0"
              />
            </defs>
            <text className="text-[8.5px] uppercase tracking-[0.24em] fill-[#8E8E8E] group-hover:fill-white transition-colors">
              <textPath href="#stampCirclePath" startOffset="0%">
                • FORM SECURITY • ZERO CAPTCHA • LOCAL HEURISTICS •
              </textPath>
            </text>
          </svg>

          {/* Center Down Arrow Circle */}
          <div className="absolute w-10 h-10 rounded-full border border-[#333333] group-hover:border-white flex items-center justify-center text-white transition-all group-hover:scale-110">
            <ArrowDown className="w-4 h-4 stroke-[2.2]" />
          </div>
        </a>
      </div>

    </section>
  );
};

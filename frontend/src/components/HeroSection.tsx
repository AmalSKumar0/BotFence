import React, { useState } from 'react';
import { Download, ArrowUpRight, ChevronLeft, ChevronRight, Search, Shield, Globe, Github } from 'lucide-react';

interface HeroSectionProps {
  onOpenDownload: () => void;
}

export const HeroSection: React.FC<HeroSectionProps> = ({ onOpenDownload }) => {
  const [activeSlide, setActiveSlide] = useState(0);

  return (
    <section className="relative w-full bg-white text-[#121110] p-4 sm:p-8 lg:p-10 select-none">
      
      {/* 1. Header Navigation Bar (Exact Editorial Layout) */}
      <header className="flex items-center justify-between pb-6 sm:pb-8 border-b border-[#ECE5DA]/60">
        
        {/* Left: Brand Identity */}
        <div className="flex items-center gap-2">
          <span className="font-[750] text-lg sm:text-xl tracking-tight text-[#121110] lowercase">
            vighnaghna:wp
          </span>
          <span className="text-[11px] font-serif text-[#7C7872] hidden sm:inline">
            (विघ्नघ्न)
          </span>
        </div>

        {/* Center: "Let's connect" + Community Social Icons */}
        <div className="hidden md:flex items-center gap-2.5 text-xs text-[#7C7872]">
          <span>Let's connect</span>
          <div className="flex items-center gap-1.5">
            <a
              href="https://wordpress.org"
              target="_blank"
              rel="noopener noreferrer"
              className="w-6 h-6 rounded-full bg-[#FAF7F2] border border-[#ECE5DA] flex items-center justify-center text-[#121110] hover:bg-[#121110] hover:text-white transition-colors"
              title="WordPress.org Plugin Directory"
            >
              <Globe className="w-3 h-3" />
            </a>
            <a
              href="https://github.com/AmalSKumar0/BotFence"
              target="_blank"
              rel="noopener noreferrer"
              className="w-6 h-6 rounded-full bg-[#FAF7F2] border border-[#ECE5DA] flex items-center justify-center text-[#121110] hover:bg-[#121110] hover:text-white transition-colors"
              title="GitHub Repository"
            >
              <Github className="w-3 h-3" />
            </a>
            <a
              href="https://amalskumar.dev"
              target="_blank"
              rel="noopener noreferrer"
              className="w-6 h-6 rounded-full bg-[#FAF7F2] border border-[#ECE5DA] flex items-center justify-center text-[#121110] hover:bg-[#121110] hover:text-white transition-colors"
              title="Developer Website"
            >
              <Shield className="w-3 h-3" />
            </a>
          </div>
        </div>

        {/* Right: Quick actions + Get Plugin Pill */}
        <div className="flex items-center gap-3">
          <a
            href="#features"
            className="w-8 h-8 rounded-full border border-[#ECE5DA] flex items-center justify-center text-[#7C7872] hover:text-[#121110] hover:border-[#121110] transition-colors"
            title="Search features"
          >
            <Search className="w-3.5 h-3.5" />
          </a>

          <button
            type="button"
            onClick={onOpenDownload}
            data-testid="hero-download-btn"
            className="pill-button border border-[#121110] px-4 sm:px-5 py-1.5 rounded-full text-xs font-semibold text-[#121110] hover:bg-[#121110] hover:text-white transition-all cursor-pointer flex items-center gap-1"
          >
            <span>+ Get Plugin</span>
          </button>
        </div>

      </header>


      {/* 2. Hero Body Grid (Left Column, Center Mosaic, Right Floating Card) */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-6 pt-8 sm:pt-12 items-start relative">
        
        {/* Left Column (Editorial Menu + Intro Text + Pagination + Card) */}
        <div className="lg:col-span-3 text-left flex flex-col justify-between space-y-8 z-10">
          
          {/* Sub Navigation Links in 2 Columns */}
          <div className="grid grid-cols-2 gap-x-4 gap-y-2 text-xs font-medium text-[#7C7872]">
            <a href="#home" className="text-[#121110] font-semibold hover:underline">Home</a>
            <a href="#faq" className="hover:text-[#121110]">FAQ</a>
            <a href="#features" className="hover:text-[#121110]">Features</a>
            <a href="#testimonials" className="hover:text-[#121110]">Testimonials</a>
            <a href="#how-it-works" className="hover:text-[#121110]">Architecture</a>
            <a href="https://amalskumar.dev" target="_blank" rel="noopener noreferrer" className="hover:text-[#121110]">About Us</a>
          </div>

          {/* Intro Text */}
          <div className="space-y-1">
            <h2 className="text-sm font-bold text-[#121110]">
              vighnaghna -
            </h2>
            <p className="text-xs text-[#7C7872] leading-relaxed">
              a companion for WordPress form protection
            </p>
          </div>

          {/* Pagination Arrow Buttons */}
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => setActiveSlide((prev) => (prev > 0 ? prev - 1 : 1))}
              aria-label="Previous slide"
              className="w-9 h-9 rounded-full border border-[#ECE5DA] flex items-center justify-center text-[#7C7872] hover:text-[#121110] hover:border-[#121110] transition-colors"
            >
              <ChevronLeft className="w-4 h-4" />
            </button>
            <button
              type="button"
              onClick={() => setActiveSlide((prev) => (prev < 1 ? prev + 1 : 0))}
              aria-label="Next slide"
              className="w-9 h-9 rounded-full border border-[#121110] flex items-center justify-center text-[#121110] hover:bg-[#121110] hover:text-white transition-colors"
            >
              <ChevronRight className="w-4 h-4" />
            </button>
          </div>

          {/* Bottom-Left Image Card Preview */}
          <div className="rounded-[24px] bg-[#FAF8F4] p-3 border border-[#EFEAE2] flex flex-col gap-3 max-w-[220px]">
            <div className="rounded-[18px] overflow-hidden h-28 w-full bg-[#EFE8DE]">
              <img
                src="/images/card-working.jpg"
                alt="Website administrator working peacefully"
                className="w-full h-full object-cover"
                loading="eager"
              />
            </div>
            <p className="text-[11px] text-[#7C7872] leading-snug">
              We offer a wide range of protections to cater to your specific WordPress form needs.
            </p>
          </div>

        </div>


        {/* Center Main Area: Giant Headline + Organic Segmented Mosaic */}
        <div className="lg:col-span-6 flex flex-col items-center text-center relative z-0">
          
          {/* Delicate 4-Pointed Sparkle Stars */}
          <span 
            aria-hidden="true" 
            className="absolute -top-4 right-10 text-xl text-[#E8A588] animate-pulse"
          >
            ✦
          </span>
          <span 
            aria-hidden="true" 
            className="absolute top-28 -left-6 text-sm text-[#E8A588]"
          >
            ✦
          </span>
          <span 
            aria-hidden="true" 
            className="absolute bottom-12 right-2 text-xs text-[#E8A588]"
          >
            ✦
          </span>

          {/* Massive Editorial Headline */}
          <div className="mb-6 relative">
            <h1 className="text-4xl sm:text-6xl md:text-7xl font-[800] tracking-tight text-[#121110] leading-none">
              YOUR FORM
            </h1>
            
            <div className="flex items-center justify-center gap-3 mt-1 sm:mt-2">
              <span className="text-4xl sm:text-6xl md:text-7xl font-serif italic font-normal text-[#121110] leading-none">
                SECURITY
              </span>

              {/* Horizontal Pill Capsule Photo (Matching Reference) */}
              <div className="h-9 sm:h-12 w-24 sm:w-32 rounded-full overflow-hidden border border-[#ECE5DA] shadow-xs shrink-0">
                <img
                  src="/images/headline-capsule.jpg"
                  alt="Minimalist architectural balance"
                  className="w-full h-full object-cover"
                  loading="eager"
                />
              </div>
            </div>

            {/* Floating Sticker Tag (Like [mental:me] in Reference) */}
            <div className="mt-2 inline-block">
              <span className="border border-[#121110] px-2.5 py-0.5 rounded-full text-[10px] font-mono font-medium text-[#121110] tracking-wider uppercase bg-white">
                vighnaghna
              </span>
            </div>
          </div>


          {/* The Iconic Organic Pebble Segmented Mosaic */}
          <div className="relative w-full max-w-[500px] aspect-[5/4] flex items-center justify-center my-2">
            <svg
              className="w-full h-full drop-shadow-sm select-none"
              viewBox="0 0 540 440"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <defs>
                {/* Composite Organic Multi-Pebble ClipPath */}
                <clipPath id="pebble-mosaic-clip">
                  {/* Top-Left Petal */}
                  <path d="M 80 40 C 130 20, 160 30, 160 80 C 160 120, 130 130, 90 130 C 50 130, 40 80, 80 40 Z" />

                  {/* Top-Center Rounded Pebble */}
                  <rect x="180" y="20" width="180" height="110" rx="40" />

                  {/* Top-Right Petal */}
                  <path d="M 460 40 C 410 20, 380 30, 380 80 C 380 120, 410 130, 450 130 C 490 130, 500 80, 460 40 Z" />

                  {/* Far-Left Vertical Pill */}
                  <rect x="15" y="150" width="55" height="130" rx="27.5" />

                  {/* Middle-Left Pebble Tile */}
                  <path d="M 90 145 C 130 145, 160 155, 160 210 C 160 265, 130 275, 90 275 C 50 275, 55 145, 90 145 Z" />

                  {/* Center Key Tile (Main Face View) */}
                  <rect x="180" y="145" width="180" height="150" rx="32" />

                  {/* Middle-Right Pebble Tile */}
                  <path d="M 450 145 C 410 145, 380 155, 380 210 C 380 265, 410 275, 450 275 C 485 275, 490 145, 450 145 Z" />

                  {/* Far-Right Vertical Pill */}
                  <rect x="470" y="150" width="55" height="130" rx="27.5" />

                  {/* Bottom-Left Petal */}
                  <path d="M 90 290 C 130 290, 160 300, 160 350 C 160 400, 120 410, 80 400 C 40 380, 50 290, 90 290 Z" />

                  {/* Bottom-Center Pebble */}
                  <rect x="180" y="310" width="180" height="110" rx="40" />

                  {/* Bottom-Right Petal */}
                  <path d="M 450 290 C 410 290, 380 300, 380 350 C 380 400, 420 410, 460 400 C 500 380, 490 290, 450 290 Z" />
                </clipPath>
              </defs>

              {/* Background Pebble Geometry Fills */}
              <g fill="#EFE8DE">
                <rect x="15" y="150" width="55" height="130" rx="27.5" />
                <rect x="470" y="150" width="55" height="130" rx="27.5" />
              </g>

              {/* Single Hero Image Sliced through the Organic Pebble Mosaic */}
              <image
                href="/images/hero-mosaic.jpg"
                x="0"
                y="0"
                width="540"
                height="440"
                preserveAspectRatio="xMidYMid slice"
                clipPath="url(#pebble-mosaic-clip)"
              />
            </svg>
          </div>

        </div>


        {/* Right Column: Floating Feature Card + Bottom CTA Callout */}
        <div className="lg:col-span-3 text-left flex flex-col justify-between h-full space-y-12 lg:space-y-0 min-h-[460px] z-10">
          
          {/* Top-Right Floating Card (Like "Individual Therapy" in Reference) */}
          <div className="rounded-[26px] bg-white p-4 border border-[#ECE5DA] shadow-[0_12px_36px_rgba(18,17,16,0.06)] max-w-[210px] space-y-2.5">
            <h3 className="text-xs font-bold text-[#121110]">
              Universal Form Guard
            </h3>
            <p className="text-[11px] text-[#7C7872] leading-snug">
              Collaborates with your trained form builders to provide invisible defense tailored to your unique circumstances.
            </p>
            <div className="rounded-xl overflow-hidden h-24 w-full bg-[#EFE8DE]">
              <img
                src="/images/card-serene.jpg"
                alt="Serene and protected environment"
                className="w-full h-full object-cover"
                loading="eager"
              />
            </div>
          </div>

          {/* Bottom-Right Callout (Like "The first lesson is free" in Reference) */}
          <div className="space-y-3 pt-6 lg:pt-0">
            <div className="text-xs font-medium text-[#121110] leading-tight">
              The plugin<br />
              is 100% free
            </div>
            <div>
              <button
                type="button"
                onClick={onOpenDownload}
                className="pill-button border border-[#121110] text-[#121110] hover:bg-[#121110] hover:text-white px-5 py-2.5 rounded-full text-xs font-semibold inline-flex items-center gap-2 cursor-pointer transition-all shadow-xs"
              >
                <span>Download .zip</span>
                <ArrowUpRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

        </div>

      </div>

    </section>
  );
};

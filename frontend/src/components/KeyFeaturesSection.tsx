import React from 'react';
import { 
  ShieldCheck, 
  Lock, 
  Zap, 
  Layers, 
  Database, 
  ArrowUpRight, 
  Check, 
  TrendingUp, 
  Sparkles,
  ArrowDown
} from 'lucide-react';

export const KeyFeaturesSection: React.FC = () => {
  return (
    <div className="w-full bg-[#FAF9FD] text-[#0D0C15]">
      
      {/* =========================================================================
          SECTION 1: "WHY VIGHNAGHNA" (Metrics & Asymmetric Bento Grid from Reference)
         ========================================================================= */}
      <section id="why-vighnaghna" className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
        
        {/* Header Row: Stat on Left, Arched Connector in Center, Big Title on Right */}
        <div className="grid grid-cols-1 md:grid-cols-12 gap-8 items-end mb-12">
          
          {/* Left Stat */}
          <div className="md:col-span-3 text-left">
            <div className="text-5xl sm:text-6xl font-[850] text-[#0D0C15] tracking-tight">
              99.8%
            </div>
            <p className="text-xs text-[#6C6A7B] mt-1 font-medium max-w-[200px]">
              Spam bot submissions intercepted every single week
            </p>
          </div>

          {/* Center Arched Connector Line with Node Badges */}
          <div className="hidden lg:flex md:col-span-5 flex-col items-center justify-center relative pb-2">
            {/* Arched SVG Curve */}
            <svg className="w-full h-16 overflow-visible" viewBox="0 0 340 60" fill="none">
              <path
                d="M 10 50 Q 170 5 330 50"
                stroke="#D6D1F0"
                strokeWidth="1.5"
                strokeDasharray="4 4"
              />
            </svg>

            {/* Pill Nodes along the arc */}
            <div className="absolute inset-0 flex items-center justify-between px-2 -top-2">
              <span className="px-2.5 py-1 rounded-full bg-white border border-[#ECEAF5] text-[10px] font-semibold text-[#5B42F3] shadow-xs flex items-center gap-1">
                <span className="w-1.5 h-1.5 rounded-full bg-[#5B42F3]" />
                zero puzzles
              </span>

              <span className="px-2.5 py-1 rounded-full bg-[#EDE9FE] border border-[#D6D1F0] text-[10px] font-semibold text-[#5B42F3] shadow-xs -mt-5 flex items-center gap-1">
                <span className="w-1.5 h-1.5 rounded-full bg-[#5B42F3]" />
                real progress
              </span>

              <span className="px-2.5 py-1 rounded-full bg-white border border-[#ECEAF5] text-[10px] font-semibold text-[#5B42F3] shadow-xs flex items-center gap-1">
                <span className="w-1.5 h-1.5 rounded-full bg-[#5B42F3]" />
                zero bloat
              </span>
            </div>
          </div>

          {/* Right Giant Section Title (Matches Reference "WHY SERENE") */}
          <div className="md:col-span-4 text-left md:text-right">
            <div className="text-5xl sm:text-6xl lg:text-7xl font-[850] text-[#0D0C15] tracking-tight leading-[0.95]">
              WHY
            </div>
            <div className="text-5xl sm:text-6xl lg:text-7xl font-[850] text-gradient-purple tracking-tight leading-[0.95]">
              VIGHNAGHNA
            </div>
          </div>

        </div>

        {/* Bento Grid: Phone Preview Card on Left + 3 Metrics Cards on Right */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          
          {/* Bento Card 1 (Left Tall Card: Clean WordPress Form Shield Preview) */}
          <div className="lg:col-span-4 rounded-[28px] bg-white border border-[#ECEAF5] p-6 text-left flex flex-col justify-between shadow-xs">
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <span className="px-3 py-1 rounded-full bg-[#0D0C15] text-white text-[10px] font-semibold tracking-wide">
                  24/7 Available
                </span>
                <span className="text-[11px] text-[#6C6A7B] font-medium">
                  Contact Form 7 Shield
                </span>
              </div>

              {/* Simulated Interactive Mobile/Device Form */}
              <div className="rounded-2xl bg-[#FAF9FD] p-4 border border-[#ECEAF5] space-y-3">
                <div className="flex items-center justify-between text-xs pb-1 border-b border-[#ECEAF5]">
                  <span className="font-semibold text-[#0D0C15]">Quick Inquiry</span>
                  <span className="text-emerald-600 font-semibold text-[11px]">● Active</span>
                </div>

                <div className="space-y-2 text-xs">
                  <div>
                    <div className="text-[10px] text-[#6C6A7B] mb-0.5 font-medium">Visitor Name</div>
                    <div className="bg-white rounded-lg px-2.5 py-1.5 border border-[#ECEAF5] text-[#0D0C15]">
                      Sarah Jenkins
                    </div>
                  </div>
                  <div>
                    <div className="text-[10px] text-[#6C6A7B] mb-0.5 font-medium">Email Address</div>
                    <div className="bg-white rounded-lg px-2.5 py-1.5 border border-[#ECEAF5] text-[#0D0C15]">
                      sarah@agency.com
                    </div>
                  </div>
                </div>

                {/* Status Indicator */}
                <div className="pt-1 flex items-center justify-between">
                  <div className="flex items-center gap-1.5 text-[11px] text-[#5B42F3] font-semibold">
                    <ShieldCheck className="w-3.5 h-3.5" />
                    Verified Human
                  </div>
                  <span className="text-[10px] text-[#6C6A7B]">0.0s delay</span>
                </div>
              </div>
            </div>

            <div className="pt-6">
              <h3 className="text-base font-bold text-[#0D0C15]">
                Invisible Verification
              </h3>
              <p className="text-xs text-[#6C6A7B] mt-1 leading-relaxed">
                Take zero seconds to verify. Real human visitors submit forms seamlessly without audio puzzles or selecting crosswalk images.
              </p>
            </div>
          </div>

          {/* Right Bento Cards Container */}
          <div className="lg:col-span-8 flex flex-col justify-between gap-6">
            
            {/* Bento Card 2 (Top Wide Card: 120,000+ Protected + Avatar Badges) */}
            <div className="rounded-[28px] bg-white border border-[#ECEAF5] p-7 text-left shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
              <div>
                <div className="flex items-center gap-2 mb-1">
                  <TrendingUp className="w-5 h-5 text-[#5B42F3]" />
                  <span className="text-xs font-semibold text-[#5B42F3]">Verified Scale</span>
                </div>
                <div className="text-4xl sm:text-5xl font-[850] text-[#0D0C15] tracking-tight">
                  120,000+
                </div>
                <p className="text-xs text-[#6C6A7B] mt-1 font-medium">
                  Form submissions cleanly protected every single week across global sites
                </p>
              </div>

              {/* Developer Avatar Chips */}
              <div className="flex items-center -space-x-2.5">
                <div className="w-10 h-10 rounded-full border-2 border-white bg-[#5B42F3] text-white text-xs font-bold flex items-center justify-center">
                  AK
                </div>
                <div className="w-10 h-10 rounded-full border-2 border-white bg-[#7B68EE] text-white text-xs font-bold flex items-center justify-center">
                  WP
                </div>
                <div className="w-10 h-10 rounded-full border-2 border-white bg-[#A594FD] text-white text-xs font-bold flex items-center justify-center">
                  CF
                </div>
                <div className="w-10 h-10 rounded-full border-2 border-white bg-[#0D0C15] text-white text-xs font-bold flex items-center justify-center">
                  +8k
                </div>
              </div>
            </div>

            {/* Bottom Row: 2 Cards (Peach Tint Card + Lavender Tint Card) */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
              
              {/* Bento Card 3: Peach Warm Tint (Matches 4.9★ Card in Reference) */}
              <div className="rounded-[28px] bg-[#FFF7F0] border border-[#FFE8D6] p-7 text-left shadow-xs flex flex-col justify-between">
                <div className="flex items-center justify-between mb-4">
                  <div className="text-4xl sm:text-5xl font-[850] text-[#0D0C15] tracking-tight">
                    5.0 ★
                  </div>
                  <Sparkles className="w-5 h-5 text-amber-500" />
                </div>
                <p className="text-xs text-[#6C6A7B] font-medium leading-relaxed">
                  Average satisfaction score from WordPress agencies, developers, and store owners.
                </p>
              </div>

              {/* Bento Card 4: Lavender Tint (Matches 96% Card in Reference) */}
              <div className="rounded-[28px] bg-[#F2EFFF] border border-[#E6E1FE] p-7 text-left shadow-xs flex flex-col justify-between relative overflow-hidden">
                <div className="flex items-center justify-between mb-4">
                  <div className="text-4xl sm:text-5xl font-[850] text-[#0D0C15] tracking-tight">
                    100%
                  </div>
                  <span className="w-8 h-8 rounded-full bg-white flex items-center justify-center text-[#5B42F3] shadow-xs">
                    <Check className="w-4 h-4 stroke-[3]" />
                  </span>
                </div>
                <p className="text-xs text-[#6C6A7B] font-medium leading-relaxed">
                  GDPR & Privacy Compliant. Zero tracking cookies, zero cloud telemetry, and zero external requests.
                </p>
              </div>

            </div>

          </div>

        </div>

      </section>


      {/* =========================================================================
          SECTION 2: "HOW IT WORKS" (Exact 3-Column Bento Architecture from Reference)
         ========================================================================= */}
      <section id="how-it-works" className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 border-t border-[#ECEAF5]">
        
        {/* Section Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12 text-left">
          <div>
            <div className="text-5xl sm:text-6xl lg:text-7xl font-[850] text-[#0D0C15] tracking-tight leading-[0.95]">
              HOW
            </div>
            <div className="text-5xl sm:text-6xl lg:text-7xl font-[850] text-gradient-purple tracking-tight leading-[0.95]">
              IT WORKS
            </div>
          </div>

          <div className="max-w-md space-y-3">
            <p className="text-xs sm:text-sm text-[#6C6A7B] leading-relaxed">
              Everything you need to eliminate form spam, bot submissions, and credential stuffing without frustrating legitimate users.
            </p>
            <div>
              <a
                href="#features"
                className="pill-button px-5 py-2 text-xs font-semibold bg-[#0D0C15] text-white hover:bg-[#242236] inline-flex items-center gap-1.5 cursor-pointer shadow-xs"
              >
                <span>Learn more</span>
                <ArrowDown className="w-3.5 h-3.5" />
              </a>
            </div>
          </div>
        </div>

        {/* 3-Column Bento Grid matching Reference Layout */}
        <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
          
          {/* Column 1 (Left 2 Cards) */}
          <div className="md:col-span-4 flex flex-col gap-6">
            
            {/* Card 1: HMAC Cryptographic Tokens */}
            <div className="rounded-[28px] bg-white border border-[#ECEAF5] p-7 text-left shadow-xs flex flex-col justify-between min-h-[220px]">
              <div>
                <div className="w-8 h-8 rounded-full bg-[#FAF9FD] border border-[#ECEAF5] flex items-center justify-center text-[#5B42F3] mb-4">
                  <Lock className="w-4 h-4" />
                </div>
                <h3 className="text-base font-bold text-[#0D0C15]">
                  HMAC Cryptographic Tokens
                </h3>
                <p className="text-xs text-[#6C6A7B] mt-1.5 leading-relaxed">
                  Every form receives a signed, single-use token bound to the form session with replay protection.
                </p>
              </div>

              {/* Micro-visual timeline */}
              <div className="pt-4 flex items-center gap-2">
                <span className="w-2.5 h-2.5 rounded-full bg-[#5B42F3]" />
                <span className="h-0.5 flex-1 bg-[#ECEAF5]" />
                <span className="w-2 h-2 rounded-full bg-[#A594FD]" />
                <span className="h-0.5 flex-1 bg-[#ECEAF5]" />
                <span className="w-2 h-2 rounded-full bg-[#5B42F3]" />
              </div>
            </div>

            {/* Card 2: Polymorphic Honeypots (Soft Lavender Card) */}
            <div className="rounded-[28px] bg-[#F2EFFF] border border-[#E6E1FE] p-7 text-left shadow-xs flex flex-col justify-between min-h-[200px]">
              <div>
                <div className="w-8 h-8 rounded-full bg-white flex items-center justify-center text-[#5B42F3] mb-4 shadow-xs">
                  <Zap className="w-4 h-4" />
                </div>
                <h3 className="text-base font-bold text-[#0D0C15]">
                  Polymorphic Honeypots
                </h3>
                <p className="text-xs text-[#6C6A7B] mt-1.5 leading-relaxed">
                  Dynamically mutated trap fields invisible to humans and screen readers. Automated scrapers fill them blindly and are dropped on arrival.
                </p>
              </div>
            </div>

          </div>

          {/* Column 2 (Center Tall Card: Multi-Signal Risk Engine) */}
          <div className="md:col-span-4 rounded-[28px] bg-gradient-to-b from-[#FAF9FD] via-white to-[#F2EFFF] border border-[#ECEAF5] p-7 text-left shadow-xs flex flex-col justify-between min-h-[460px]">
            <div>
              <div className="w-8 h-8 rounded-full bg-[#5B42F3] text-white flex items-center justify-center mb-4 shadow-xs">
                <Layers className="w-4 h-4" />
              </div>

              <h3 className="text-lg font-bold text-[#0D0C15]">
                Multi-Signal Risk Engine
              </h3>
              <p className="text-xs text-[#6C6A7B] mt-1.5 leading-relaxed">
                Scores client telemetry, keystroke micro-jitter, and synthetic headless browser signals in under 2 milliseconds.
              </p>

              {/* Security Telemetry Diagnostics Box */}
              <div className="mt-6 rounded-2xl bg-white border border-[#ECEAF5] p-4 space-y-2.5 text-xs shadow-xs">
                <div className="flex items-center justify-between pb-2 border-b border-[#ECEAF5]">
                  <span className="font-semibold text-[#0D0C15]">Heuristic Audit</span>
                  <span className="px-2 py-0.5 bg-[#ECFDF5] text-emerald-700 text-[10px] font-bold rounded-md">
                    VERIFIED
                  </span>
                </div>

                <div className="flex items-center justify-between text-[11px]">
                  <span className="text-[#6C6A7B]">Headless Browser</span>
                  <span className="font-mono text-emerald-600 font-medium">0 / 50</span>
                </div>

                <div className="flex items-center justify-between text-[11px]">
                  <span className="text-[#6C6A7B]">isTrusted Event</span>
                  <span className="font-mono text-emerald-600 font-medium">0 / 45</span>
                </div>

                <div className="flex items-center justify-between text-[11px]">
                  <span className="text-[#6C6A7B]">Submission Duration</span>
                  <span className="font-mono text-[#0D0C15]">3.42s (Natural)</span>
                </div>

                <div className="flex items-center justify-between text-[11px]">
                  <span className="text-[#6C6A7B]">Honeypot Decoy</span>
                  <span className="font-mono text-emerald-600 font-medium">Empty</span>
                </div>
              </div>
            </div>

            <div className="pt-4 flex items-center justify-between text-xs font-semibold text-[#5B42F3]">
              <span>Real-Time In-Memory Execution</span>
              <span>&lt; 2ms</span>
            </div>
          </div>

          {/* Column 3 (Right 2 Cards) */}
          <div className="md:col-span-4 flex flex-col gap-6">
            
            {/* Card 3: Universal Form Integration */}
            <div className="rounded-[28px] bg-white border border-[#ECEAF5] p-7 text-left shadow-xs flex flex-col justify-between min-h-[220px]">
              <div>
                <div className="w-8 h-8 rounded-full bg-[#FAF9FD] border border-[#ECEAF5] flex items-center justify-center text-[#5B42F3] mb-4">
                  <Database className="w-4 h-4" />
                </div>
                <h3 className="text-base font-bold text-[#0D0C15]">
                  Universal Form Integration
                </h3>
                <p className="text-xs text-[#6C6A7B] mt-1.5 leading-relaxed">
                  Hooks automatically into Contact Form 7, WPForms, Gravity Forms, Fluent Forms, and WooCommerce checkout out of the box.
                </p>
              </div>

              {/* Supported form badges */}
              <div className="pt-3 flex flex-wrap gap-1.5">
                <span className="px-2 py-0.5 rounded-full bg-[#FAF9FD] border border-[#ECEAF5] text-[10px] text-[#0D0C15] font-medium">
                  CF7
                </span>
                <span className="px-2 py-0.5 rounded-full bg-[#FAF9FD] border border-[#ECEAF5] text-[10px] text-[#0D0C15] font-medium">
                  WPForms
                </span>
                <span className="px-2 py-0.5 rounded-full bg-[#FAF9FD] border border-[#ECEAF5] text-[10px] text-[#0D0C15] font-medium">
                  Gravity
                </span>
                <span className="px-2 py-0.5 rounded-full bg-[#FAF9FD] border border-[#ECEAF5] text-[10px] text-[#0D0C15] font-medium">
                  WooCommerce
                </span>
              </div>
            </div>

            {/* Card 4: Self-Hosted Zero SaaS Calls with Arrow CTA Button */}
            <div className="rounded-[28px] bg-white border border-[#ECEAF5] p-7 text-left shadow-xs flex flex-col justify-between min-h-[200px] relative">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="text-base font-bold text-[#0D0C15]">
                    Zero External SaaS Calls
                  </h3>
                  <p className="text-xs text-[#6C6A7B] mt-1.5 leading-relaxed max-w-[210px]">
                    100% self-hosted on your server. Never breaks if external CAPTCHA cloud servers fail.
                  </p>
                </div>

                <div className="w-10 h-10 rounded-full bg-[#0D0C15] text-white flex items-center justify-center shrink-0">
                  <ArrowUpRight className="w-4 h-4 stroke-[2.5]" />
                </div>
              </div>

              <div className="pt-4 flex items-center gap-1.5 text-xs text-emerald-600 font-semibold">
                <Check className="w-3.5 h-3.5 stroke-[2.5]" />
                <span>99.99% Autonomous Uptime</span>
              </div>
            </div>

          </div>

        </div>

      </section>

    </div>
  );
};

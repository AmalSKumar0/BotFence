import React from 'react';
import { 
  Lock, 
  Zap, 
  Layers, 
  Database, 
  ArrowUpRight, 
  CheckCircle2, 
  ShieldCheck, 
  ArrowRight,
  Cpu
} from 'lucide-react';

interface KeyFeaturesSectionProps {
  onOpenDownload?: () => void;
}

const FEATURES = [
  {
    icon: Zap,
    title: 'Polymorphic Honeypots',
    desc: 'Dynamically mutated trap fields invisible to humans and screen readers. Scrapers fill them blindly and are dropped on arrival.',
  },
  {
    icon: Cpu,
    title: 'Sub-2ms Verification',
    desc: 'Real-time in-memory execution on your WordPress server. Eliminates spam before your database executes an insert.',
  },
  {
    icon: Lock,
    title: 'HMAC Cryptographic Tokens',
    desc: 'Every form receives a signed, single-use token bound to the form session with replay and timing protection.',
  },
  {
    icon: Layers,
    title: 'Multi-Signal Risk Engine',
    desc: 'Scores keystroke micro-jitter, synthetic headless browser telemetry, and event timing without invasive cookies.',
  },
  {
    icon: Database,
    title: 'Zero External SaaS',
    desc: '100% self-hosted on your server. Zero API keys, zero third-party cloud outages, and zero tracking telemetry.',
  },
  {
    icon: ShieldCheck,
    title: 'Universal Integration',
    desc: 'Hooks automatically into Contact Form 7, WPForms, Gravity Forms, Fluent Forms, and WooCommerce checkout.',
  },
];

export const KeyFeaturesSection: React.FC<KeyFeaturesSectionProps> = ({ onOpenDownload }) => {
  return (
    <div className="w-full bg-[#0A0A0A] text-white">
      
      {/* =========================================================================
          SECTION 1: "FEATURES" (Exact Layout & Typography from Reference Image)
         ========================================================================= */}
      <section id="features" className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-20 sm:py-28">
        
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-start">
          
          {/* Left Column: Giant Title + Annotation + Arrow */}
          <div className="lg:col-span-4 flex flex-col justify-between self-stretch space-y-12 text-left">
            <div>
              <h2 className="text-5xl sm:text-7xl font-[850] tracking-tight text-white uppercase leading-[0.95]">
                FEATURES
              </h2>
            </div>

            <div className="pt-8 sm:pt-16 space-y-4">
              <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed max-w-[200px]">
                Protect forms with ease. Zero CAPTCHAs, zero tracking.
              </p>
              <div>
                <button
                  type="button"
                  onClick={onOpenDownload}
                  className="w-10 h-10 rounded-full border border-[#262626] hover:border-white hover:text-[#FF4D6D] flex items-center justify-center text-white transition-all cursor-pointer"
                  title="Download Vighnaghna Plugin"
                >
                  <ArrowUpRight className="w-5 h-5 stroke-[2.2]" />
                </button>
              </div>
            </div>
          </div>

          {/* Right Column: Grid of Sleek Dark Cards */}
          <div className="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-6">
            {FEATURES.map((feat, idx) => {
              const Icon = feat.icon;
              return (
                <div
                  key={idx}
                  className="card-dark-glass p-7 sm:p-8 flex flex-col justify-between text-left group"
                >
                  <div className="space-y-4">
                    {/* Glowing Outline Icon Badge */}
                    <div className="w-9 h-9 rounded-xl bg-[#1A1A1A] border border-[#2B2B2B] group-hover:border-[#FF4D6D]/40 flex items-center justify-center text-[#FF4D6D] transition-colors">
                      <Icon className="w-4 h-4 stroke-[2.2]" />
                    </div>

                    <h3 className="text-base sm:text-lg font-bold text-white tracking-tight">
                      {feat.title}
                    </h3>

                    <p className="text-xs text-[#8E8E8E] leading-relaxed">
                      {feat.desc}
                    </p>
                  </div>
                </div>
              );
            })}
          </div>

        </div>

      </section>


      {/* =========================================================================
          SECTION 2: "OUR WORKS" (Exact 3D Visual Showcase from Reference Image)
         ========================================================================= */}
      <section id="showcase" className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-16 sm:py-24 border-t border-[#1C1C1C]">
        
        {/* Centered Serif Section Title */}
        <div className="text-center pb-12 sm:pb-16">
          <h2 className="font-serif italic text-4xl sm:text-6xl tracking-widest text-white uppercase">
            OUR WORKS
          </h2>
          <p className="text-xs sm:text-sm text-[#8E8E8E] mt-3 tracking-wider uppercase">
            Protected Ecosystems & Form Architecture
          </p>
        </div>

        {/* Visual Cards Grid */}
        <div className="space-y-6">
          
          {/* Top Large Feature Card (Ruby 3D Render) */}
          <div 
            onClick={onOpenDownload}
            className="group relative rounded-[28px] overflow-hidden border border-[#222222] bg-[#141414] aspect-[16/9] sm:aspect-[21/9] cursor-pointer hover:border-[#383838] transition-all"
          >
            <img
              src="/images/showcase-ruby.jpg"
              alt="Polygonal Ruby 3D Digital Art Render"
              className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 brightness-90"
              loading="lazy"
            />
            {/* Dark Vignette Overlay */}
            <div className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent" />

            {/* Bottom Bar Details */}
            <div className="absolute inset-x-0 bottom-0 p-6 sm:p-8 flex items-center justify-between text-white z-10">
              <span className="text-xs sm:text-sm font-medium tracking-wide">
                Contact Form 7 & WPForms / In-Memory Form Guard
              </span>
              <div className="w-8 h-8 rounded-full bg-white/10 backdrop-blur-md border border-white/20 group-hover:bg-white group-hover:text-black flex items-center justify-center transition-all">
                <ArrowRight className="w-4 h-4 stroke-[2.2]" />
              </div>
            </div>
          </div>

          {/* Bottom Row: 2 Split Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            {/* Left Card: Neon Cyber Landscape */}
            <div 
              onClick={onOpenDownload}
              className="group relative rounded-[28px] overflow-hidden border border-[#222222] bg-[#141414] aspect-[4/3] sm:aspect-[16/10] cursor-pointer hover:border-[#383838] transition-all"
            >
              <img
                src="/images/showcase-neon.jpg"
                alt="Cyber Portal 3D Digital Art Render"
                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 brightness-90"
                loading="lazy"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/25 to-transparent" />
              
              <div className="absolute inset-x-0 bottom-0 p-6 sm:p-8 flex items-center justify-between text-white z-10">
                <span className="text-xs sm:text-sm font-medium tracking-wide">
                  WooCommerce Checkout / Zero Abandonment
                </span>
                <div className="w-8 h-8 rounded-full bg-white/10 backdrop-blur-md border border-white/20 group-hover:bg-white group-hover:text-black flex items-center justify-center transition-all">
                  <ArrowRight className="w-4 h-4 stroke-[2.2]" />
                </div>
              </div>
            </div>

            {/* Right Card: Chrome Futuristic 3D Render */}
            <div 
              onClick={onOpenDownload}
              className="group relative rounded-[28px] overflow-hidden border border-[#222222] bg-[#141414] aspect-[4/3] sm:aspect-[16/10] cursor-pointer hover:border-[#383838] transition-all"
            >
              <img
                src="/images/showcase-chrome.jpg"
                alt="Chrome Futuristic 3D Digital Art Render"
                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 brightness-90"
                loading="lazy"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/25 to-transparent" />
              
              <div className="absolute inset-x-0 bottom-0 p-6 sm:p-8 flex items-center justify-between text-white z-10">
                <span className="text-xs sm:text-sm font-medium tracking-wide">
                  Gravity Forms & Fluent / Headless Scraper Defense
                </span>
                <div className="w-8 h-8 rounded-full bg-white/10 backdrop-blur-md border border-white/20 group-hover:bg-white group-hover:text-black flex items-center justify-center transition-all">
                  <ArrowRight className="w-4 h-4 stroke-[2.2]" />
                </div>
              </div>
            </div>

          </div>

        </div>

      </section>

    </div>
  );
};

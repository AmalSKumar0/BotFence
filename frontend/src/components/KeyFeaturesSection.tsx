import React from 'react';
import { 
  Lock, 
  Zap, 
  Layers, 
  Database, 
  ArrowUpRight, 
  ShieldCheck, 
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

      {/* 
        NOTE: "OUR WORKS" section hidden as requested. 
        Will be re-enabled later when other portfolio items are added.
      */}

    </div>
  );
};

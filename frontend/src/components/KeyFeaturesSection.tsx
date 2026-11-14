import React, { useRef, useState } from 'react';
import { 
  EyeOff, 
  Workflow, 
  Target, 
  Globe2, 
  Database, 
  Cpu 
} from 'lucide-react';
import { motion, useScroll, useTransform, MotionValue } from 'motion/react';

interface FeatureItem {
  id: string;
  badge: string;
  title: string;
  description: string;
  icon: React.ComponentType<{ className?: string }>;
  parallaxRange: [number, number];
  iconParallaxRange: [number, number];
}

const FEATURES: FeatureItem[] = [
  {
    id: 'zero-captcha',
    badge: 'Frictionless',
    title: 'Zero-CAPTCHA invisible defense.',
    description: 'Protect forms with client-side behavioral heuristics. Real humans submit forms without puzzles or image tests, while automated bots are intercepted silently.',
    icon: EyeOff,
    parallaxRange: [24, -24],
    iconParallaxRange: [8, -8],
  },
  {
    id: 'universal-hook',
    badge: 'Universal',
    title: 'Universal form compatibility.',
    description: 'Auto-detects and shields Contact Form 7, WPForms, Gravity Forms, Elementor Forms, Fluent Forms, and WooCommerce checkout out of the box.',
    icon: Workflow,
    parallaxRange: [-18, 18],
    iconParallaxRange: [-6, 6],
  },
  {
    id: 'honeypot-timing',
    badge: 'Heuristics',
    title: 'Polymorphic honeypots & timing.',
    description: 'Generates dynamically mutated hidden traps and evaluates submission velocity. Automated headless crawlers fill them blindly and are neutralized on contact.',
    icon: Target,
    parallaxRange: [32, -32],
    iconParallaxRange: [10, -10],
  },
  {
    id: 'reputation-matrix',
    badge: 'Firewall',
    title: 'Scraper and proxy deflection.',
    description: 'Deflects automated credential stuffing and dictionary attacks originating from commercial scraper datacenters, compromised VPN subnets, and TOR nodes.',
    icon: Globe2,
    parallaxRange: [-16, 16],
    iconParallaxRange: [-5, 5],
  },
  {
    id: 'zero-bloat',
    badge: 'Database',
    title: 'Zero database and mail bloat.',
    description: 'Malicious submissions are purged directly in-memory before they consume database row IDs or trigger expensive external transactional SMTP emails.',
    icon: Database,
    parallaxRange: [28, -28],
    iconParallaxRange: [9, -9],
  },
  {
    id: 'lightweight-privacy',
    badge: 'Privacy',
    title: 'Lightweight and privacy first.',
    description: 'No third-party tracking scripts, zero cookies, and strictly GDPR compliant. Operates entirely on your server with an imperceptible 2ms footprint.',
    icon: Cpu,
    parallaxRange: [-22, 22],
    iconParallaxRange: [-7, 7],
  },
];

interface ParallaxCardProps {
  feature: FeatureItem;
  progress: MotionValue<number>;
  isHovered: boolean;
  onHover: (id: string | null) => void;
}

const ParallaxCard: React.FC<ParallaxCardProps> = ({ feature, progress, isHovered, onHover }) => {
  const Icon = feature.icon;
  // Subtle card vertical translation mapped to viewport scroll progress
  const y = useTransform(progress, [0, 1], feature.parallaxRange);
  // Micro-parallax on the squircle icon tile for elevated optical depth
  const iconY = useTransform(progress, [0, 1], feature.iconParallaxRange);

  return (
    <motion.div
      style={{ y }}
      onMouseEnter={() => onHover(feature.id)}
      onMouseLeave={() => onHover(null)}
      className={`relative flex flex-col justify-between rounded-[24px] p-7 transition-colors duration-200 text-left border will-change-transform ${
        isHovered 
          ? 'bg-[#f3f3f3] border-transparent' 
          : 'bg-[#ffffff] border-[#f0f0f0]'
      }`}
    >
      <div>
        <div className="flex items-center justify-between mb-6">
          {/* iOS-style 30% squircle icon with micro-parallax translation */}
          <motion.div 
            style={{ y: iconY }}
            className="w-12 h-12 squircle-icon bg-[#f3f3f3] flex items-center justify-center text-[#141414] transition-transform duration-200 group-hover:scale-105"
          >
            <Icon className="w-6 h-6 stroke-[2]" />
          </motion.div>

          <span className="text-xs font-medium text-[#707070] bg-[#f3f3f3] px-3 py-1 rounded-full">
            {feature.badge}
          </span>
        </div>

        <h3 className="text-xl font-[650] text-[#141414] tracking-tight mb-2 leading-snug">
          {feature.title}
        </h3>

        <p className="text-sm font-[450] text-[#707070] leading-relaxed">
          {feature.description}
        </p>
      </div>
    </motion.div>
  );
};

export const KeyFeaturesSection: React.FC = () => {
  const [activeHoverId, setActiveHoverId] = useState<string | null>(null);
  const sectionRef = useRef<HTMLElement>(null);

  // Track scroll position across the KeyFeaturesSection viewport window
  const { scrollYProgress } = useScroll({
    target: sectionRef,
    offset: ['start end', 'end start'],
  });

  return (
    <section 
      ref={sectionRef}
      id="features"
      className="relative w-full bg-[#ffffff] py-20 sm:py-28 border-t border-[#f0f0f0] overflow-hidden"
    >
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Section Header */}
        <div className="max-w-2xl mb-16 text-left">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#f3f3f3] text-xs font-semibold text-[#141414] mb-4">
            <span>Core features</span>
          </div>
          <h2 className="text-3xl sm:text-4xl md:text-5xl font-[650] tracking-tight text-[#141414] leading-[1.13]">
            Engineered to disappear behind your content.
          </h2>
          <p className="mt-3 text-base sm:text-lg font-[300] text-[#707070] leading-relaxed">
            Vighnaghna sits quietly between your visitors and your database, keeping conversions high and spam at zero.
          </p>
        </div>

        {/* Feature Cards Grid with Subtle Parallax Dynamics */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-7 pt-4 pb-4">
          {FEATURES.map((feature) => (
            <ParallaxCard
              key={feature.id}
              feature={feature}
              progress={scrollYProgress}
              isHovered={activeHoverId === feature.id}
              onHover={setActiveHoverId}
            />
          ))}
        </div>

      </div>
    </section>
  );
};

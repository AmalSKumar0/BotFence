import React from 'react';
import { Shield, Heart, Code2, Globe, ArrowUpRight } from 'lucide-react';
import { SEO } from '../components/SEO';

interface PageProps {
  onOpenDownload: () => void;
  onNavigate: (path: string) => void;
}

export const AboutPage: React.FC<PageProps> = ({ onOpenDownload, onNavigate }) => {
  const structuredData = {
    '@context': 'https://schema.org',
    '@type': 'AboutPage',
    'name': 'About Vighnaghna & Author Amal S Kumar',
    'description': 'The story, engineering philosophy, and architectural vision behind Vighnaghna — an open-source, zero-CAPTCHA WordPress form security engine.',
    'url': 'https://vighnaghna.amalskumar.dev/about',
    'author': {
      '@type': 'Person',
      'name': 'Amal S Kumar',
      'url': 'https://amalskumar.dev'
    },
    'breadcrumb': {
      '@type': 'BreadcrumbList',
      'itemListElement': [
        { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://vighnaghna.amalskumar.dev/' },
        { '@type': 'ListItem', 'position': 2, 'name': 'About', 'item': 'https://vighnaghna.amalskumar.dev/about' }
      ]
    }
  };

  return (
    <div className="w-full bg-[#0A0A0A] text-white font-sans text-left">
      <SEO
        title="About Vighnaghna & Creator Amal S Kumar — Philosophy & Mission"
        description="Learn the story, engineering philosophy, and Sanskrit meaning behind Vighnaghna — the invisible, privacy-first form protection plugin for WordPress."
        canonicalUrl="https://vighnaghna.amalskumar.dev/about"
        structuredData={structuredData}
      />

      {/* Header */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 pt-20 pb-12">
        <div className="space-y-4 max-w-3xl">
          <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#FF4D6D] inline-block">
            Our Mission &amp; Origins
          </span>
          <h1 className="text-4xl sm:text-5xl font-[850] tracking-tight text-white leading-[1.1]">
            Destroying Form Spam Obstacles Without Compromising Privacy
          </h1>
          <p className="text-sm sm:text-base text-[#8E8E8E] leading-relaxed">
            The story, engineering philosophy, and architectural vision behind Vighnaghna — built for the independent open web by Amal S Kumar.
          </p>
        </div>
      </section>

      {/* Sanskrit Etymology */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-8">
        <div className="card-dark-glass p-8 sm:p-12 space-y-6 border border-[#2B2B2B] bg-[#121212]">
          <div className="flex items-center gap-3">
            <span className="font-serif italic text-3xl sm:text-4xl text-[#FF4D6D]">
              विघ्नघ्न
            </span>
            <span className="text-sm font-mono text-[#8E8E8E]">
              (Vighna-ghna)
            </span>
          </div>
          <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight">
            Etymology: The "Destroyer of Obstacles"
          </h2>
          <p className="text-xs sm:text-sm text-neutral-300 leading-relaxed max-w-3xl">
            In Sanskrit, <strong>Vighna</strong> (विघ्न) signifies an obstacle, impediment, or disruption, while <strong>Ghna</strong> (घ्न) denotes the destroyer or remover. For decades, spam bots have disrupted website communications, while conventional CAPTCHAs have placed frustrating obstacles in front of legitimate visitors.
          </p>
          <p className="text-xs sm:text-sm text-neutral-300 leading-relaxed max-w-3xl">
            Vighnaghna destroys both obstacles simultaneously: it neutralizes automated bot spam while eliminating puzzle friction for genuine human visitors.
          </p>
        </div>
      </section>

      {/* Core Engineering Principles */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-12 space-y-8">
        <div>
          <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#8E8E8E]">
            Foundational Tenets
          </span>
          <h2 className="text-2xl sm:text-3xl font-[850] tracking-tight text-white pt-1">
            Core Engineering Principles
          </h2>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div className="card-dark-glass p-7 sm:p-8 space-y-3">
            <h3 className="text-lg font-bold text-white">1. Zero Human Friction</h3>
            <p className="text-xs text-[#8E8E8E] leading-relaxed">
              Legitimate visitors must never be treated like suspects. Audio challenges, warped text, and image grids destroy conversion rates and frustrate users with visual or cognitive impairments. Protection must remain 100% invisible.
            </p>
          </div>

          <div className="card-dark-glass p-7 sm:p-8 space-y-3">
            <h3 className="text-lg font-bold text-white">2. Radical Self-Hosted Sovereignty</h3>
            <p className="text-xs text-[#8E8E8E] leading-relaxed">
              WordPress was built on the ethos of open data sovereignty. Plugins should not require third-party cloud accounts, external API keys, or subscriptions. Vighnaghna processes all security heuristics entirely on your server.
            </p>
          </div>

          <div className="card-dark-glass p-7 sm:p-8 space-y-3">
            <h3 className="text-lg font-bold text-white">3. Native GDPR &amp; Privacy by Design</h3>
            <p className="text-xs text-[#8E8E8E] leading-relaxed">
              We reject cross-site user fingerprinting and tracking cookies. Security signals must be ephemeral and zero-PII. IP addresses are hashed using cryptographically salted algorithms, and audit logs are subnet-masked.
            </p>
          </div>

          <div className="card-dark-glass p-7 sm:p-8 space-y-3">
            <h3 className="text-lg font-bold text-white">4. Sub-2ms Computational Efficiency</h3>
            <p className="text-xs text-[#8E8E8E] leading-relaxed">
              Security should never add page weight or server overhead. The client-side asset is under 3.2KB, and server-side verification executes in under 2 milliseconds on typical PHP hosting.
            </p>
          </div>
        </div>
      </section>

      {/* Author Section */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-12">
        <div className="card-dark-glass p-8 sm:p-12 space-y-4 border border-[#222222]">
          <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#FF4D6D]">
            Lead Engineer &amp; Maintainer
          </span>
          <h2 className="text-2xl sm:text-3xl font-[850] text-white">
            Created by Amal S Kumar
          </h2>
          <p className="text-xs sm:text-sm text-[#8E8E8E] max-w-2xl leading-relaxed">
            Amal S Kumar is a software engineer and WordPress security researcher dedicated to building privacy-respecting, high-performance open-source tools.
          </p>
          <div className="pt-2 flex items-center gap-4 text-xs font-medium">
            <a
              href="https://amalskumar.dev"
              target="_blank"
              rel="noopener noreferrer"
              className="text-white hover:text-[#FF4D6D] transition-colors inline-flex items-center gap-1.5"
            >
              <span>Personal Website (amalskumar.dev)</span>
              <ArrowUpRight className="w-3.5 h-3.5" />
            </a>
            <span className="text-neutral-700">|</span>
            <a
              href="https://github.com/AmalSKumar0"
              target="_blank"
              rel="noopener noreferrer"
              className="text-white hover:text-[#FF4D6D] transition-colors inline-flex items-center gap-1.5"
            >
              <span>GitHub Profile</span>
              <ArrowUpRight className="w-3.5 h-3.5" />
            </a>
          </div>
        </div>
      </section>
    </div>
  );
};

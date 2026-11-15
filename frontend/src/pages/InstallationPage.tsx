import React from 'react';
import { Terminal, Download, CheckCircle2, AlertCircle, ArrowRight, ShieldCheck, Database } from 'lucide-react';
import { SEO } from '../components/SEO';

interface PageProps {
  onOpenDownload: () => void;
  onNavigate: (path: string) => void;
}

export const InstallationPage: React.FC<PageProps> = ({ onOpenDownload, onNavigate }) => {
  const structuredData = {
    '@context': 'https://schema.org',
    '@type': 'HowTo',
    'name': 'How to Install and Activate Vighnaghna on WordPress',
    'description': 'Comprehensive installation guide for Vighnaghna anti-spam plugin via WordPress Admin, WP-CLI, or manual SFTP.',
    'step': [
      {
        '@type': 'HowToStep',
        'position': 1,
        'name': 'Download Release ZIP',
        'text': 'Download the official vighnaghna.zip release archive from the official website or repository.'
      },
      {
        '@type': 'HowToStep',
        'position': 2,
        'name': 'Upload via WordPress Admin',
        'text': 'Navigate to Plugins > Add New > Upload Plugin, select vighnaghna.zip, and click Install Now.'
      },
      {
        '@type': 'HowToStep',
        'position': 3,
        'name': 'Activate and Verify Tables',
        'text': 'Click Activate Plugin. Vighnaghna automatically creates custom tables, schedules cleanup crons, and begins protecting forms.'
      }
    ],
    'breadcrumb': {
      '@type': 'BreadcrumbList',
      'itemListElement': [
        { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://vighnaghna.amalskumar.dev/' },
        { '@type': 'ListItem', 'position': 2, 'name': 'Documentation', 'item': 'https://vighnaghna.amalskumar.dev/docs' },
        { '@type': 'ListItem', 'position': 3, 'name': 'Installation', 'item': 'https://vighnaghna.amalskumar.dev/docs/installation' }
      ]
    }
  };

  return (
    <div className="w-full bg-[#0A0A0A] text-white font-sans text-left">
      <SEO
        title="Installation Guide — Vighnaghna WordPress Anti-Spam Plugin"
        description="Step-by-step instructions to install and activate Vighnaghna on WordPress via Admin ZIP upload, WP-CLI, or manual deployment. Includes server prerequisites and table verification."
        canonicalUrl="https://vighnaghna.amalskumar.dev/docs/installation"
        structuredData={structuredData}
      />

      {/* Header */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 pt-20 pb-12">
        <div className="space-y-4 max-w-3xl">
          <div className="flex items-center gap-2 text-xs font-mono text-[#8E8E8E] uppercase tracking-wider">
            <a href="/docs" onClick={(e) => { e.preventDefault(); onNavigate('/docs'); }} className="hover:text-white transition-colors">
              Docs
            </a>
            <span>/</span>
            <span className="text-[#FF4D6D]">Installation</span>
          </div>
          <h1 className="text-4xl sm:text-5xl font-[850] tracking-tight text-white leading-[1.1]">
            Installing Vighnaghna
          </h1>
          <p className="text-sm sm:text-base text-[#8E8E8E] leading-relaxed">
            Follow this guide to install, activate, and verify Vighnaghna on any WordPress 6.0+ site in under 60 seconds.
          </p>
        </div>
      </section>

      {/* Prerequisites Callout */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-6">
        <div className="card-dark-glass p-6 sm:p-8 border border-[#2B2B2B] bg-[#121212] space-y-4">
          <h2 className="text-base sm:text-lg font-bold text-white flex items-center gap-2">
            <ShieldCheck className="w-5 h-5 text-[#FF4D6D]" />
            <span>Environment Prerequisites</span>
          </h2>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div className="bg-[#1A1A1A] p-4 rounded-xl border border-[#2E2E2E]">
              <div className="text-[#8E8E8E] uppercase tracking-wider text-[10px] pb-1">PHP Version</div>
              <div className="text-sm font-bold text-white font-mono">PHP 8.1 or higher</div>
              <p className="text-[11px] text-neutral-400 pt-1">Leverages native strict types and cryptographic random bytes.</p>
            </div>
            <div className="bg-[#1A1A1A] p-4 rounded-xl border border-[#2E2E2E]">
              <div className="text-[#8E8E8E] uppercase tracking-wider text-[10px] pb-1">WordPress Core</div>
              <div className="text-sm font-bold text-white font-mono">WordPress 6.0 to 6.7</div>
              <p className="text-[11px] text-neutral-400 pt-1">Tested and certified up to WordPress 6.7.</p>
            </div>
            <div className="bg-[#1A1A1A] p-4 rounded-xl border border-[#2E2E2E]">
              <div className="text-[#8E8E8E] uppercase tracking-wider text-[10px] pb-1">Database Engine</div>
              <div className="text-sm font-bold text-white font-mono">MySQL 5.7+ / MariaDB 10.3+</div>
              <p className="text-[11px] text-neutral-400 pt-1">Requires InnoDB support for atomic rate limiting.</p>
            </div>
          </div>
        </div>
      </section>

      {/* Installation Methods */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-10 space-y-12">
        
        {/* Method 1: WordPress Admin Upload */}
        <div className="space-y-4">
          <div className="flex items-center gap-2.5">
            <span className="w-6 h-6 rounded-full bg-[#1F1F1F] text-xs font-mono font-bold flex items-center justify-center text-[#FF4D6D]">
              1
            </span>
            <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight">
              Method 1: WordPress Admin Dashboard (Recommended)
            </h2>
          </div>
          <div className="card-dark-glass p-6 sm:p-8 space-y-4">
            <ol className="space-y-3 text-xs sm:text-sm text-neutral-300 list-decimal list-inside leading-relaxed">
              <li>
                Download the official <code className="text-white bg-[#1F1F1F] px-2 py-0.5 rounded">vighnaghna.zip</code> archive using the button below.
              </li>
              <li>
                Log into your WordPress Administrator dashboard (<code className="text-white bg-[#1F1F1F] px-2 py-0.5 rounded">/wp-admin</code>).
              </li>
              <li>
                Navigate to <strong className="text-white">Plugins &rarr; Add New Plugin</strong> and click the <strong className="text-white">Upload Plugin</strong> button at the top of the screen.
              </li>
              <li>
                Choose the downloaded <code className="text-white bg-[#1F1F1F] px-2 py-0.5 rounded">vighnaghna.zip</code> file and click <strong className="text-white">Install Now</strong>.
              </li>
              <li>
                Once installation completes, click <strong className="text-white">Activate Plugin</strong>.
              </li>
            </ol>
            <div className="pt-2">
              <button
                type="button"
                onClick={onOpenDownload}
                className="px-4 py-2 rounded-full bg-white text-black font-semibold text-xs tracking-wider hover:bg-neutral-200 transition-all cursor-pointer inline-flex items-center gap-1.5"
              >
                <Download className="w-3.5 h-3.5" />
                <span>Download vighnaghna.zip</span>
              </button>
            </div>
          </div>
        </div>

        {/* Method 2: WP-CLI */}
        <div className="space-y-4">
          <div className="flex items-center gap-2.5">
            <span className="w-6 h-6 rounded-full bg-[#1F1F1F] text-xs font-mono font-bold flex items-center justify-center text-[#FF4D6D]">
              2
            </span>
            <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight">
              Method 2: WP-CLI Command-Line Automation
            </h2>
          </div>
          <div className="card-dark-glass p-6 sm:p-8 space-y-4">
            <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
              For server administrators, DevOps pipelines, and staging environments with SSH access, install and activate Vighnaghna in one terminal command:
            </p>
            <pre className="bg-[#0D0D0D] p-4 rounded-xl text-xs font-mono text-neutral-300 overflow-x-auto border border-[#1F1F1F]">
{`# Install and activate directly from official production archive
wp plugin install https://vighnaghna.amalskumar.dev/vighnaghna.zip --activate

# Verify plugin status
wp plugin status vighnaghna`}
            </pre>
          </div>
        </div>

        {/* Method 3: Manual SFTP / Composer */}
        <div className="space-y-4">
          <div className="flex items-center gap-2.5">
            <span className="w-6 h-6 rounded-full bg-[#1F1F1F] text-xs font-mono font-bold flex items-center justify-center text-[#FF4D6D]">
              3
            </span>
            <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight">
              Method 3: Manual SFTP / Git Submodule
            </h2>
          </div>
          <div className="card-dark-glass p-6 sm:p-8 space-y-4">
            <ol className="space-y-3 text-xs sm:text-sm text-neutral-300 list-decimal list-inside leading-relaxed">
              <li>
                Extract <code className="text-white bg-[#1F1F1F] px-2 py-0.5 rounded">vighnaghna.zip</code> on your local workstation.
              </li>
              <li>
                Upload the entire <code className="text-white bg-[#1F1F1F] px-2 py-0.5 rounded">vighnaghna/</code> folder into your remote WordPress directory: <code className="text-white bg-[#1F1F1F] px-2 py-0.5 rounded">/wp-content/plugins/vighnaghna/</code>.
              </li>
              <li>
                In your WordPress admin, go to <strong className="text-white">Plugins &rarr; Installed Plugins</strong> and click <strong className="text-white">Activate</strong> under Vighnaghna.
              </li>
            </ol>
          </div>
        </div>

        {/* Database Verification */}
        <div className="space-y-4 pt-4">
          <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight flex items-center gap-2">
            <Database className="w-5 h-5 text-[#FF4D6D]" />
            <span>Verifying Database Tables</span>
          </h2>
          <div className="card-dark-glass p-6 sm:p-8 space-y-4">
            <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
              Upon activation, Vighnaghna creates three custom database tables utilizing WordPress core <code className="text-white font-mono">dbDelta()</code>:
            </p>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-mono">
              <div className="bg-[#111111] p-4 rounded-xl border border-[#222222]">
                <div className="text-white font-bold pb-1">&#123;prefix&#125;vighnaghna_logs</div>
                <p className="text-neutral-400 font-sans text-[11px] leading-relaxed">
                  Stores privacy-anonymized submission records with cryptographically masked IP addresses.
                </p>
              </div>
              <div className="bg-[#111111] p-4 rounded-xl border border-[#222222]">
                <div className="text-white font-bold pb-1">&#123;prefix&#125;vighnaghna_rate_limits</div>
                <p className="text-neutral-400 font-sans text-[11px] leading-relaxed">
                  Tracks request frequencies per IP hash and enforces quarantine penalties.
                </p>
              </div>
              <div className="bg-[#111111] p-4 rounded-xl border border-[#222222]">
                <div className="text-white font-bold pb-1">&#123;prefix&#125;vighnaghna_used_tokens</div>
                <p className="text-neutral-400 font-sans text-[11px] leading-relaxed">
                  Records consumed HMAC token hashes to provide atomic replay prevention.
                </p>
              </div>
            </div>
          </div>
        </div>

      </section>

      {/* Next Step Banner */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-12">
        <div className="card-dark-glass p-8 sm:p-10 flex flex-col sm:flex-row items-center justify-between gap-6 border border-[#2E2E2E]">
          <div className="space-y-1 text-center sm:text-left">
            <h3 className="text-lg font-bold text-white">Next Step: Configure Settings</h3>
            <p className="text-xs text-[#8E8E8E]">Learn how to tune risk thresholds, rate limits, and form integrations.</p>
          </div>
          <button
            type="button"
            onClick={() => onNavigate('/docs/configuration')}
            className="px-5 py-2.5 rounded-full bg-white text-black font-semibold text-xs tracking-wider hover:bg-neutral-200 transition-all cursor-pointer flex items-center gap-1.5 shrink-0"
          >
            <span>Configuration Guide</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </button>
        </div>
      </section>
    </div>
  );
};

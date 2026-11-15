import React from 'react';
import { BookOpen, Terminal, Settings, Shield, ArrowRight, Code2, Cpu, FileCode2 } from 'lucide-react';
import { SEO } from '../components/SEO';

interface PageProps {
  onOpenDownload: () => void;
  onNavigate: (path: string) => void;
}

export const DocsHubPage: React.FC<PageProps> = ({ onOpenDownload, onNavigate }) => {
  const structuredData = {
    '@context': 'https://schema.org',
    '@type': 'TechArticle',
    'headline': 'Vighnaghna Documentation & Developer Reference',
    'description': 'Official technical documentation, system architecture, lifecycle sequence, and developer hook APIs for the Vighnaghna WordPress anti-spam plugin.',
    'url': 'https://vighnaghna.amalskumar.dev/docs',
    'author': {
      '@type': 'Person',
      'name': 'Amal S Kumar',
      'url': 'https://amalskumar.dev'
    },
    'breadcrumb': {
      '@type': 'BreadcrumbList',
      'itemListElement': [
        { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://vighnaghna.amalskumar.dev/' },
        { '@type': 'ListItem', 'position': 2, 'name': 'Documentation', 'item': 'https://vighnaghna.amalskumar.dev/docs' }
      ]
    }
  };

  return (
    <div className="w-full bg-[#0A0A0A] text-white font-sans text-left">
      <SEO
        title="Documentation & Developer API Reference — Vighnaghna"
        description="Official technical documentation, architecture overview, lifecycle execution flow, and developer hook APIs for the Vighnaghna WordPress anti-spam plugin."
        canonicalUrl="https://vighnaghna.amalskumar.dev/docs"
        structuredData={structuredData}
      />

      {/* Header */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 pt-20 pb-12">
        <div className="space-y-4 max-w-3xl">
          <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#FF4D6D] inline-block">
            Developer Documentation
          </span>
          <h1 className="text-4xl sm:text-5xl font-[850] tracking-tight text-white leading-[1.1]">
            Vighnaghna Documentation &amp; Architecture
          </h1>
          <p className="text-sm sm:text-base text-[#8E8E8E] leading-relaxed">
            Everything you need to deploy, configure, fine-tune, and customize Vighnaghna across single sites, multisite networks, and headless WordPress setups.
          </p>
        </div>
      </section>

      {/* Quick Navigation Cards */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-6">
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <div
            onClick={() => onNavigate('/docs/installation')}
            className="card-dark-glass p-7 hover:border-white transition-all cursor-pointer space-y-4 group"
          >
            <div className="w-9 h-9 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D]">
              <Terminal className="w-4 h-4" />
            </div>
            <h2 className="text-lg font-bold text-white group-hover:text-[#FF4D6D] transition-colors">
              Installation Guide
            </h2>
            <p className="text-xs text-[#8E8E8E] leading-relaxed">
              Step-by-step setup via WordPress Admin ZIP, WP-CLI, or manual SSH deployment.
            </p>
            <div className="flex items-center gap-1.5 text-xs text-white font-medium pt-2">
              <span>Read guide</span>
              <ArrowRight className="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
            </div>
          </div>

          <div
            onClick={() => onNavigate('/docs/configuration')}
            className="card-dark-glass p-7 hover:border-white transition-all cursor-pointer space-y-4 group"
          >
            <div className="w-9 h-9 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D]">
              <Settings className="w-4 h-4" />
            </div>
            <h2 className="text-lg font-bold text-white group-hover:text-[#FF4D6D] transition-colors">
              Configuration Reference
            </h2>
            <p className="text-xs text-[#8E8E8E] leading-relaxed">
              Tune sensitivity thresholds, signal weights, IP rate limiting, and reverse proxy trust.
            </p>
            <div className="flex items-center gap-1.5 text-xs text-white font-medium pt-2">
              <span>View settings</span>
              <ArrowRight className="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
            </div>
          </div>

          <div
            onClick={() => onNavigate('/integrations/contact-form-7')}
            className="card-dark-glass p-7 hover:border-white transition-all cursor-pointer space-y-4 group"
          >
            <div className="w-9 h-9 rounded-xl bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center text-[#FF4D6D]">
              <FileCode2 className="w-4 h-4" />
            </div>
            <h2 className="text-lg font-bold text-white group-hover:text-[#FF4D6D] transition-colors">
              Contact Form 7 Guide
            </h2>
            <p className="text-xs text-[#8E8E8E] leading-relaxed">
              Protect Contact Form 7 without CAPTCHA and eliminate "Sending mail has been aborted" errors.
            </p>
            <div className="flex items-center gap-1.5 text-xs text-white font-medium pt-2">
              <span>Integration details</span>
              <ArrowRight className="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
            </div>
          </div>
        </div>
      </section>

      {/* System Architecture Diagram */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-12">
        <div className="card-dark-glass p-8 sm:p-12 space-y-8 border border-[#262626]">
          <div className="space-y-2">
            <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#8E8E8E]">
              Core Engine Pipeline
            </span>
            <h2 className="text-2xl sm:text-3xl font-[850] tracking-tight text-white">
              Lifecycle Execution Sequence
            </h2>
            <p className="text-xs sm:text-sm text-[#8E8E8E] max-w-2xl leading-relaxed">
              How Vighnaghna evaluates every inbound submission before WordPress persists data to MySQL.
            </p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs font-mono">
            <div className="bg-[#111111] p-5 rounded-xl border border-[#222222] space-y-2">
              <div className="text-[#FF4D6D] font-bold">STAGE 1: Ingestion</div>
              <p className="text-neutral-400 font-sans leading-relaxed">
                Hooks into form handler (<code className="text-neutral-300">wpcf7_spam</code>, <code className="text-neutral-300">wpforms_process</code>). Extracts payload, token, and client IP.
              </p>
            </div>
            <div className="bg-[#111111] p-5 rounded-xl border border-[#222222] space-y-2">
              <div className="text-[#FF4D6D] font-bold">STAGE 2: Honeypot &amp; Token</div>
              <p className="text-neutral-400 font-sans leading-relaxed">
                Inspects hidden trap fields for blind bot population. Validates HMAC-SHA256 signature, expiration age, and single-use status.
              </p>
            </div>
            <div className="bg-[#111111] p-5 rounded-xl border border-[#222222] space-y-2">
              <div className="text-[#FF4D6D] font-bold">STAGE 3: Risk Scoring</div>
              <p className="text-neutral-400 font-sans leading-relaxed">
                Aggregates points across 10 signals (elapsed time, headless flags, repeated text). Final score clamped between 0 and 100.
              </p>
            </div>
            <div className="bg-[#111111] p-5 rounded-xl border border-[#222222] space-y-2">
              <div className="text-[#FF4D6D] font-bold">STAGE 4: Enforcement</div>
              <p className="text-neutral-400 font-sans leading-relaxed">
                Score &gt;= 80 blocks delivery; &gt;= 30 challenges; &lt; 30 allows submission. Log entry created with masked IP.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Developer Hooks API */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-12">
        <div className="space-y-6">
          <div>
            <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#8E8E8E]">
              Custom Theme &amp; Plugin Development
            </span>
            <h2 className="text-2xl sm:text-3xl font-[850] tracking-tight text-white pt-1">
              Public WordPress Hook API
            </h2>
            <p className="text-xs sm:text-sm text-[#8E8E8E] max-w-2xl leading-relaxed pt-2">
              Protect bespoke HTML forms, custom AJAX endpoints, or standalone PHP handlers using Vighnaghna's public action and filter hooks.
            </p>
          </div>

          <div className="space-y-6">
            {/* Hook 1 */}
            <div className="card-dark-glass p-6 sm:p-8 space-y-4">
              <div className="flex flex-wrap items-center justify-between gap-2 border-b border-[#222222] pb-3">
                <span className="font-mono text-sm font-bold text-[#FF4D6D]">
                  apply_filters( 'vighnaghna_verify_submission', $result, $post_data, $form_id )
                </span>
                <span className="text-[11px] font-mono text-neutral-500 uppercase tracking-wider">
                  Filter Hook
                </span>
              </div>
              <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
                Evaluates form submission data through the complete multi-signal risk engine. Returns verification array with <code className="text-white">is_valid</code>, <code className="text-white">score</code>, <code className="text-white">action</code>, and user-facing <code className="text-white">error_message</code>.
              </p>
              <pre className="bg-[#0D0D0D] p-4 rounded-xl text-xs font-mono text-neutral-300 overflow-x-auto border border-[#1F1F1F]">
{`// Inside your custom form submission handler:
$post_data = wp_unslash( $_POST );
$result    = apply_filters( 'vighnaghna_verify_submission', array(), $post_data, 'my_custom_form' );

if ( ! empty( $result['is_valid'] ) ) {
    // Process submission safely
} else {
    $error = ! empty( $result['error_message'] ) ? $result['error_message'] : 'Spam verification failed.';
    wp_die( esc_html( $error ), 'Submission Error', array( 'response' => 403 ) );
}`}
              </pre>
            </div>

            {/* Hook 2 */}
            <div className="card-dark-glass p-6 sm:p-8 space-y-4">
              <div className="flex flex-wrap items-center justify-between gap-2 border-b border-[#222222] pb-3">
                <span className="font-mono text-sm font-bold text-[#FF4D6D]">
                  do_action( 'vighnaghna_render_fields', $form_id )
                </span>
                <span className="text-[11px] font-mono text-neutral-500 uppercase tracking-wider">
                  Action Hook
                </span>
              </div>
              <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
                Renders security tokens, polymorphic honeypot input tags, and telemetry fields directly into any custom form template.
              </p>
              <pre className="bg-[#0D0D0D] p-4 rounded-xl text-xs font-mono text-neutral-300 overflow-x-auto border border-[#1F1F1F]">
{`<!-- Inside your custom theme form template: -->
<form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
    <input type="text" name="user_name" placeholder="Your Name" required />
    <input type="email" name="user_email" placeholder="Your Email" required />
    
    <?php do_action( 'vighnaghna_render_fields', 'lead_form' ); ?>
    
    <button type="submit">Submit Request</button>
</form>`}
              </pre>
            </div>
          </div>
        </div>
      </section>

      {/* Footer CTA */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-16">
        <div className="card-dark-glass p-8 sm:p-12 text-center space-y-4">
          <h2 className="text-xl sm:text-2xl font-bold text-white">
            Need Help Deploying Vighnaghna?
          </h2>
          <p className="text-xs sm:text-sm text-[#8E8E8E] max-w-md mx-auto leading-relaxed">
            Consult our dedicated installation and configuration guides or check the troubleshooting checklist.
          </p>
          <div className="pt-2 flex flex-wrap items-center justify-center gap-4">
            <button
              type="button"
              onClick={() => onNavigate('/docs/installation')}
              className="px-5 py-2.5 rounded-full bg-white text-black font-semibold text-xs tracking-wider hover:bg-neutral-200 transition-all cursor-pointer"
            >
              Installation Guide
            </button>
            <button
              type="button"
              onClick={() => onNavigate('/support')}
              className="px-5 py-2.5 rounded-full border border-[#2E2E2E] bg-[#141414] text-white font-medium text-xs tracking-wider hover:border-white transition-all cursor-pointer"
            >
              Get Community Support
            </button>
          </div>
        </div>
      </section>
    </div>
  );
};

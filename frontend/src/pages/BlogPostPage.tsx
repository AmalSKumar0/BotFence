import React from 'react';
import { Calendar, Clock, User, ArrowLeft, ArrowUpRight, CheckCircle2, Shield } from 'lucide-react';
import { SEO } from '../components/SEO';
import { BLOG_POSTS } from './BlogHubPage';

interface BlogPostPageProps {
  slug: string;
  onNavigate: (path: string) => void;
  onOpenDownload: () => void;
}

export const BlogPostPage: React.FC<BlogPostPageProps> = ({ slug, onNavigate, onOpenDownload }) => {
  const postMeta = BLOG_POSTS.find((p) => p.slug === slug) || BLOG_POSTS[0];

  let articleContent = null;

  if (slug === 'how-wordpress-form-spam-works') {
    articleContent = (
      <div className="space-y-8 text-xs sm:text-sm text-neutral-300 leading-relaxed">
        <p className="text-base sm:text-lg text-white font-medium leading-relaxed">
          Form spam on WordPress is no longer driven by single scripts submitting obvious casino keywords. Today, modern form abuse is orchestrated by distributed botnets, headless browser farms, and automated credential stuffing engines.
        </p>

        <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight pt-4">
          The Anatomy of a Modern Form Attack
        </h2>
        <p>
          Automated scrapers target WordPress forms through two primary vectors: direct HTTP POST flooding and headless browser execution.
        </p>

        <h3 className="text-base sm:text-lg font-bold text-white tracking-tight pt-2">
          1. Direct HTTP POST Flooding
        </h3>
        <p>
          Scrapers crawl public WordPress sitemaps to locate form builder endpoints (such as <code className="text-white bg-[#1A1A1A] px-2 py-0.5 rounded font-mono">/wp-json/contact-form-7/v1/contact-forms/&#123;id&#125;/feedback</code> or <code className="text-white bg-[#1A1A1A] px-2 py-0.5 rounded font-mono">/wp-comments-post.php</code>).
        </p>
        <p>
          Once identified, the botnet dispatches raw HTTP POST requests directly to the handler, completely bypassing the visual HTML page. Because these bots do not execute JavaScript or render CSS, traditional visual traps or basic CSS hiding methods fail if the honeypot naming is static.
        </p>

        <h3 className="text-base sm:text-lg font-bold text-white tracking-tight pt-2">
          2. Headless Chromium Scrapers (Puppeteer &amp; Playwright)
        </h3>
        <p>
          Advanced spam campaigns leverage headless browser instances to execute client-side JavaScript, solve basic mathematical challenges, and bypass simple static token checks. However, these environments leave distinct fingerprint artifacts:
        </p>
        <ul className="list-disc list-inside space-y-2 text-[#8E8E8E] pl-2">
          <li>Presence of <code className="text-white font-mono">navigator.webdriver = true</code> in the JavaScript runtime.</li>
          <li>Synthetic event dispatch where <code className="text-white font-mono">event.isTrusted === false</code>.</li>
          <li>Unnatural mouse trajectories with zero micro-jitter or natural curved acceleration.</li>
          <li>Sub-second form completion times impossible for human readers.</li>
        </ul>

        <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight pt-6">
          Why Keyword Blocklists Fail
        </h2>
        <p>
          Historically, WordPress administrators relied on keyword blocklists in <code className="text-white font-mono">Settings &rarr; Discussion &rarr; Disallowed Comment Keys</code>. While useful for filtering known promotional phrases, keyword filtering suffers from severe flaws:
        </p>
        <ul className="list-disc list-inside space-y-2 text-[#8E8E8E] pl-2">
          <li><strong>Zero-Day Spam Text:</strong> Bots constantly mutate character homoglyphs (e.g. replacing Latin "a" with Cyrillic "а") to bypass regex filters.</li>
          <li><strong>High Server Overhead:</strong> Scanning 500-word form bodies against thousands of regex patterns on every POST request degrades server CPU.</li>
          <li><strong>False Positives on Real Inquiries:</strong> Legitimate business inquiries frequently mention words like "quote", "marketing", or "services" that over-aggressive keyword filters drop.</li>
        </ul>

        <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight pt-6">
          The Solution: Multi-Layered Heuristic Verification
        </h2>
        <p>
          Effective defense requires shifting from inspecting <em>what was said</em> (content text) to inspecting <em>how the submission was made</em> (behavioral heuristics and cryptographic integrity).
        </p>
        <p>
          By combining polymorphic honeypots with ephemeral HMAC-SHA256 tokens and client interaction vectors, <a href="/features" onClick={(e) => { e.preventDefault(); onNavigate('/features'); }} className="text-[#FF4D6D] underline">Vighnaghna's multi-signal engine</a> stops 99.8% of automated submissions before database persistence without visual puzzles.
        </p>
      </div>
    );
  } else if (slug === 'how-invisible-captcha-works') {
    articleContent = (
      <div className="space-y-8 text-xs sm:text-sm text-neutral-300 leading-relaxed">
        <p className="text-base sm:text-lg text-white font-medium leading-relaxed">
          For nearly two decades, the web has forced legitimate human beings to prove their humanity by clicking blurry fire hydrants, transcribing distorted text, or listening to garbled audio clips. Invisible CAPTCHA technology eliminates this user friction entirely.
        </p>

        <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight pt-4">
          What is Invisible Form Verification?
        </h2>
        <p>
          Invisible form protection verifies visitor authenticity passively in the background. Rather than demanding active user labor, the security engine evaluates ambient signals generated naturally during page browsing and form completion.
        </p>

        <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight pt-6">
          The Three Pillars of Invisible Verification
        </h2>

        <h3 className="text-base sm:text-lg font-bold text-white tracking-tight pt-2">
          1. Cryptographic Nonces &amp; Time Bounds
        </h3>
        <p>
          When a form renders, Vighnaghna embeds a signed single-use HMAC token:
        </p>
        <pre className="bg-[#0D0D0D] p-4 rounded-xl text-xs font-mono text-neutral-300 overflow-x-auto border border-[#1F1F1F]">
{`// Token payload format:
{
  "t": 1794653410,       // Issuance timestamp (Unix seconds)
  "f": "contact_form_7",  // Form builder binding
  "n": "8f3a9e2d1c7b"     // Cryptographic random nonce
}`}
        </pre>
        <p>
          The token is signed with the server's private secret key. Upon submission, the server verifies:
        </p>
        <ul className="list-disc list-inside space-y-1.5 text-[#8E8E8E] pl-2">
          <li><strong>Authenticity:</strong> Did our server sign this exact payload with HMAC-SHA256?</li>
          <li><strong>Age:</strong> Has the token exceeded its lifetime window (e.g., 600s)?</li>
          <li><strong>Replay:</strong> Has this nonce hash already been consumed in <code className="text-white font-mono">wp_vighnaghna_used_tokens</code>?</li>
        </ul>

        <h3 className="text-base sm:text-lg font-bold text-white tracking-tight pt-4">
          2. Polymorphic Decoy Honeypots
        </h3>
        <p>
          Honeypots exploit the programmatic nature of automated scrapers. Vighnaghna injects decoy text fields disguised with believable names (e.g. <code className="text-white font-mono">vighnaghna_field_url_website</code>).
        </p>
        <p>
          Using CSS (<code className="text-white font-mono">position: absolute; left: -9999px; opacity: 0;</code>) and W3C accessibility markup (<code className="text-white font-mono">aria-hidden="true" tabindex="-1"</code>), human visitors and screen readers never interact with the decoy. If a submission arrives with text in this field, it is 100% confirmed as an automated bot.
        </p>

        <h3 className="text-base sm:text-lg font-bold text-white tracking-tight pt-4">
          3. Micro-Interaction Heuristics
        </h3>
        <p>
          Human beings take time to read labels, position cursors, click inputs, and type characters. Vighnaghna monitors ambient browser events without collecting personal data:
        </p>
        <ul className="list-disc list-inside space-y-1.5 text-[#8E8E8E] pl-2">
          <li><strong>Elapsed Duration:</strong> Humans rarely complete a 4-field contact form in under 2.5 seconds.</li>
          <li><strong>Mouse Micro-Jitter:</strong> Real cursors move in curved vectors with continuous velocity changes.</li>
          <li><strong>Event Trust:</strong> Programmatic clicks generated by scripts feature <code className="text-white font-mono">event.isTrusted === false</code>.</li>
        </ul>

        <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight pt-6">
          Self-Hosted vs Cloud-Based Invisible CAPTCHA
        </h2>
        <p>
          While services like Google reCAPTCHA v3 or Cloudflare Turnstile also claim to be invisible, they rely on massive external data harvesting and cookies. <a href="/privacy" onClick={(e) => { e.preventDefault(); onNavigate('/privacy'); }} className="text-[#FF4D6D] underline">Vighnaghna's self-hosted architecture</a> guarantees total GDPR compliance because all signals are evaluated locally on your own WordPress hosting server.
        </p>
      </div>
    );
  } else {
    articleContent = (
      <div className="space-y-8 text-xs sm:text-sm text-neutral-300 leading-relaxed">
        <p className="text-base sm:text-lg text-white font-medium leading-relaxed">
          Nothing damages a business faster than blocking a legitimate customer inquiry. While eliminating spam is essential, anti-spam plugins must be carefully engineered to avoid false-positive rejections.
        </p>

        <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight pt-4">
          Common Causes of False-Positive Spam Blocks
        </h2>
        <p>
          Understanding why legitimate visitors get flagged is the first step toward building a bulletproof security configuration:
        </p>

        <h3 className="text-base sm:text-lg font-bold text-white tracking-tight pt-2">
          1. Cellular CGNAT &amp; Mobile Carrier IP Switching
        </h3>
        <p>
          Mobile users on 4G and 5G networks frequently switch cell towers while browsing. When a user loads a form in downtown, their IP might be <code className="text-white font-mono">203.0.113.10</code>. By the time they hit submit 3 minutes later while riding transit, their carrier routes their connection through a different gateway: <code className="text-white font-mono">198.51.100.25</code>.
        </p>
        <p>
          Older security plugins with strict IP binding reject the submission immediately. <strong>Vighnaghna resolves this with a safe IP policy:</strong> IP mismatches are recorded in diagnostic telemetry without penalizing authentic, cryptographically signed tokens.
        </p>

        <h3 className="text-base sm:text-lg font-bold text-white tracking-tight pt-4">
          2. Password Manager Autofill &amp; Clipboard Paste
        </h3>
        <p>
          Users of 1Password, Bitwarden, or browser autofill frequently populate entire forms in a single click. Similarly, job applicants or B2B prospects often paste extensive pre-written project briefs directly into textareas.
        </p>
        <p>
          Naïve behavioral scripts check for zero keystrokes and penalize the user. Vighnaghna's client telemetry specifically monitors <code className="text-white font-mono">paste</code> and <code className="text-white font-mono">autofill</code> events, ensuring users who paste long text or use assistive autofill are never penalized.
        </p>

        <h3 className="text-base sm:text-lg font-bold text-white tracking-tight pt-4">
          3. Stale Tokens from Idle Browser Tabs
        </h3>
        <p>
          Users often open a contact form in a tab, step away for a phone call, and return an hour later to submit.
        </p>
        <p>
          Instead of branding the user as a malicious attacker, Vighnaghna separates expired tokens from cryptographic tampering. The visitor is gently prompted: <em>"Your form session has expired. Please refresh the page and try again."</em> When they refresh, a fresh token is loaded without breaking the experience.
        </p>

        <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight pt-6">
          Admin Checklist: Tuning Vighnaghna for Zero False Positives
        </h2>
        <div className="card-dark-glass p-6 sm:p-8 space-y-3 border border-[#222222]">
          <div className="flex items-start gap-2.5">
            <CheckCircle2 className="w-4 h-4 text-green-400 shrink-0 mt-0.5" />
            <span><strong>Short Newsletter Forms:</strong> If your form only has 1 field (Email), reduce <code className="text-white font-mono">Minimum Submission Time</code> from 2.5s to 1.0s in General Settings.</span>
          </div>
          <div className="flex items-start gap-2.5">
            <CheckCircle2 className="w-4 h-4 text-green-400 shrink-0 mt-0.5" />
            <span><strong>Static Page Caching:</strong> Ensure <code className="text-white font-mono">vighnaghna.min.js</code> is active so cached pages dynamically fetch fresh tokens via REST API.</span>
          </div>
          <div className="flex items-start gap-2.5">
            <CheckCircle2 className="w-4 h-4 text-green-400 shrink-0 mt-0.5" />
            <span><strong>Reverse Proxies:</strong> If using Cloudflare or AWS CloudFront, enable <code className="text-white font-mono">Trust Reverse Proxies</code> under Rate Limiting to prevent shared IP rate throttling.</span>
          </div>
        </div>
      </div>
    );
  }

  const structuredData = {
    '@context': 'https://schema.org',
    '@type': 'BlogPosting',
    'headline': postMeta.title,
    'description': postMeta.excerpt,
    'datePublished': '2026-11-14T10:00:00+05:30',
    'dateModified': '2026-11-14T10:00:00+05:30',
    'url': `https://vighnaghna.amalskumar.dev/blog/${slug}`,
    'author': {
      '@type': 'Person',
      'name': 'Amal S Kumar',
      'url': 'https://amalskumar.dev'
    },
    'publisher': {
      '@type': 'Organization',
      'name': 'Vighnaghna',
      'url': 'https://vighnaghna.amalskumar.dev'
    },
    'breadcrumb': {
      '@type': 'BreadcrumbList',
      'itemListElement': [
        { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://vighnaghna.amalskumar.dev/' },
        { '@type': 'ListItem', 'position': 2, 'name': 'Blog', 'item': 'https://vighnaghna.amalskumar.dev/blog' },
        { '@type': 'ListItem', 'position': 3, 'name': postMeta.title, 'item': `https://vighnaghna.amalskumar.dev/blog/${slug}` }
      ]
    }
  };

  return (
    <div className="w-full bg-[#0A0A0A] text-white font-sans text-left">
      <SEO
        title={`${postMeta.title} — Vighnaghna`}
        description={postMeta.excerpt}
        canonicalUrl={`https://vighnaghna.amalskumar.dev/blog/${slug}`}
        ogType="article"
        structuredData={structuredData}
        articlePublishedTime="2026-11-14T10:00:00+05:30"
        articleModifiedTime="2026-11-14T10:00:00+05:30"
      />

      {/* Article Header */}
      <section className="max-w-4xl mx-auto px-6 sm:px-10 pt-20 pb-10">
        <div className="space-y-6">
          <button
            type="button"
            onClick={() => onNavigate('/blog')}
            className="flex items-center gap-1.5 text-xs text-[#8E8E8E] hover:text-white transition-colors cursor-pointer"
          >
            <ArrowLeft className="w-3.5 h-3.5" />
            <span>Back to Engineering Blog</span>
          </button>

          <div className="space-y-3">
            <span className="px-2.5 py-0.5 rounded-full bg-[#1C1C1C] border border-[#2B2B2B] text-[#FF4D6D] font-mono text-[11px] font-medium inline-block">
              {postMeta.category}
            </span>
            <h1 className="text-3xl sm:text-5xl font-[850] tracking-tight text-white leading-[1.15]">
              {postMeta.title}
            </h1>
          </div>

          <div className="flex flex-wrap items-center gap-4 text-xs text-[#8E8E8E] border-y border-[#1C1C1C] py-4">
            <span className="flex items-center gap-1.5 text-white font-medium">
              <User className="w-3.5 h-3.5 text-[#FF4D6D]" />
              <span>Amal S Kumar</span>
            </span>
            <span>•</span>
            <span className="flex items-center gap-1.5">
              <Calendar className="w-3.5 h-3.5" />
              <span>{postMeta.date}</span>
            </span>
            <span>•</span>
            <span className="flex items-center gap-1.5">
              <Clock className="w-3.5 h-3.5" />
              <span>{postMeta.readTime}</span>
            </span>
          </div>
        </div>
      </section>

      {/* Article Body */}
      <section className="max-w-4xl mx-auto px-6 sm:px-10 pb-20">
        {articleContent}

        {/* Author Bio Box */}
        <div className="mt-16 card-dark-glass p-8 border border-[#262626] space-y-3">
          <span className="text-[10px] font-mono uppercase tracking-widest text-[#FF4D6D]">
            Written by
          </span>
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-[#1C1C1C] border border-[#2E2E2E] flex items-center justify-center font-bold text-sm text-[#FF4D6D]">
              AK
            </div>
            <div>
              <div className="text-sm font-bold text-white">Amal S Kumar</div>
              <div className="text-xs text-[#8E8E8E]">Creator of Vighnaghna &amp; WordPress Security Researcher</div>
            </div>
          </div>
          <p className="text-xs text-[#8E8E8E] leading-relaxed pt-1">
            Amal builds open-source, privacy-first developer tools. Explore his work at <a href="https://amalskumar.dev" target="_blank" rel="noopener noreferrer" className="text-white underline">amalskumar.dev</a>.
          </p>
        </div>

        {/* Bottom Download CTA */}
        <div className="mt-12 text-center card-dark-glass p-8 sm:p-10 space-y-4">
          <h3 className="text-lg sm:text-xl font-bold text-white">
            Experience Invisible Protection on Your WordPress Site
          </h3>
          <p className="text-xs text-[#8E8E8E] max-w-md mx-auto">
            Deploy Vighnaghna in less than 60 seconds with zero configuration and zero external requests.
          </p>
          <button
            type="button"
            onClick={onOpenDownload}
            className="px-6 py-2.5 rounded-full bg-white text-black font-semibold text-xs tracking-wider hover:bg-neutral-200 transition-all cursor-pointer inline-flex items-center gap-1.5"
          >
            <span>Download Vighnaghna (.zip)</span>
            <ArrowUpRight className="w-3.5 h-3.5" />
          </button>
        </div>
      </section>
    </div>
  );
};

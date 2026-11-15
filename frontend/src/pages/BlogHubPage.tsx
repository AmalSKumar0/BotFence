import React from 'react';
import { ArrowRight, Calendar, Clock, User, BookOpen } from 'lucide-react';
import { SEO } from '../components/SEO';

interface PageProps {
  onOpenDownload: () => void;
  onNavigate: (path: string) => void;
}

export const BLOG_POSTS = [
  {
    slug: 'how-wordpress-form-spam-works',
    title: 'How WordPress Form Spam Works (And Why Simple Keyword Blocklists Fail)',
    excerpt: 'An architectural breakdown of modern botnet tactics: automated POST flooding, headless Chromium scrapers, and credential stuffing vectors targeting WordPress forms.',
    date: 'November 14, 2026',
    readTime: '6 min read',
    category: 'Bot Detection & Architecture'
  },
  {
    slug: 'how-invisible-captcha-works',
    title: 'How Invisible CAPTCHA Works Without Visual Puzzles or Annoying Challenges',
    excerpt: 'Demystifying zero-interaction form defense: cryptographic token signatures, mouse micro-jitter heuristics, timing thresholds, and polymorphic honeypots.',
    date: 'November 10, 2026',
    readTime: '7 min read',
    category: 'Security Engineering'
  },
  {
    slug: 'reduce-false-positive-spam-detection',
    title: 'How to Prevent False-Positive Spam Blocks in WordPress Forms',
    excerpt: 'Practical engineering guide for site administrators: handling cellular CGNAT carrier IP changes, password manager autofill, clipboard paste events, and tuning sensitivity weights.',
    date: 'November 6, 2026',
    readTime: '5 min read',
    category: 'Administrator Guide'
  }
];

export const BlogHubPage: React.FC<PageProps> = ({ onOpenDownload, onNavigate }) => {
  const structuredData = {
    '@context': 'https://schema.org',
    '@type': 'Blog',
    'name': 'Vighnaghna Engineering Blog — WordPress Form Security & Bot Defense',
    'description': 'Technical articles, research, and best practices on WordPress form security, invisible spam prevention, and privacy-first bot detection.',
    'url': 'https://vighnaghna.amalskumar.dev/blog',
    'blogPost': BLOG_POSTS.map((post) => ({
      '@type': 'BlogPosting',
      'headline': post.title,
      'description': post.excerpt,
      'datePublished': '2026-11-14',
      'url': `https://vighnaghna.amalskumar.dev/blog/${post.slug}`,
      'author': {
        '@type': 'Person',
        'name': 'Amal S Kumar',
        'url': 'https://amalskumar.dev'
      }
    })),
    'breadcrumb': {
      '@type': 'BreadcrumbList',
      'itemListElement': [
        { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://vighnaghna.amalskumar.dev/' },
        { '@type': 'ListItem', 'position': 2, 'name': 'Blog', 'item': 'https://vighnaghna.amalskumar.dev/blog' }
      ]
    }
  };

  return (
    <div className="w-full bg-[#0A0A0A] text-white font-sans text-left">
      <SEO
        title="Engineering Blog — WordPress Form Security & Bot Defense"
        description="In-depth technical guides and research on WordPress form spam prevention, invisible CAPTCHAs, headless browser detection, and false-positive reduction."
        canonicalUrl="https://vighnaghna.amalskumar.dev/blog"
        structuredData={structuredData}
      />

      {/* Header */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 pt-20 pb-12">
        <div className="space-y-4 max-w-3xl">
          <span className="text-xs font-mono uppercase tracking-[0.2em] text-[#FF4D6D] inline-block">
            Engineering Insights
          </span>
          <h1 className="text-4xl sm:text-5xl font-[850] tracking-tight text-white leading-[1.1]">
            WordPress Form Security Blog
          </h1>
          <p className="text-sm sm:text-base text-[#8E8E8E] leading-relaxed">
            Technical analysis, security research, and engineering best practices for securing WordPress forms against modern automated botnets.
          </p>
        </div>
      </section>

      {/* Article Cards Grid */}
      <section className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 py-8">
        <div className="space-y-8 max-w-4xl">
          {BLOG_POSTS.map((post) => (
            <article
              key={post.slug}
              onClick={() => onNavigate(`/blog/${post.slug}`)}
              className="card-dark-glass p-8 sm:p-10 space-y-4 border border-[#222222] hover:border-white transition-all cursor-pointer group"
            >
              <div className="flex flex-wrap items-center gap-4 text-xs text-[#8E8E8E]">
                <span className="px-2.5 py-0.5 rounded-full bg-[#1C1C1C] border border-[#2B2B2B] text-[#FF4D6D] font-mono text-[11px] font-medium">
                  {post.category}
                </span>
                <span className="flex items-center gap-1.5">
                  <Calendar className="w-3.5 h-3.5" />
                  <span>{post.date}</span>
                </span>
                <span>•</span>
                <span className="flex items-center gap-1.5">
                  <Clock className="w-3.5 h-3.5" />
                  <span>{post.readTime}</span>
                </span>
              </div>

              <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight group-hover:text-[#FF4D6D] transition-colors leading-[1.25]">
                {post.title}
              </h2>

              <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed">
                {post.excerpt}
              </p>

              <div className="flex items-center gap-1.5 text-xs text-white font-semibold pt-2">
                <span>Read technical article</span>
                <ArrowRight className="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
              </div>
            </article>
          ))}
        </div>
      </section>
    </div>
  );
};

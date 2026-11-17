import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import React from 'react';
import { renderToString } from 'react-dom/server';
import App from './src/App';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const DIST_DIR = path.resolve(__dirname, 'dist');
const BASE_URL = 'https://vighnaghna.amalskumar.dev';

interface RouteConfig {
  path: string;
  title: string;
  description: string;
  ogType?: 'website' | 'article';
  schema?: Record<string, any>;
}

const routes: RouteConfig[] = [
  {
    path: '/',
    title: 'Vighnaghna — Zero-CAPTCHA Anti-Spam Plugin for WordPress',
    description: 'Lightweight, invisible anti-spam protection for WordPress forms. No CAPTCHA, no external requests. Stop spam bots across CF7, WPForms, and WooCommerce.',
    ogType: 'website',
  },
  {
    path: '/features',
    title: 'Vighnaghna Features — 6-Layer Multi-Signal Anti-Spam Architecture',
    description: "Deep technical breakdown of Vighnaghna's multi-signal protection engine: polymorphic honeypots, HMAC-SHA256 tokens, trusted proxy handling, and zero external requests.",
    ogType: 'website',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Home', item: `${BASE_URL}/` },
        { '@type': 'ListItem', position: 2, name: 'Features', item: `${BASE_URL}/features` },
      ],
    },
  },
  {
    path: '/docs',
    title: 'Vighnaghna Documentation — WordPress Anti-Spam Architecture & API',
    description: 'Complete developer documentation for Vighnaghna. Lifecycle hooks, REST API verification endpoints, filter references, and architectural specifications.',
    ogType: 'website',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Home', item: `${BASE_URL}/` },
        { '@type': 'ListItem', position: 2, name: 'Documentation', item: `${BASE_URL}/docs` },
      ],
    },
  },
  {
    path: '/docs/installation',
    title: 'Installing Vighnaghna — Step-by-Step WordPress Deployment Guide',
    description: 'Step-by-step installation instructions for Vighnaghna: WordPress Admin upload, WP-CLI command, or SFTP deployment. System requirements and database schema verification.',
    ogType: 'website',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Home', item: `${BASE_URL}/` },
        { '@type': 'ListItem', position: 2, name: 'Documentation', item: `${BASE_URL}/docs` },
        { '@type': 'ListItem', position: 3, name: 'Installation', item: `${BASE_URL}/docs/installation` },
      ],
    },
  },
  {
    path: '/docs/configuration',
    title: 'Configuring Vighnaghna — Complete Administrator & Security Manual',
    description: 'In-depth settings reference for Vighnaghna: token lifetime windows, risk scoring threshold tuning, trusted reverse proxy CIDR configuration, and audit logging.',
    ogType: 'website',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Home', item: `${BASE_URL}/` },
        { '@type': 'ListItem', position: 2, name: 'Documentation', item: `${BASE_URL}/docs` },
        { '@type': 'ListItem', position: 3, name: 'Configuration', item: `${BASE_URL}/docs/configuration` },
      ],
    },
  },
  {
    path: '/integrations/contact-form-7',
    title: 'Contact Form 7 Anti-Spam Integration Guide — Zero CAPTCHA Protection',
    description: "Comprehensive guide to protecting Contact Form 7 without reCAPTCHA or Turnstile. Eliminates 'Sending mail has been aborted' errors, supports AJAX and REST API.",
    ogType: 'website',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Home', item: `${BASE_URL}/` },
        { '@type': 'ListItem', position: 2, name: 'Integrations', item: `${BASE_URL}/docs` },
        { '@type': 'ListItem', position: 3, name: 'Contact Form 7', item: `${BASE_URL}/integrations/contact-form-7` },
      ],
    },
  },
  {
    path: '/privacy',
    title: 'Privacy & Security Architecture — Vighnaghna WordPress Anti-Spam',
    description: 'Detailed privacy specification: zero external HTTP requests, zero tracking cookies, SHA-256 IP hashing, subnet masking, and GDPR/CCPA compliance verification.',
    ogType: 'website',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Home', item: `${BASE_URL}/` },
        { '@type': 'ListItem', position: 2, name: 'Privacy & Security', item: `${BASE_URL}/privacy` },
      ],
    },
  },
  {
    path: '/faq',
    title: 'Frequently Asked Questions — Vighnaghna WordPress Anti-Spam',
    description: 'Answers to technical and operational questions regarding Vighnaghna: false-positive handling, caching compatibility, Cloudflare CDN setup, and multi-site support.',
    ogType: 'website',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'FAQPage',
      mainEntity: [
        {
          '@type': 'Question',
          name: 'Does Vighnaghna require external API keys or cloud accounts?',
          acceptedAnswer: {
            '@type': 'Answer',
            text: 'No. Vighnaghna runs entirely locally on your WordPress host. It requires zero API keys, no Google or Cloudflare accounts, and makes zero outbound HTTP requests.',
          },
        },
        {
          '@type': 'Question',
          name: 'What happens if a legitimate visitor has JavaScript disabled?',
          acceptedAnswer: {
            '@type': 'Answer',
            text: 'Vighnaghna includes server-side fallback evaluation. For standard forms, static honeypot fields and IP-based rate limiting continue to protect the form even without JavaScript.',
          },
        },
        {
          '@type': 'Question',
          name: 'How does Vighnaghna handle full-page caching (WP Rocket, LiteSpeed, Cloudflare)?',
          acceptedAnswer: {
            '@type': 'Answer',
            text: 'Vighnaghna provides an asynchronous token refresh endpoint via standard WordPress REST API (/wp-json/vighnaghna/v1/token) so static cached pages fetch fresh HMAC tokens dynamically without purging the cache.',
          },
        },
      ],
    },
  },
  {
    path: '/changelog',
    title: 'Changelog & Release Notes — Vighnaghna WordPress Plugin',
    description: 'Official changelog and version history for Vighnaghna WordPress anti-spam plugin. Security enhancements, compatibility updates, and database migration improvements.',
    ogType: 'website',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Home', item: `${BASE_URL}/` },
        { '@type': 'ListItem', position: 2, name: 'Changelog', item: `${BASE_URL}/changelog` },
      ],
    },
  },
  {
    path: '/support',
    title: 'Support & Troubleshooting — Vighnaghna WordPress Anti-Spam',
    description: 'Technical diagnostics, troubleshooting guides for reverse proxies and caching plugins, false-positive dispute steps, and community support channels.',
    ogType: 'website',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Home', item: `${BASE_URL}/` },
        { '@type': 'ListItem', position: 2, name: 'Support', item: `${BASE_URL}/support` },
      ],
    },
  },
  {
    path: '/about',
    title: 'About Vighnaghna & Amal S Kumar — The Anti-Spam Story',
    description: 'The story behind Vighnaghna (विघ्नघ्न), an open-source privacy-first WordPress security plugin engineered by Amal S Kumar. Core philosophy and development roadmap.',
    ogType: 'website',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Home', item: `${BASE_URL}/` },
        { '@type': 'ListItem', position: 2, name: 'About', item: `${BASE_URL}/about` },
      ],
    },
  },
  {
    path: '/blog',
    title: 'Vighnaghna Engineering Blog — WordPress Security & Spam Architecture',
    description: 'Technical essays and architectural guides on bot detection heuristics, cryptographic form tokens, Contact Form 7 security, and zero-CAPTCHA verification.',
    ogType: 'website',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Home', item: `${BASE_URL}/` },
        { '@type': 'ListItem', position: 2, name: 'Blog', item: `${BASE_URL}/blog` },
      ],
    },
  },
  {
    path: '/blog/how-wordpress-form-spam-works',
    title: 'How WordPress Form Spam Works: Anatomy of Automated Bot Attacks',
    description: 'A technical deep-dive into how automated headless bots target WordPress forms, bypass basic honeypots, weaponize CF7, and how to defend without visual CAPTCHAs.',
    ogType: 'article',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'BlogPosting',
      headline: 'How WordPress Form Spam Works: Anatomy of Automated Bot Attacks',
      description: 'A technical deep-dive into how automated headless bots target WordPress forms, bypass basic honeypots, weaponize CF7, and how to defend without visual CAPTCHAs.',
      datePublished: '2026-11-10T08:00:00Z',
      dateModified: '2026-11-14T10:00:00Z',
      author: {
        '@type': 'Person',
        name: 'Amal S Kumar',
        url: 'https://amalskumar.dev',
      },
      publisher: {
        '@type': 'Person',
        name: 'Amal S Kumar',
        url: 'https://amalskumar.dev',
      },
      mainEntityOfPage: {
        '@type': 'WebPage',
        '@id': `${BASE_URL}/blog/how-wordpress-form-spam-works`,
      },
    },
  },
  {
    path: '/blog/how-invisible-captcha-works',
    title: 'How Invisible CAPTCHA Works: The Mathematics of Bot Detection',
    description: 'An engineering analysis of invisible bot detection: HMAC token lifecycles, DOM interaction micro-heuristics, and why visual puzzles are obsolete in 2026.',
    ogType: 'article',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'BlogPosting',
      headline: 'How Invisible CAPTCHA Works: The Mathematics of Bot Detection',
      description: 'An engineering analysis of invisible bot detection: HMAC token lifecycles, DOM interaction micro-heuristics, and why visual puzzles are obsolete in 2026.',
      datePublished: '2026-11-12T09:00:00Z',
      dateModified: '2026-11-14T10:00:00Z',
      author: {
        '@type': 'Person',
        name: 'Amal S Kumar',
        url: 'https://amalskumar.dev',
      },
      publisher: {
        '@type': 'Person',
        name: 'Amal S Kumar',
        url: 'https://amalskumar.dev',
      },
      mainEntityOfPage: {
        '@type': 'WebPage',
        '@id': `${BASE_URL}/blog/how-invisible-captcha-works`,
      },
    },
  },
  {
    path: '/blog/reduce-false-positive-spam-detection',
    title: 'How to Eliminate False-Positive Spam Blocks on WordPress Forms',
    description: 'Best practices for tuning bot detection thresholds, accommodating screen readers, handling autofill tools, and providing safe retry paths for expired tokens.',
    ogType: 'article',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'BlogPosting',
      headline: 'How to Eliminate False-Positive Spam Blocks on WordPress Forms',
      description: 'Best practices for tuning bot detection thresholds, accommodating screen readers, handling autofill tools, and providing safe retry paths for expired tokens.',
      datePublished: '2026-11-14T07:30:00Z',
      dateModified: '2026-11-14T10:00:00Z',
      author: {
        '@type': 'Person',
        name: 'Amal S Kumar',
        url: 'https://amalskumar.dev',
      },
      publisher: {
        '@type': 'Person',
        name: 'Amal S Kumar',
        url: 'https://amalskumar.dev',
      },
      mainEntityOfPage: {
        '@type': 'WebPage',
        '@id': `${BASE_URL}/blog/reduce-false-positive-spam-detection`,
      },
    },
  },
  {
    path: '/download',
    title: 'Download Vighnaghna — Official Release Package (v1.0.2)',
    description: 'Download the official open-source Vighnaghna WordPress anti-spam plugin package (v1.0.2). SHA-256 verified, GPL-2.0+ licensed, zero external dependencies.',
    ogType: 'website',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Home', item: `${BASE_URL}/` },
        { '@type': 'ListItem', position: 2, name: 'Download', item: `${BASE_URL}/download` },
      ],
    },
  },
];

function prerender() {
  const templatePath = path.join(DIST_DIR, 'index.html');
  if (!fs.existsSync(templatePath)) {
    console.error('Template dist/index.html not found! Run "vite build" first.');
    process.exit(1);
  }

  const rawTemplate = fs.readFileSync(templatePath, 'utf8');

  console.log(`Starting static pre-rendering for ${routes.length} canonical routes...`);

  for (const route of routes) {
    const canonicalUrl = `${BASE_URL}${route.path === '/' ? '/' : route.path}`;
    const ogType = route.ogType || 'website';

    // 1. Render React tree to static HTML string
    const appHtml = renderToString(React.createElement(App as React.ComponentType<{ initialPath?: string }>, { initialPath: route.path }));

    // 2. Clone base template and replace meta headers
    let html = rawTemplate;

    // Title
    html = html.replace(/<title>.*?<\/title>/i, `<title>${route.title}</title>`);
    html = html.replace(/<meta name="title" content=".*?" \/>/i, `<meta name="title" content="${route.title}" />`);

    // Description
    html = html.replace(/<meta name="description" content=".*?" \/>/i, `<meta name="description" content="${route.description}" />`);

    // Canonical
    html = html.replace(/<link rel="canonical" href=".*?" \/>/i, `<link rel="canonical" href="${canonicalUrl}" />`);

    // Open Graph
    html = html.replace(/<meta property="og:title" content=".*?" \/>/i, `<meta property="og:title" content="${route.title}" />`);
    html = html.replace(/<meta property="og:description" content=".*?" \/>/i, `<meta property="og:description" content="${route.description}" />`);
    html = html.replace(/<meta property="og:url" content=".*?" \/>/i, `<meta property="og:url" content="${canonicalUrl}" />`);
    html = html.replace(/<meta property="og:type" content=".*?" \/>/i, `<meta property="og:type" content="${ogType}" />`);

    // Twitter
    html = html.replace(/<meta name="twitter:title" content=".*?" \/>/i, `<meta name="twitter:title" content="${route.title}" />`);
    html = html.replace(/<meta name="twitter:description" content=".*?" \/>/i, `<meta name="twitter:description" content="${route.description}" />`);
    html = html.replace(/<meta name="twitter:url" content=".*?" \/>/i, `<meta name="twitter:url" content="${canonicalUrl}" />`);

    // Dynamic Schema Injection
    if (route.schema) {
      const schemaScript = `\n    <script type="application/ld+json" id="vighnaghna-page-ld">\n    ${JSON.stringify(route.schema, null, 2).replace(/\n/g, '\n    ')}\n    </script>`;
      html = html.replace('</head>', `${schemaScript}\n  </head>`);
    }

    // 3. Inject Pre-rendered App into #root
    html = html.replace('<div id="root"></div>', `<div id="root">${appHtml}</div>`);

    // 4. Write output file
    if (route.path === '/') {
      fs.writeFileSync(templatePath, html, 'utf8');
      console.log(`✓ Pre-rendered: / -> dist/index.html (${html.length} bytes)`);
    } else {
      const cleanSubPath = route.path.replace(/^\//, '');
      const targetDir = path.join(DIST_DIR, cleanSubPath);
      fs.mkdirSync(targetDir, { recursive: true });

      const subIndexPath = path.join(targetDir, 'index.html');
      fs.writeFileSync(subIndexPath, html, 'utf8');

      // Also write clean route .html for non-directory URL fallback
      const directHtmlPath = path.join(DIST_DIR, `${cleanSubPath}.html`);
      fs.mkdirSync(path.dirname(directHtmlPath), { recursive: true });
      fs.writeFileSync(directHtmlPath, html, 'utf8');

      console.log(`✓ Pre-rendered: ${route.path} -> dist/${cleanSubPath}/index.html (${html.length} bytes)`);
    }
  }

  console.log(`\nAll ${routes.length} canonical routes successfully pre-rendered with zero errors!`);
}

prerender();

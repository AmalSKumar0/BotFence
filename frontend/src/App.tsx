/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState, useEffect } from 'react';
import { Navbar } from './components/Navbar';
import { HeroSection } from './components/HeroSection';
import { KeyFeaturesSection } from './components/KeyFeaturesSection';
import { FaqAndInstallSection } from './components/FaqAndInstallSection';
import { FooterSection } from './components/FooterSection';
import { DownloadPage } from './components/DownloadPage';
import { SEO } from './components/SEO';

// Multi-Page Architecture Components
import { FeaturesPage } from './pages/FeaturesPage';
import { DocsHubPage } from './pages/DocsHubPage';
import { InstallationPage } from './pages/InstallationPage';
import { ConfigurationPage } from './pages/ConfigurationPage';
import { ContactForm7Page } from './pages/ContactForm7Page';
import { PrivacyPage } from './pages/PrivacyPage';
import { FaqPage } from './pages/FaqPage';
import { ChangelogPage } from './pages/ChangelogPage';
import { SupportPage } from './pages/SupportPage';
import { AboutPage } from './pages/AboutPage';
import { BlogHubPage } from './pages/BlogHubPage';
import { BlogPostPage } from './pages/BlogPostPage';

function normalizePath(pathname: string, hash: string): string {
  // Check hash route fallbacks first
  if (hash === '#download' || pathname === '/download') return '/download';
  if (hash === '#features' || pathname === '/features') return '/features';
  if (hash === '#installation' || pathname === '/docs/installation') return '/docs/installation';
  if (hash === '#faq' || pathname === '/faq') return '/faq';

  // Strip trailing slashes
  const clean = pathname.replace(/\/+$/, '');
  return clean === '' ? '/' : clean;
}

export default function App({ initialPath }: { initialPath?: string } = {}) {
  const [currentPath, setCurrentPath] = useState<string>(() => {
    if (initialPath) return initialPath;
    if (typeof window !== 'undefined') {
      return normalizePath(window.location.pathname, window.location.hash);
    }
    return '/';
  });

  useEffect(() => {
    const handleLocationChange = () => {
      const path = normalizePath(window.location.pathname, window.location.hash);
      setCurrentPath(path);
    };

    window.addEventListener('popstate', handleLocationChange);
    window.addEventListener('hashchange', handleLocationChange);
    return () => {
      window.removeEventListener('popstate', handleLocationChange);
      window.removeEventListener('hashchange', handleLocationChange);
    };
  }, []);

  const navigateTo = (path: string) => {
    setCurrentPath(path);
    if (typeof window !== 'undefined') {
      window.history.pushState({}, '', path);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  const homeStructuredData = [
    {
      '@context': 'https://schema.org',
      '@type': 'WebSite',
      'name': 'Vighnaghna',
      'url': 'https://vighnaghna.amalskumar.dev/',
      'potentialAction': {
        '@type': 'SearchAction',
        'target': {
          '@type': 'EntryPoint',
          'urlTemplate': 'https://vighnaghna.amalskumar.dev/faq?q={search_term_string}'
        },
        'query-input': 'required name=search_term_string'
      }
    },
    {
      '@context': 'https://schema.org',
      '@type': 'Organization',
      'name': 'Vighnaghna',
      'url': 'https://vighnaghna.amalskumar.dev/',
      'logo': 'https://vighnaghna.amalskumar.dev/icon-256x256.png',
      'founder': {
        '@type': 'Person',
        'name': 'Amal S Kumar',
        'url': 'https://amalskumar.dev'
      }
    },
    {
      '@context': 'https://schema.org',
      '@type': 'SoftwareApplication',
      'name': 'Vighnaghna',
      'alternateName': ['Vighnaghna WordPress Plugin', 'Vighnaghna Anti-Spam'],
      'operatingSystem': 'WordPress 6.0+',
      'applicationCategory': 'SecurityApplication',
      'softwareVersion': '1.0.1',
      'fileSize': '2.0MB',
      'license': 'https://www.gnu.org/licenses/gpl-2.0.html',
      'url': 'https://vighnaghna.amalskumar.dev/',
      'downloadUrl': 'https://vighnaghna.amalskumar.dev/vighnaghna.zip',
      'image': 'https://vighnaghna.amalskumar.dev/og-image.png',
      'description': 'Lightweight, invisible anti-spam protection for WordPress forms. No CAPTCHA, no external requests. Stop spam bots across CF7, WPForms, and WooCommerce.'
    }
  ];

  // Route: Download Page
  if (currentPath === '/download') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <SEO
          title="Download Vighnaghna — Official Release Package (v1.0.1)"
          description="Download the official open-source Vighnaghna WordPress anti-spam plugin package (v1.0.1). SHA-256 verified, GPL-2.0+ licensed, zero external dependencies."
          canonicalUrl="https://vighnaghna.amalskumar.dev/download"
        />
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <DownloadPage onBackHome={() => navigateTo('/')} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Route: Features Page
  if (currentPath === '/features') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <FeaturesPage onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Route: Documentation Hub
  if (currentPath === '/docs') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <DocsHubPage onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Route: Installation Guide
  if (currentPath === '/docs/installation') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <InstallationPage onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Route: Configuration Guide
  if (currentPath === '/docs/configuration') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <ConfigurationPage onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Route: Contact Form 7 Integration
  if (currentPath === '/integrations/contact-form-7') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <ContactForm7Page onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Route: Privacy & Security
  if (currentPath === '/privacy') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <PrivacyPage onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Route: FAQ Page
  if (currentPath === '/faq') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <FaqPage onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Route: Changelog Page
  if (currentPath === '/changelog') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <ChangelogPage onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Route: Support Page
  if (currentPath === '/support') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <SupportPage onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Route: About Page
  if (currentPath === '/about') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <AboutPage onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Route: Blog Index
  if (currentPath === '/blog') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <BlogHubPage onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Route: Blog Post 1
  if (currentPath === '/blog/how-wordpress-form-spam-works') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <BlogPostPage slug="how-wordpress-form-spam-works" onNavigate={navigateTo} onOpenDownload={() => navigateTo('/download')} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Route: Blog Post 2
  if (currentPath === '/blog/how-invisible-captcha-works') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <BlogPostPage slug="how-invisible-captcha-works" onNavigate={navigateTo} onOpenDownload={() => navigateTo('/download')} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Route: Blog Post 3
  if (currentPath === '/blog/reduce-false-positive-spam-detection') {
    return (
      <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased">
        <Navbar currentPath={currentPath} onNavigate={navigateTo} />
        <BlogPostPage slug="reduce-false-positive-spam-detection" onNavigate={navigateTo} onOpenDownload={() => navigateTo('/download')} />
        <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
      </div>
    );
  }

  // Default Route: Homepage
  return (
    <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased selection:bg-[#FF4D6D]/30 selection:text-white">
      <SEO
        title="Vighnaghna — Zero-CAPTCHA Anti-Spam Plugin for WordPress"
        description="Lightweight, invisible anti-spam protection for WordPress forms. No CAPTCHA, no external requests. Stop spam bots across Contact Form 7, WPForms, and WooCommerce."
        canonicalUrl="https://vighnaghna.amalskumar.dev/"
        structuredData={homeStructuredData}
      />
      <main className="w-full">
        {/* 1. Dark Luxury Studio Hero Section */}
        <HeroSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />

        {/* 2. Key Features Grid */}
        <KeyFeaturesSection onOpenDownload={() => navigateTo('/download')} />

        {/* 3. Installation, Comparison Matrix & FAQ */}
        <FaqAndInstallSection onOpenDownload={() => navigateTo('/download')} />
      </main>

      {/* 4. Sleek Dark Studio Footer */}
      <FooterSection onOpenDownload={() => navigateTo('/download')} onNavigate={navigateTo} />
    </div>
  );
}

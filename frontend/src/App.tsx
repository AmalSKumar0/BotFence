/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState, useEffect } from 'react';
import { HeroSection } from './components/HeroSection';
import { KeyFeaturesSection } from './components/KeyFeaturesSection';
import { FaqAndInstallSection } from './components/FaqAndInstallSection';
import { FooterSection } from './components/FooterSection';
import { DownloadPage } from './components/DownloadPage';

export default function App() {
  const [currentPage, setCurrentPage] = useState<'home' | 'download'>(() => {
    if (typeof window !== 'undefined') {
      const hash = window.location.hash;
      const path = window.location.pathname;
      if (hash === '#download' || path === '/download') {
        return 'download';
      }
    }
    return 'home';
  });

  useEffect(() => {
    const handleLocationChange = () => {
      const hash = window.location.hash;
      const path = window.location.pathname;
      if (hash === '#download' || path === '/download') {
        setCurrentPage('download');
      } else {
        setCurrentPage('home');
      }
    };

    window.addEventListener('popstate', handleLocationChange);
    window.addEventListener('hashchange', handleLocationChange);
    return () => {
      window.removeEventListener('popstate', handleLocationChange);
      window.removeEventListener('hashchange', handleLocationChange);
    };
  }, []);

  const navigateToDownload = () => {
    setCurrentPage('download');
    if (typeof window !== 'undefined') {
      window.history.pushState({}, '', '#download');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  const navigateToHome = () => {
    setCurrentPage('home');
    if (typeof window !== 'undefined') {
      window.history.pushState({}, '', '#');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  if (currentPage === 'download') {
    return <DownloadPage onBackHome={navigateToHome} />;
  }

  return (
    <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased selection:bg-[#FF4D6D]/30 selection:text-white">
      <main className="w-full">
        {/* 1. Dark Luxury Studio Hero Section */}
        <HeroSection onOpenDownload={navigateToDownload} />

        {/* 2. FEATURES & "OUR WORKS" 3D Digital Art Showcase */}
        <KeyFeaturesSection onOpenDownload={navigateToDownload} />

        {/* 3. Installation, Comparison Matrix & FAQ */}
        <FaqAndInstallSection onOpenDownload={navigateToDownload} />
      </main>

      {/* 4. Sleek Dark Studio Footer */}
      <FooterSection onOpenDownload={navigateToDownload} />
    </div>
  );
}

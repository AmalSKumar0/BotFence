/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from 'react';
import { HeroSection } from './components/HeroSection';
import { KeyFeaturesSection } from './components/KeyFeaturesSection';
import { FaqAndInstallSection } from './components/FaqAndInstallSection';
import { FooterSection } from './components/FooterSection';
import { DownloadModal } from './components/DownloadModal';

export default function App() {
  const [isDownloadOpen, setIsDownloadOpen] = useState(false);

  return (
    <div className="min-h-screen bg-[#EFE8DE] text-[#121110] font-sans antialiased selection:bg-[#E8A588]/30 selection:text-[#121110] p-2 sm:p-6 lg:p-8 flex flex-col items-center justify-start">
      <div className="w-full max-w-[1380px] bg-white rounded-[32px] sm:rounded-[48px] shadow-2xl border border-[#E5DDCF] overflow-hidden flex flex-col">
        <main className="w-full">
          {/* 1. Hero Section inspired by Editorial Design */}
          <HeroSection onOpenDownload={() => setIsDownloadOpen(true)} />

          {/* 2. Key Features & How It Works */}
          <KeyFeaturesSection />

          {/* 3. Installation, Comparison Matrix & FAQ */}
          <FaqAndInstallSection onOpenDownload={() => setIsDownloadOpen(true)} />
        </main>

        {/* 4. Minimalist Editorial Footer */}
        <FooterSection onOpenDownload={() => setIsDownloadOpen(true)} />
      </div>

      {/* 5. Download Modal */}
      <DownloadModal
        isOpen={isDownloadOpen}
        onClose={() => setIsDownloadOpen(false)}
      />
    </div>
  );
}

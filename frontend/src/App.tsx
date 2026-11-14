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
    <div className="min-h-screen bg-[#0A0A0A] text-white font-sans antialiased selection:bg-[#FF4D6D]/30 selection:text-white">
      <main className="w-full">
        {/* 1. Dark Luxury Studio Hero Section (Exact Reference Layout) */}
        <HeroSection onOpenDownload={() => setIsDownloadOpen(true)} />

        {/* 2. FEATURES & "OUR WORKS" 3D Digital Art Showcase */}
        <KeyFeaturesSection onOpenDownload={() => setIsDownloadOpen(true)} />

        {/* 3. Installation, Comparison Matrix & FAQ */}
        <FaqAndInstallSection onOpenDownload={() => setIsDownloadOpen(true)} />
      </main>

      {/* 4. Sleek Dark Studio Footer */}
      <FooterSection onOpenDownload={() => setIsDownloadOpen(true)} />

      {/* 5. Download Modal */}
      <DownloadModal
        isOpen={isDownloadOpen}
        onClose={() => setIsDownloadOpen(false)}
      />
    </div>
  );
}

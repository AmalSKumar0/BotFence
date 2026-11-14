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
    <div className="min-h-screen bg-[#FAF9FD] text-[#0D0C15] font-sans antialiased selection:bg-[#EDE9FE] selection:text-[#5B42F3]">
      {/* 
        NO NAVBAR as explicitly requested in prompt:
        "i want you to create a site with no navbar, herosection 'Stop Bots. Protect Every Form'"
      */}
      <main>
        {/* 1. Hero Section with 3D Three.js Suspended Kinetic Defense Core */}
        <HeroSection onOpenDownload={() => setIsDownloadOpen(true)} />

        {/* 2. Key Features Section with Animated Icon Cards */}
        <KeyFeaturesSection />

        {/* 3. Rapid 60-Second Installation, Comparison Matrix & FAQ */}
        <FaqAndInstallSection onOpenDownload={() => setIsDownloadOpen(true)} />
      </main>

      {/* 4. Redesigned Minimal Mobbin Footer with Developer Credit */}
      <FooterSection onOpenDownload={() => setIsDownloadOpen(true)} />

      {/* 5. Requested Download Modal */}
      <DownloadModal
        isOpen={isDownloadOpen}
        onClose={() => setIsDownloadOpen(false)}
      />
    </div>
  );
}

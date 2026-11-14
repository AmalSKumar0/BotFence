import React, { useState } from 'react';
import { Download, Check, ShieldCheck, ArrowLeft, ArrowRight, Copy, CheckCheck } from 'lucide-react';
import { validateDownloadForm } from '../utils/vighnaghna-engine';
import { sendTelegramNotification } from '../utils/telegram';

interface DownloadPageProps {
  onBackHome: () => void;
}

export const DownloadPage: React.FC<DownloadPageProps> = ({ onBackHome }) => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [sendUpdates, setSendUpdates] = useState(true);

  const [errors, setErrors] = useState<{ name?: string; email?: string }>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [downloadSuccess, setDownloadSuccess] = useState(false);
  const [downloadedFileName, setDownloadedFileName] = useState('vighnaghna.zip');
  const [copiedCli, setCopiedCli] = useState(false);

  const triggerZipDownload = () => {
    const fileName = 'vighnaghna.zip';
    const anchor = document.createElement('a');
    anchor.href = `/${fileName}`;
    anchor.download = fileName;
    document.body.appendChild(anchor);
    anchor.click();
    setTimeout(() => {
      if (anchor.parentNode) {
        anchor.parentNode.removeChild(anchor);
      }
    }, 200);
    setDownloadedFileName(fileName);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    const validation = validateDownloadForm({
      name: name.trim(),
      email: email.trim(),
      wouldReview: sendUpdates,
    });

    if (!validation.valid) {
      setErrors(validation.errors);
      return;
    }

    setErrors({});
    setIsSubmitting(true);

    try {
      // 1. Send download notification via Telegram
      await sendTelegramNotification({
        name: name.trim(),
        email: email.trim(),
        wouldReview: sendUpdates,
        timestamp: new Date().toLocaleString(),
      });

      // 2. Trigger direct zip package download in browser
      triggerZipDownload();
      setDownloadSuccess(true);
    } catch (err) {
      console.error('Failed to trigger download or notification:', err);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleCopyCli = () => {
    const cmd = `wp plugin install ${downloadedFileName || 'vighnaghna.zip'} --activate`;
    navigator.clipboard?.writeText(cmd);
    setCopiedCli(true);
    setTimeout(() => setCopiedCli(false), 2000);
  };

  return (
    <div className="min-h-screen w-full bg-[#0A0A0A] dark-studio-glow text-white flex flex-col justify-between p-6 sm:p-12 lg:p-16 select-none overflow-x-hidden">
      
      {/* 1. Header Bar: Return to Home + Brand Identity */}
      <header className="w-full max-w-7xl mx-auto flex items-center justify-between pb-8 border-b border-[#1C1C1C]">
        <button
          type="button"
          onClick={onBackHome}
          className="inline-flex items-center gap-2 text-xs text-[#8E8E8E] hover:text-white transition-colors cursor-pointer group tracking-wide"
        >
          <ArrowLeft className="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
          <span>Back to site</span>
        </button>

        <div className="flex items-center gap-2 text-xs tracking-[0.2em] uppercase font-bold text-white">
          <span>VIGHNAGHNA</span>
          <span className="font-serif italic text-[#8E8E8E] font-normal normal-case hidden sm:inline">
            (विघ्नघ्न)
          </span>
        </div>
      </header>


      {/* 2. Main Body Grid: Product Architecture & Specifications on Left, Underline Form on Right */}
      <main className="w-full max-w-7xl mx-auto my-auto grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start py-12 sm:py-16">
        
        {/* Left Column: Product Identity & Architecture */}
        <div className="lg:col-span-5 text-left flex flex-col justify-between space-y-10">
          
          {/* Display Typography (Matches Hero Editorial Style) */}
          <div className="space-y-1 sm:space-y-2">
            <div className="text-5xl sm:text-7xl lg:text-[84px] font-[850] tracking-tight leading-[0.92] text-white">
              GET
            </div>
            <div className="font-serif italic text-5xl sm:text-7xl lg:text-[84px] font-normal tracking-wide text-white leading-[0.92]">
              VIGHNAGHNA
            </div>
          </div>

          <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed max-w-md">
            Lightweight, invisible anti-spam protection for WordPress forms. Blocks automated bot submissions with zero puzzles, zero tracking cookies, and zero external SaaS dependencies.
          </p>

          {/* Technical Specifications */}
          <div className="grid grid-cols-2 gap-6 pt-2 border-t border-[#1C1C1C]">
            <div className="space-y-1">
              <div className="text-[11px] uppercase tracking-[0.2em] text-[#8E8E8E] font-semibold">
                COMPATIBILITY
              </div>
              <div className="text-xs sm:text-sm text-neutral-300 font-medium">
                WordPress 5.9+ • PHP 7.4–8.5+
              </div>
            </div>

            <div className="space-y-1">
              <div className="text-[11px] uppercase tracking-[0.2em] text-[#8E8E8E] font-semibold">
                PACKAGE
              </div>
              <div className="text-xs sm:text-sm text-neutral-300 font-medium">
                v1.0.0 • GPL-2.0+ License
              </div>
            </div>
          </div>

          {/* Core Defense Pillars */}
          <div className="space-y-2.5 pt-2 text-xs text-[#8E8E8E]">
            <div className="flex items-center gap-2">
              <span className="w-1.5 h-1.5 rounded-full bg-[#FF4D6D]" />
              <span>Sub-2ms in-memory execution with zero database bloat</span>
            </div>
            <div className="flex items-center gap-2">
              <span className="w-1.5 h-1.5 rounded-full bg-[#FF4D6D]" />
              <span>Polymorphic honeypots invisible to legitimate human visitors</span>
            </div>
            <div className="flex items-center gap-2">
              <span className="w-1.5 h-1.5 rounded-full bg-[#FF4D6D]" />
              <span>Native hooks for Contact Form 7, WPForms, Gravity & WooCommerce</span>
            </div>
            <div className="flex items-center gap-2">
              <span className="w-1.5 h-1.5 rounded-full bg-[#FF4D6D]" />
              <span>100% GDPR compliant with zero external cloud requests</span>
            </div>
          </div>

        </div>


        {/* Right Column: Underline Form matching Dark Studio Theme */}
        <div className="lg:col-span-7 w-full text-left">
          
          {!downloadSuccess ? (
            <form onSubmit={handleSubmit} noValidate className="space-y-8 max-w-xl lg:ml-auto">
              
              <div>
                <h2 className="text-xl sm:text-2xl font-[850] text-white tracking-tight">
                  Download Official Package
                </h2>
                <p className="text-xs text-[#8E8E8E] mt-1">
                  Enter your details to generate your verified plugin download (.zip).
                </p>
              </div>

              {/* Field 1: NAME */}
              <div className="space-y-2">
                <label htmlFor="user-name" className="block text-[11px] uppercase tracking-[0.22em] text-[#8E8E8E] font-semibold">
                  NAME OR AGENCY
                </label>
                <input
                  id="user-name"
                  type="text"
                  value={name}
                  onChange={(e) => {
                    setName(e.target.value);
                    if (errors.name) setErrors((prev) => ({ ...prev, name: undefined }));
                  }}
                  placeholder="e.g. Alex Rivera or Agency Name"
                  className="w-full bg-transparent border-b border-[#222222] focus:border-white focus:outline-none py-3 text-white placeholder-neutral-600 text-sm sm:text-base transition-colors"
                />
                {errors.name && (
                  <p className="text-xs text-rose-400 font-medium pt-1">{errors.name}</p>
                )}
              </div>

              {/* Field 2: EMAIL ADDRESS (REQUIRED) */}
              <div className="space-y-2">
                <label htmlFor="user-email" className="block text-[11px] uppercase tracking-[0.22em] text-[#8E8E8E] font-semibold">
                  EMAIL ADDRESS (REQUIRED)
                </label>
                <input
                  id="user-email"
                  type="email"
                  value={email}
                  onChange={(e) => {
                    setEmail(e.target.value);
                    if (errors.email) setErrors((prev) => ({ ...prev, email: undefined }));
                  }}
                  placeholder="e.g. alex@example.com"
                  className="w-full bg-transparent border-b border-[#222222] focus:border-white focus:outline-none py-3 text-white placeholder-neutral-600 text-sm sm:text-base transition-colors"
                />
                {errors.email && (
                  <p className="text-xs text-rose-400 font-medium pt-1">{errors.email}</p>
                )}
              </div>

              {/* Field 3: Willing to Send Updation Mail Checkbox */}
              <div className="pt-2">
                <label
                  htmlFor="updates-check"
                  className="inline-flex items-center gap-3 cursor-pointer select-none group"
                >
                  <input
                    id="updates-check"
                    type="checkbox"
                    checked={sendUpdates}
                    onChange={(e) => setSendUpdates(e.target.checked)}
                    className="sr-only"
                  />
                  <div
                    aria-hidden="true"
                    className={`w-4 h-4 rounded-[4px] flex items-center justify-center transition-colors ${
                      sendUpdates ? 'bg-white text-black' : 'bg-transparent border border-[#333333]'
                    }`}
                  >
                    {sendUpdates && <Check className="w-3 h-3 stroke-[3]" />}
                  </div>
                  <span className="text-xs text-[#8E8E8E] group-hover:text-white transition-colors">
                    Send me plugin updates, security advisories, and release notes
                  </span>
                </label>
              </div>

              {/* Submit Button */}
              <div className="pt-4">
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="pill-button px-8 py-3.5 bg-white text-black font-semibold text-sm hover:bg-neutral-200 transition-all cursor-pointer inline-flex items-center gap-2 shadow-lg disabled:opacity-60"
                >
                  <Download className="w-4 h-4" />
                  <span>{isSubmitting ? 'Preparing plugin...' : 'Download Vighnaghna (.zip)'}</span>
                  <ArrowRight className="w-4 h-4" />
                </button>
              </div>

              <div className="text-[11px] text-[#555555]">
                100% Free forever under GNU General Public License v2+. No spam, unsubscribe anytime.
              </div>

            </form>
          ) : (
            /* Instant Download Success State */
            <div className="card-dark-glass p-8 sm:p-10 space-y-6 max-w-xl lg:ml-auto">
              
              <div className="flex items-start gap-4">
                <div className="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                  <ShieldCheck className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="text-xl font-bold text-white tracking-tight">
                    Download Started!
                  </h3>
                  <p className="text-xs text-[#8E8E8E] mt-1 leading-relaxed">
                    Saved <span className="font-semibold text-white font-mono">{downloadedFileName}</span> to your device.
                  </p>
                </div>
              </div>

              {/* WP-CLI Quick Command */}
              <div className="p-4 rounded-xl bg-[#0A0A0A] border border-[#222222] flex items-center justify-between gap-3">
                <code className="text-xs font-mono text-neutral-300 truncate">
                  wp plugin install {downloadedFileName} --activate
                </code>
                <button
                  type="button"
                  onClick={handleCopyCli}
                  className="pill-button px-3 py-1 bg-[#1F1F1F] hover:bg-white hover:text-black text-white text-xs font-medium transition-colors cursor-pointer shrink-0 inline-flex items-center gap-1.5"
                >
                  {copiedCli ? (
                    <>
                      <CheckCheck className="w-3 h-3 text-emerald-400" />
                      <span>Copied</span>
                    </>
                  ) : (
                    <>
                      <Copy className="w-3 h-3" />
                      <span>Copy</span>
                    </>
                  )}
                </button>
              </div>

              {/* Action Buttons */}
              <div className="flex flex-wrap items-center gap-3 pt-2">
                <button
                  type="button"
                  onClick={triggerZipDownload}
                  className="pill-button px-5 py-2.5 bg-white text-black font-semibold text-xs hover:bg-neutral-200 transition-colors inline-flex items-center gap-2 cursor-pointer"
                >
                  <Download className="w-3.5 h-3.5" />
                  <span>Download again</span>
                </button>

                <button
                  type="button"
                  onClick={onBackHome}
                  className="pill-button px-5 py-2.5 bg-transparent border border-[#2B2B2B] text-neutral-300 hover:text-white hover:border-white font-medium text-xs transition-colors cursor-pointer"
                >
                  Return to Home
                </button>
              </div>

            </div>
          )}

        </div>

      </main>

      {/* 3. Footer Bar */}
      <footer className="w-full max-w-7xl mx-auto pt-8 border-t border-[#1C1C1C] text-center text-xs text-[#555555]">
        Vighnaghna WordPress Plugin • Free & Open Source under GPL-2.0-or-later • No external SaaS calls
      </footer>

    </div>
  );
};

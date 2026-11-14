import React, { useState } from 'react';
import { Download, Check, ShieldCheck, ArrowLeft, ArrowUpRight, Copy, CheckCheck } from 'lucide-react';
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
      await sendTelegramNotification({
        name: name.trim(),
        email: email.trim(),
        wouldReview: sendUpdates,
        timestamp: new Date().toLocaleString(),
      });

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
      
      {/* 1. Header Bar: Minimal Return Link + Brand */}
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


      {/* 2. Main Body Grid: Minimalist 2-Column Presentation */}
      <main className="w-full max-w-7xl mx-auto my-auto grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start py-12 sm:py-20">
        
        {/* Left Column: Pure Typography & Tagline */}
        <div className="lg:col-span-5 text-left flex flex-col justify-between space-y-8">
          
          <div className="space-y-1 sm:space-y-2">
            <div className="text-5xl sm:text-7xl lg:text-[84px] font-[850] tracking-tight leading-[0.92] text-white">
              GET
            </div>
            <div className="font-serif italic text-5xl sm:text-7xl lg:text-[84px] font-normal tracking-wide text-white leading-[0.92]">
              VIGHNAGHNA
            </div>
          </div>

          <p className="text-xs sm:text-sm text-[#8E8E8E] leading-relaxed max-w-md">
            Invisible anti-spam for WordPress forms. Zero CAPTCHAs, zero tracking cookies, zero external requests.
          </p>

          <div className="pt-2 text-xs text-[#666666] tracking-wider uppercase font-medium">
            v1.0.0 • Free & Open Source (GPL-2.0+) • Self-Hosted
          </div>

        </div>


        {/* Right Column: Minimal Underline Form */}
        <div className="lg:col-span-7 w-full text-left">
          
          {!downloadSuccess ? (
            <form onSubmit={handleSubmit} noValidate className="space-y-8 max-w-lg lg:ml-auto">
              
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
                  placeholder="e.g. Alex Rivera"
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
              <div className="pt-1">
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
                    Send me plugin release and security updates
                  </span>
                </label>
              </div>

              {/* Submit Button */}
              <div className="pt-2">
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="pill-button px-8 py-3.5 bg-white text-black font-semibold text-xs sm:text-sm hover:bg-neutral-200 transition-all cursor-pointer inline-flex items-center gap-2 shadow-lg disabled:opacity-60"
                >
                  <span>{isSubmitting ? 'Preparing...' : 'Download Vighnaghna (.zip)'}</span>
                  <ArrowUpRight className="w-4 h-4 stroke-[2.2]" />
                </button>
              </div>

            </form>
          ) : (
            /* Instant Download Success State */
            <div className="card-dark-glass p-8 space-y-6 max-w-lg lg:ml-auto">
              
              <div className="flex items-start gap-3.5">
                <div className="w-9 h-9 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                  <ShieldCheck className="w-4 h-4" />
                </div>
                <div>
                  <h3 className="text-lg font-bold text-white tracking-tight">
                    Download Started
                  </h3>
                  <p className="text-xs text-[#8E8E8E] mt-0.5 leading-relaxed">
                    Saved <span className="font-semibold text-white font-mono">{downloadedFileName}</span> to your device.
                  </p>
                </div>
              </div>

              {/* WP-CLI Quick Command */}
              <div className="p-3.5 rounded-xl bg-[#0A0A0A] border border-[#222222] flex items-center justify-between gap-3">
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
              <div className="flex flex-wrap items-center gap-3 pt-1">
                <button
                  type="button"
                  onClick={triggerZipDownload}
                  className="pill-button px-4 py-2 bg-white text-black font-semibold text-xs hover:bg-neutral-200 transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                >
                  <Download className="w-3.5 h-3.5" />
                  <span>Download again</span>
                </button>

                <button
                  type="button"
                  onClick={onBackHome}
                  className="pill-button px-4 py-2 bg-transparent border border-[#2B2B2B] text-neutral-300 hover:text-white hover:border-white font-medium text-xs transition-colors cursor-pointer"
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
        Vighnaghna • Free & Open Source under GPL-2.0-or-later • No external requests
      </footer>

    </div>
  );
};

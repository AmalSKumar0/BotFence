import React, { useState } from 'react';
import { Send, Check, ShieldCheck, ArrowLeft, Download, Copy, CheckCheck } from 'lucide-react';
import { validateDownloadForm } from '../utils/vighnaghna-engine';
import { sendTelegramNotification } from '../utils/telegram';

interface DownloadPageProps {
  onBackHome: () => void;
}

// Decorative floating petals matching the reference design screenshot
const PETALS = [
  { top: '15%', left: '15%', rotate: '-25deg', size: '14px', opacity: 0.35 },
  { top: '13%', left: '21%', rotate: '15deg', size: '18px', opacity: 0.45 },
  { top: '19%', left: '27%', rotate: '45deg', size: '13px', opacity: 0.3 },
  { top: '22%', left: '14%', rotate: '-65deg', size: '15px', opacity: 0.38 },
  { top: '29%', left: '28%', rotate: '30deg', size: '12px', opacity: 0.25 },
  { top: '22%', left: '61%', rotate: '18deg', size: '15px', opacity: 0.4 },
  { top: '28%', left: '62%', rotate: '-35deg', size: '12px', opacity: 0.3 },
  { top: '58%', left: '58%', rotate: '70deg', size: '16px', opacity: 0.35 },
  { top: '66%', left: '69%', rotate: '-15deg', size: '17px', opacity: 0.3 },
  { top: '57%', left: '84%', rotate: '-45deg', size: '18px', opacity: 0.4 },
  { top: '78%', left: '83%', rotate: '35deg', size: '16px', opacity: 0.35 },
  { top: '79%', left: '84%', rotate: '-70deg', size: '13px', opacity: 0.4 },
];

export const DownloadPage: React.FC<DownloadPageProps> = ({ onBackHome }) => {
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [projectDescription, setProjectDescription] = useState('');
  const [wouldReview, setWouldReview] = useState(true);

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

    const fullName = `${firstName.trim()} ${lastName.trim()}`.trim();
    const validation = validateDownloadForm({
      name: fullName || firstName.trim(),
      email: email.trim(),
      wouldReview,
    });

    if (!validation.valid) {
      setErrors(validation.errors);
      return;
    }

    setErrors({});
    setIsSubmitting(true);

    try {
      // 1. Dispatch Telegram notification to site owner
      await sendTelegramNotification({
        name: fullName,
        email: email.trim(),
        wouldReview,
        projectDescription: projectDescription.trim(),
        timestamp: new Date().toLocaleString(),
      });

      // 2. Trigger automatic direct download of the official zip package
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
    <div className="relative min-h-screen w-full bg-[#0E0C12] text-white flex flex-col justify-between p-6 sm:p-12 lg:p-16 select-none overflow-x-hidden">
      
      {/* Decorative Scattered Floating Petals from Reference Design */}
      <div className="absolute inset-0 pointer-events-none overflow-hidden z-0" aria-hidden="true">
        {PETALS.map((petal, i) => (
          <svg
            key={i}
            className="absolute transition-transform duration-1000 ease-out"
            style={{
              top: petal.top,
              left: petal.left,
              transform: `rotate(${petal.rotate})`,
              width: petal.size,
              height: petal.size,
              opacity: petal.opacity,
            }}
            viewBox="0 0 24 24"
            fill="#7C6E96"
          >
            <path d="M12 2C7 8 2 12 2 17a10 10 0 0 0 20 0c0-5-5-9-10-15z" />
          </svg>
        ))}
      </div>

      {/* Top Bar: Back to Home Link */}
      <header className="relative z-10 w-full max-w-7xl mx-auto flex items-center justify-between pb-8">
        <button
          type="button"
          onClick={onBackHome}
          className="inline-flex items-center gap-2 text-xs text-[#8E869E] hover:text-white transition-colors cursor-pointer group tracking-wide"
        >
          <ArrowLeft className="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
          <span>Back to home</span>
        </button>

        <div className="text-xs tracking-[0.2em] uppercase font-bold text-white">
          VIGHNAGHNA
        </div>
      </header>


      {/* Main Two-Column Content Layout (Exact Match to Screenshot) */}
      <main className="relative z-10 w-full max-w-7xl mx-auto my-auto grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start py-8">
        
        {/* Left Column: Heading + Contact Info */}
        <div className="lg:col-span-5 text-left flex flex-col justify-between space-y-12">
          
          {/* Giant Title */}
          <div>
            <h1 className="text-6xl sm:text-7xl lg:text-[88px] font-[850] text-white tracking-tight leading-[0.95] mb-12 sm:mb-16">
              Get plugin
            </h1>

            {/* LOCATION & YEAR */}
            <div className="space-y-1 mb-10">
              <div className="text-[11px] uppercase tracking-[0.22em] text-[#7E778B] font-semibold">
                LOCATION & YEAR
              </div>
              <div className="text-sm text-[#B2A9C4] font-medium">
                Kerala, India © 2026
              </div>
            </div>

            {/* Contact Details */}
            <div className="space-y-3">
              <a
                href="mailto:amalskumardev@gmail.com"
                className="block text-2xl sm:text-3xl font-[800] text-white hover:text-neutral-300 transition-colors tracking-tight"
              >
                amalskumardev@gmail.com
              </a>
              <a
                href="tel:+918590774603"
                className="block text-2xl sm:text-3xl font-[800] text-white hover:text-neutral-300 transition-colors tracking-tight"
              >
                +91 85907 74603
              </a>
            </div>
          </div>

          {/* Left Footer Bar */}
          <div className="pt-8 border-t border-[#231E2D] flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs text-[#7E778B]">
            <div>
              <div>Kerala, India / 2026</div>
              <div>Say hello: Work with us</div>
            </div>
            <div className="flex items-center gap-4">
              <a
                href="https://github.com/AmalSKumar0/BotFence"
                target="_blank"
                rel="noopener noreferrer"
                className="hover:text-white transition-colors"
              >
                GitHub
              </a>
              <a
                href="https://linkedin.com"
                target="_blank"
                rel="noopener noreferrer"
                className="hover:text-white transition-colors"
              >
                LinkedIn
              </a>
              <a
                href="mailto:amalskumardev@gmail.com"
                className="hover:text-white transition-colors"
              >
                Email
              </a>
            </div>
          </div>

        </div>


        {/* Right Column: Underline Form Fields (Exact Match to Screenshot) */}
        <div className="lg:col-span-7 w-full text-left">
          
          {!downloadSuccess ? (
            <form onSubmit={handleSubmit} noValidate className="space-y-8 max-w-xl lg:ml-auto">
              
              {/* Row 1: FIRST NAME & LAST NAME */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-8">
                
                {/* First Name */}
                <div className="space-y-2">
                  <label htmlFor="first-name" className="block text-[11px] uppercase tracking-[0.22em] text-[#7E778B] font-semibold">
                    FIRST NAME
                  </label>
                  <input
                    id="first-name"
                    type="text"
                    value={firstName}
                    onChange={(e) => {
                      setFirstName(e.target.value);
                      if (errors.name) setErrors((prev) => ({ ...prev, name: undefined }));
                    }}
                    placeholder="Amal S"
                    className="w-full bg-transparent border-b border-[#2C2538] focus:border-white focus:outline-none py-2 text-white placeholder-[#514B60] text-base transition-colors"
                  />
                  {errors.name && (
                    <p className="text-xs text-rose-400 font-medium pt-1">{errors.name}</p>
                  )}
                </div>

                {/* Last Name */}
                <div className="space-y-2">
                  <label htmlFor="last-name" className="block text-[11px] uppercase tracking-[0.22em] text-[#7E778B] font-semibold">
                    LAST NAME
                  </label>
                  <input
                    id="last-name"
                    type="text"
                    value={lastName}
                    onChange={(e) => setLastName(e.target.value)}
                    placeholder="Kumar"
                    className="w-full bg-transparent border-b border-[#2C2538] focus:border-white focus:outline-none py-2 text-white placeholder-[#514B60] text-base transition-colors"
                  />
                </div>

              </div>

              {/* Row 2: EMAIL ADDRESS (REQUIRED) */}
              <div className="space-y-2">
                <label htmlFor="email-address" className="block text-[11px] uppercase tracking-[0.22em] text-[#7E778B] font-semibold">
                  EMAIL ADDRESS (REQUIRED)
                </label>
                <input
                  id="email-address"
                  type="email"
                  value={email}
                  onChange={(e) => {
                    setEmail(e.target.value);
                    if (errors.email) setErrors((prev) => ({ ...prev, email: undefined }));
                  }}
                  placeholder="amalskumar@gmail.com"
                  className="w-full bg-transparent border-b border-[#2C2538] focus:border-white focus:outline-none py-2 text-white placeholder-[#514B60] text-base transition-colors"
                />
                {errors.email && (
                  <p className="text-xs text-rose-400 font-medium pt-1">{errors.email}</p>
                )}
              </div>

              {/* Row 3: PROJECT DESCRIPTION */}
              <div className="space-y-2">
                <label htmlFor="project-desc" className="block text-[11px] uppercase tracking-[0.22em] text-[#7E778B] font-semibold">
                  PROJECT DESCRIPTION
                </label>
                <textarea
                  id="project-desc"
                  rows={3}
                  value={projectDescription}
                  onChange={(e) => setProjectDescription(e.target.value)}
                  placeholder="Tell me about your project details, goals, timeline..."
                  className="w-full bg-transparent border-b border-[#2C2538] focus:border-white focus:outline-none py-2 text-white placeholder-[#514B60] text-base resize-none transition-colors"
                />
              </div>

              {/* Row 4: Review Option */}
              <div className="pt-2">
                <label
                  htmlFor="review-check"
                  className="inline-flex items-center gap-3 cursor-pointer select-none group"
                >
                  <input
                    id="review-check"
                    type="checkbox"
                    checked={wouldReview}
                    onChange={(e) => setWouldReview(e.target.checked)}
                    className="sr-only"
                  />
                  <div
                    aria-hidden="true"
                    className={`w-4 h-4 rounded-[4px] flex items-center justify-center transition-colors ${
                      wouldReview ? 'bg-white text-black' : 'bg-transparent border border-[#3E354F]'
                    }`}
                  >
                    {wouldReview && <Check className="w-3 h-3 stroke-[3]" />}
                  </div>
                  <span className="text-xs text-[#988EA8] group-hover:text-white transition-colors">
                    Would love to give review after using this plugin
                  </span>
                </label>
              </div>

              {/* Row 5: Submit Button (Exact Pill Button from Reference Image) */}
              <div className="pt-4">
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="pill-button px-8 py-3 bg-white text-black font-semibold text-sm hover:bg-neutral-200 transition-all cursor-pointer inline-flex items-center gap-2 shadow-lg disabled:opacity-60"
                >
                  <span>{isSubmitting ? 'Preparing...' : 'Submit'}</span>
                  <Send className="w-3.5 h-3.5 -rotate-45" />
                </button>
              </div>

            </form>
          ) : (
            /* Instant Download Success State */
            <div className="space-y-6 max-w-xl lg:ml-auto p-8 rounded-[28px] bg-[#16131C] border border-[#2B2438]">
              
              <div className="flex items-start gap-4">
                <div className="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                  <ShieldCheck className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="text-xl font-bold text-white tracking-tight">
                    Download Started!
                  </h3>
                  <p className="text-xs text-[#A79DBA] mt-1 leading-relaxed">
                    Saved <span className="font-semibold text-white font-mono">{downloadedFileName}</span> to your computer.
                  </p>
                </div>
              </div>

              {/* WP-CLI Quick Command */}
              <div className="p-4 rounded-xl bg-[#0E0C12] border border-[#2B2438] flex items-center justify-between gap-3">
                <code className="text-xs font-mono text-[#D4CDE3] truncate">
                  wp plugin install {downloadedFileName} --activate
                </code>
                <button
                  type="button"
                  onClick={handleCopyCli}
                  className="pill-button px-3 py-1 bg-white/10 hover:bg-white hover:text-black text-white text-xs font-medium transition-colors cursor-pointer shrink-0 inline-flex items-center gap-1.5"
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
                  className="pill-button px-5 py-2.5 bg-transparent border border-[#3E354F] text-[#D4CDE3] hover:text-white hover:border-white font-medium text-xs transition-colors cursor-pointer"
                >
                  Return to Home
                </button>
              </div>

            </div>
          )}

        </div>

      </main>

      {/* Subtle Bottom Credit */}
      <footer className="relative z-10 w-full max-w-7xl mx-auto pt-8 text-center text-xs text-[#5E566E]">
        Vighnaghna WordPress Plugin • Free & Open Source under GPL-2.0-or-later
      </footer>

    </div>
  );
};

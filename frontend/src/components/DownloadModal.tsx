import React, { useState, useEffect } from 'react';
import { X, Download, Check, ShieldCheck, ArrowRight } from 'lucide-react';
import { validateDownloadForm } from '../utils/vighnaghna-engine';
import { sendTelegramNotification } from '../utils/telegram';

interface DownloadModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export const DownloadModal: React.FC<DownloadModalProps> = ({ isOpen, onClose }) => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [wouldReview, setWouldReview] = useState(true);
  const [errors, setErrors] = useState<{ name?: string; email?: string }>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [downloadSuccess, setDownloadSuccess] = useState(false);
  const [downloadedFileName, setDownloadedFileName] = useState('vighnaghna.zip');
  const [copiedCli, setCopiedCli] = useState(false);

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isOpen) {
        onClose();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  useEffect(() => {
    if (isOpen) {
      setErrors({});
      setDownloadSuccess(false);
      setIsSubmitting(false);
    }
  }, [isOpen]);

  if (!isOpen) return null;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    const validation = validateDownloadForm({ name, email, wouldReview });
    if (!validation.valid) {
      setErrors(validation.errors);
      return;
    }

    setErrors({});
    setIsSubmitting(true);

    try {
      // Send download lead details to the Telegram bot configured in .env
      await sendTelegramNotification({
        name: name.trim(),
        email: email.trim(),
        wouldReview,
        timestamp: new Date().toLocaleString(),
      });

      // Trigger browser download for vighnaghna.zip from public directory
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
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto"
      onClick={(e) => {
        if (e.target === e.currentTarget) onClose();
      }}
    >
      {/* Sleek Dark Studio Modal Card */}
      <div className="relative w-full max-w-md bg-[#141414] rounded-[32px] p-6 sm:p-8 my-8 shadow-2xl border border-[#2B2B2B] text-left text-white">
        
        {/* Header */}
        <div className="flex items-center justify-between pb-5 border-b border-[#242424]">
          <div>
            <h2 id="modal-title" className="text-xl font-[850] text-white tracking-tight">
              {downloadSuccess ? 'Download Ready' : 'Download Vighnaghna'}
            </h2>
            <p className="text-xs text-neutral-400 mt-0.5">
              WordPress plugin package (v1.0.0 • GPL-2.0-or-later)
            </p>
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label="Close download modal"
            className="w-8 h-8 rounded-full bg-[#1F1F1F] text-neutral-400 hover:text-white hover:bg-[#2B2B2B] flex items-center justify-center transition-colors cursor-pointer border border-[#2B2B2B]"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Modal Body */}
        <div className="pt-5">
          {!downloadSuccess ? (
            <form onSubmit={handleSubmit} noValidate className="space-y-4">
              {/* Name Field */}
              <div>
                <label htmlFor="user-name" className="block text-xs font-semibold text-neutral-300 mb-1.5">
                  Name or agency
                </label>
                <input
                  id="user-name"
                  type="text"
                  placeholder="e.g. Alex Rivera"
                  value={name}
                  onChange={(e) => {
                    setName(e.target.value);
                    if (errors.name) setErrors((prev) => ({ ...prev, name: undefined }));
                  }}
                  className={`w-full px-4 py-3 bg-[#1A1A1A] text-white placeholder-neutral-500 text-xs sm:text-sm rounded-[18px] border border-[#2E2E2E] focus:ring-2 focus:ring-white focus:border-transparent focus:outline-none transition-all ${
                    errors.name ? 'ring-2 ring-red-500 border-red-500' : ''
                  }`}
                />
                {errors.name && (
                  <p className="mt-1 text-xs text-red-400 font-medium">
                    {errors.name}
                  </p>
                )}
              </div>

              {/* Email Field */}
              <div>
                <label htmlFor="user-email" className="block text-xs font-semibold text-neutral-300 mb-1.5">
                  Email address
                </label>
                <input
                  id="user-email"
                  type="email"
                  placeholder="e.g. alex@example.com"
                  value={email}
                  onChange={(e) => {
                    setEmail(e.target.value);
                    if (errors.email) setErrors((prev) => ({ ...prev, email: undefined }));
                  }}
                  className={`w-full px-4 py-3 bg-[#1A1A1A] text-white placeholder-neutral-500 text-xs sm:text-sm rounded-[18px] border border-[#2E2E2E] focus:ring-2 focus:ring-white focus:border-transparent focus:outline-none transition-all ${
                    errors.email ? 'ring-2 ring-red-500 border-red-500' : ''
                  }`}
                />
                {errors.email && (
                  <p className="mt-1 text-xs text-red-400 font-medium">
                    {errors.email}
                  </p>
                )}
              </div>

              {/* Review Checkbox */}
              <div className="pt-1">
                <label
                  htmlFor="review-checkbox"
                  className="flex items-center gap-3 p-3 rounded-[18px] bg-[#1A1A1A] hover:bg-[#202020] border border-[#2E2E2E] cursor-pointer transition-colors select-none"
                >
                  <input
                    id="review-checkbox"
                    type="checkbox"
                    checked={wouldReview}
                    onChange={(e) => setWouldReview(e.target.checked)}
                    className="sr-only"
                  />
                  <div
                    aria-hidden="true"
                    className={`w-5 h-5 rounded-[6px] flex items-center justify-center transition-colors ${
                      wouldReview ? 'bg-white text-black' : 'bg-[#141414] border border-[#3A3A3A]'
                    }`}
                  >
                    {wouldReview && <Check className="w-3.5 h-3.5 stroke-[2.5]" />}
                  </div>
                  <span className="text-xs font-medium text-neutral-300 leading-snug">
                    Would love to give review after using this plugin
                  </span>
                </label>
              </div>

              {/* Submit Button */}
              <div className="pt-2">
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="pill-button w-full flex items-center justify-center gap-2 bg-white text-black font-semibold text-sm py-3.5 px-6 hover:bg-neutral-200 cursor-pointer shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-white disabled:opacity-60"
                >
                  <Download className="w-4 h-4" />
                  <span>{isSubmitting ? 'Preparing plugin...' : 'Download Vighnaghna (.zip)'}</span>
                  <ArrowRight className="w-4 h-4" />
                </button>
              </div>

              <div className="text-center text-[11px] text-neutral-500">
                Free forever under GNU General Public License v2+
              </div>
            </form>
          ) : (
            /* Instant Download Success State */
            <div className="space-y-4">
              <div className="bg-[#1C1C1C] border border-[#2B2B2B] rounded-[20px] p-4 flex items-start gap-3">
                <ShieldCheck className="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
                <div>
                  <div className="text-sm font-bold text-white">Download Started</div>
                  <div className="text-xs text-neutral-400 mt-0.5">
                    Saved <span className="font-semibold text-white">{downloadedFileName}</span> to your device.
                  </div>
                </div>
              </div>

              <div className="bg-[#181818] rounded-[20px] border border-[#262626] p-4 space-y-2 text-xs">
                <div className="font-bold text-white">Installation Steps:</div>
                <ol className="list-decimal list-inside space-y-1 text-neutral-400">
                  <li>In WordPress Admin, go to <strong>Plugins ➔ Add New ➔ Upload</strong></li>
                  <li>Select the downloaded <code className="bg-[#101010] text-[#FF4D6D] border border-[#262626] px-1 py-0.5 rounded font-mono font-medium">{downloadedFileName}</code> file</li>
                  <li>Click <strong>Activate</strong></li>
                </ol>
              </div>

              {/* WP-CLI command */}
              <div className="bg-[#181818] border border-[#262626] rounded-[18px] p-3 text-xs flex items-center justify-between gap-2">
                <div className="truncate font-mono text-[11px] text-neutral-400">
                  wp plugin install {downloadedFileName} --activate
                </div>
                <button
                  type="button"
                  onClick={handleCopyCli}
                  className="pill-button shrink-0 px-3 py-1 bg-[#262626] text-white text-[11px] font-medium hover:bg-white hover:text-black transition-colors"
                >
                  {copiedCli ? 'Copied' : 'Copy'}
                </button>
              </div>

              <div className="pt-2">
                <button
                  type="button"
                  onClick={onClose}
                  className="pill-button w-full bg-white text-black font-semibold text-xs py-3 hover:bg-neutral-200 cursor-pointer shadow-sm transition-all"
                >
                  Done
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

import React, { useState, useEffect } from 'react';
import { X, Download, Check, ShieldCheck } from 'lucide-react';
import { validateDownloadForm } from '../utils/botfence-engine';
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
  const [downloadedFileName, setDownloadedFileName] = useState('BotFence.zip');
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

      // Trigger browser download for BotFence.zip from public directory
      const fileName = 'BotFence.zip';
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
    const cmd = `wp plugin install ${downloadedFileName || 'BotFence.zip'} --activate`;
    navigator.clipboard?.writeText(cmd);
    setCopiedCli(true);
    setTimeout(() => setCopiedCli(false), 2000);
  };

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[rgba(0,0,0,0.5)] overflow-y-auto"
      onClick={(e) => {
        if (e.target === e.currentTarget) onClose();
      }}
    >
      {/* Mobbin 24px Card Geometry (rounded-[24px]) */}
      <div className="relative w-full max-w-md bg-[#ffffff] rounded-[24px] p-6 sm:p-8 my-8 shadow-2xl border border-[#f0f0f0] text-left">
        {/* Header */}
        <div className="flex items-center justify-between pb-5 border-b border-[#f0f0f0]">
          <div>
            <h2 id="modal-title" className="text-xl font-[650] text-[#141414] tracking-tight">
              {downloadSuccess ? 'Download ready' : 'Download BotFence'}
            </h2>
            <p className="text-xs text-[#707070] mt-0.5">
              WordPress plugin package (v1.0.1 • GPL-2.0-or-later)
            </p>
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label="Close download modal"
            className="w-8 h-8 rounded-full bg-[#f3f3f3] text-[#141414] hover:bg-[#e0e0e0] flex items-center justify-center transition-colors cursor-pointer"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Modal Body */}
        <div className="pt-5">
          {!downloadSuccess ? (
            <form onSubmit={handleSubmit} noValidate className="space-y-4">
              {/* Name Field (Mobbin 16px field fill) */}
              <div>
                <label htmlFor="user-name" className="block text-xs font-semibold text-[#141414] mb-1.5">
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
                  className={`w-full px-4 py-3 bg-[#f0f0f0] text-[#141414] placeholder-[#adadad] text-sm rounded-[16px] border-0 focus:ring-2 focus:ring-[#141414] focus:outline-none transition-all ${
                    errors.name ? 'ring-2 ring-red-500' : ''
                  }`}
                />
                {errors.name && (
                  <p className="mt-1 text-xs text-red-600 font-medium">
                    {errors.name}
                  </p>
                )}
              </div>

              {/* Email Field */}
              <div>
                <label htmlFor="user-email" className="block text-xs font-semibold text-[#141414] mb-1.5">
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
                  className={`w-full px-4 py-3 bg-[#f0f0f0] text-[#141414] placeholder-[#adadad] text-sm rounded-[16px] border-0 focus:ring-2 focus:ring-[#141414] focus:outline-none transition-all ${
                    errors.email ? 'ring-2 ring-red-500' : ''
                  }`}
                />
                {errors.email && (
                  <p className="mt-1 text-xs text-red-600 font-medium">
                    {errors.email}
                  </p>
                )}
              </div>

              {/* Review Checkbox - User friendly pill control */}
              <div className="pt-1">
                <label
                  htmlFor="review-checkbox"
                  className="flex items-center gap-3 p-3 rounded-[16px] bg-[#f3f3f3] hover:bg-[#ebebeb] cursor-pointer transition-colors select-none"
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
                      wouldReview ? 'bg-[#141414] text-white' : 'bg-white border border-[#e0e0e0]'
                    }`}
                  >
                    {wouldReview && <Check className="w-3.5 h-3.5 stroke-[2.5]" />}
                  </div>
                  <span className="text-xs font-medium text-[#141414] leading-snug">
                    Would love to give review after using this plugin
                  </span>
                </label>
              </div>

              {/* Submit Button (button-primary stadium pill) */}
              <div className="pt-2">
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="pill-button w-full flex items-center justify-center gap-2 bg-[#141414] text-[#ffffff] font-semibold text-sm py-3.5 px-6 hover:bg-[#262626] cursor-pointer disabled:opacity-70"
                >
                  <Download className="w-4 h-4" />
                  <span>{isSubmitting ? 'Preparing plugin...' : 'Download BotFence (.zip)'}</span>
                </button>
              </div>

              <div className="text-center text-[11px] text-[#adadad]">
                Free forever under GNU General Public License v2+
              </div>
            </form>
          ) : (
            /* Instant Download Success State */
            <div className="space-y-4">
              <div className="bg-[#f3f3f3] rounded-[16px] p-4 flex items-start gap-3">
                <ShieldCheck className="w-5 h-5 text-[#141414] shrink-0 mt-0.5" />
                <div>
                  <div className="text-sm font-semibold text-[#141414]">Download started.</div>
                  <div className="text-xs text-[#707070] mt-0.5">
                    Saved <span className="font-medium text-[#141414]">{downloadedFileName}</span> to your device.
                  </div>
                </div>
              </div>

              <div className="bg-[#ffffff] rounded-[16px] border border-[#f0f0f0] p-4 space-y-2 text-xs">
                <div className="font-semibold text-[#141414]">Installation steps:</div>
                <ol className="list-decimal list-inside space-y-1 text-[#707070]">
                  <li>In WordPress Admin, go to <strong>Plugins ➔ Add New ➔ Upload</strong></li>
                  <li>Select the downloaded <code className="bg-[#f0f0f0] px-1 py-0.5 rounded text-[#141414] font-medium">BotFence.zip</code> file</li>
                  <li>Click <strong>Activate</strong></li>
                </ol>
              </div>

              {/* WP-CLI command */}
              <div className="bg-[#f3f3f3] rounded-[16px] p-3 text-xs flex items-center justify-between gap-2">
                <div className="truncate font-mono text-[11px] text-[#707070]">
                  wp plugin install {downloadedFileName} --activate
                </div>
                <button
                  type="button"
                  onClick={handleCopyCli}
                  className="pill-button shrink-0 px-3 py-1 bg-[#ffffff] border border-[#e0e0e0] text-[#141414] text-[11px] font-medium hover:bg-[#f3f3f3]"
                >
                  {copiedCli ? 'Copied' : 'Copy'}
                </button>
              </div>

              <div className="pt-2">
                <button
                  type="button"
                  onClick={onClose}
                  className="pill-button w-full bg-[#141414] text-[#ffffff] font-semibold text-xs py-3 hover:bg-[#262626] cursor-pointer"
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

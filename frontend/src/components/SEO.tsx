import React, { useEffect } from 'react';

export interface SEOProps {
  title: string;
  description: string;
  canonicalUrl: string;
  ogType?: 'website' | 'article';
  ogImage?: string;
  structuredData?: Record<string, any> | Array<Record<string, any>>;
  articlePublishedTime?: string;
  articleModifiedTime?: string;
}

export const SEO: React.FC<SEOProps> = ({
  title,
  description,
  canonicalUrl,
  ogType = 'website',
  ogImage = 'https://vighnaghna.amalskumar.dev/og-image.png',
  structuredData,
  articlePublishedTime,
  articleModifiedTime,
}) => {
  useEffect(() => {
    // 1. Update Title
    document.title = title;

    // 2. Helper to set or create meta tags
    const setMeta = (nameOrProperty: string, isProperty: boolean, content: string) => {
      const attr = isProperty ? 'property' : 'name';
      let el = document.querySelector(`meta[${attr}="${nameOrProperty}"]`);
      if (!el) {
        el = document.createElement('meta');
        el.setAttribute(attr, nameOrProperty);
        document.head.appendChild(el);
      }
      el.setAttribute('content', content);
    };

    setMeta('description', false, description);
    setMeta('title', false, title);

    // Open Graph
    setMeta('og:title', true, title);
    setMeta('og:description', true, description);
    setMeta('og:url', true, canonicalUrl);
    setMeta('og:type', true, ogType);
    setMeta('og:image', true, ogImage);
    setMeta('og:site_name', true, 'Vighnaghna');

    // Twitter Card
    setMeta('twitter:card', false, 'summary_large_image');
    setMeta('twitter:title', false, title);
    setMeta('twitter:description', false, description);
    setMeta('twitter:url', false, canonicalUrl);
    setMeta('twitter:image', false, ogImage);

    // Article Meta
    if (ogType === 'article') {
      if (articlePublishedTime) {
        setMeta('article:published_time', true, articlePublishedTime);
      }
      if (articleModifiedTime) {
        setMeta('article:modified_time', true, articleModifiedTime);
      }
      setMeta('article:author', true, 'Amal S Kumar');
    }

    // 3. Update Canonical Link
    let canonical = document.querySelector('link[rel="canonical"]');
    if (!canonical) {
      canonical = document.createElement('link');
      canonical.setAttribute('rel', 'canonical');
      document.head.appendChild(canonical);
    }
    canonical.setAttribute('href', canonicalUrl);

    // 4. Update Dynamic JSON-LD structured data
    let dynamicScript = document.getElementById('vighnaghna-page-ld');
    if (dynamicScript) {
      dynamicScript.remove();
    }
    if (structuredData) {
      dynamicScript = document.createElement('script');
      dynamicScript.id = 'vighnaghna-page-ld';
      dynamicScript.setAttribute('type', 'application/ld+json');
      dynamicScript.textContent = JSON.stringify(structuredData);
      document.head.appendChild(dynamicScript);
    }

    return () => {
      const el = document.getElementById('vighnaghna-page-ld');
      if (el) el.remove();
    };
  }, [title, description, canonicalUrl, ogType, ogImage, structuredData, articlePublishedTime, articleModifiedTime]);

  return null;
};

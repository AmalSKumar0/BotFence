import test from 'node:test';
import assert from 'node:assert/strict';
import {
  validateDownloadForm,
  analyzeFormSubmission,
  generatePluginManifest,
} from './vighnaghna-engine';

test('validateDownloadForm rejects empty fields', () => {
  const result = validateDownloadForm({ name: '', email: '', wouldReview: false });
  assert.equal(result.valid, false);
  assert.ok(result.errors.name);
  assert.ok(result.errors.email);
});

test('validateDownloadForm rejects invalid email', () => {
  const result = validateDownloadForm({ name: 'Alex Developer', email: 'not-an-email', wouldReview: true });
  assert.equal(result.valid, false);
  assert.ok(result.errors.email);
  assert.equal(result.errors.name, undefined);
});

test('validateDownloadForm accepts valid data with review true or false', () => {
  const result = validateDownloadForm({
    name: 'Sarah Connor',
    email: 'sarah@skynet-defense.com',
    wouldReview: true,
  });
  assert.equal(result.valid, true);
  assert.deepEqual(result.errors, {});
});

test('analyzeFormSubmission flags honeypot-filled submissions as blocked', () => {
  const result = analyzeFormSubmission({
    fillDurationMs: 400,
    honeypotFilled: true,
    mouseEventsCount: 0,
    isHeadless: true,
  });
  assert.equal(result.isBlocked, true);
  assert.equal(result.verdict, 'BLOCKED');
  assert.ok(result.threatScore >= 80);
});

test('analyzeFormSubmission passes natural human interaction', () => {
  const result = analyzeFormSubmission({
    fillDurationMs: 3400,
    honeypotFilled: false,
    mouseEventsCount: 28,
    isHeadless: false,
  });
  assert.equal(result.isBlocked, false);
  assert.equal(result.verdict, 'VERIFIED_HUMAN');
  assert.equal(result.threatScore, 0);
});

test('generatePluginManifest produces complete WordPress package with correct headers', () => {
  const manifest = generatePluginManifest({
    name: 'Marcus Brody',
    email: 'marcus@archaeology.org',
    wouldReview: true,
  });
  assert.equal(manifest.version, '1.0.0');
  assert.ok(manifest.filename.includes('1.0.0'));
  assert.equal(manifest.files.length, 3);
  
  const mainPhp = manifest.files.find(f => f.name === 'vighnaghna/vighnaghna.php');
  assert.ok(mainPhp);
  assert.ok(mainPhp.content.includes('Plugin Name:       Vighnaghna'));
  assert.ok(mainPhp.content.includes('Marcus Brody'));
  assert.ok(mainPhp.content.includes('add_action(\'init\''));
});

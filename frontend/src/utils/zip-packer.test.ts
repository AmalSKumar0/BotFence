import test from 'node:test';
import assert from 'node:assert/strict';
import { createZipBlob } from './zip-packer';

test('createZipBlob returns a non-empty application/zip blob with correct PK signatures', async () => {
  const files = [
    { name: 'botfence/botfence.php', content: '<?php echo "hello"; ?>' },
    { name: 'botfence/readme.txt', content: '=== BotFence ===' },
  ];
  const blob = createZipBlob(files);
  assert.equal(blob.type, 'application/zip');
  assert.ok(blob.size > 100);

  const buffer = await blob.arrayBuffer();
  const bytes = new Uint8Array(buffer);
  
  // Local file header signature 0x04034b50 (little endian: 0x50, 0x4b, 0x03, 0x04 = 'PK\x03\x04')
  assert.equal(bytes[0], 0x50);
  assert.equal(bytes[1], 0x4b);
  assert.equal(bytes[2], 0x03);
  assert.equal(bytes[3], 0x04);
});

/**
 * Pure TypeScript Minimal ZIP Archive Packer (Store mode, uncompressed).
 * Generates valid standard PKZIP archives entirely in memory using Uint8Array/Blob.
 * Compatible with macOS Archive Utility, Windows Explorer, unzip, and WordPress plugin uploader.
 */

interface ZipEntry {
  path: string;
  data: Uint8Array;
}

export function createZipBlob(files: { name: string; content: string }[]): Blob {
  const encoder = new TextEncoder();
  const entries: ZipEntry[] = files.map(f => ({
    path: f.name,
    data: encoder.encode(f.content),
  }));

  const localHeaders: Uint8Array[] = [];
  const centralHeaders: Uint8Array[] = [];
  let offset = 0;

  // CRC32 table
  const crcTable = new Uint32Array(256);
  for (let i = 0; i < 256; i++) {
    let c = i;
    for (let k = 0; k < 8; k++) {
      c = (c & 1) ? (0xedb88320 ^ (c >>> 1)) : (c >>> 1);
    }
    crcTable[i] = c >>> 0;
  }

  function crc32(buf: Uint8Array): number {
    let crc = 0xffffffff;
    for (let i = 0; i < buf.length; i++) {
      crc = (crcTable[(crc ^ buf[i]) & 0xff] ^ (crc >>> 8)) >>> 0;
    }
    return (crc ^ 0xffffffff) >>> 0;
  }

  // DOS time (2026-11-08 12:00:00)
  const dosTime = (12 << 11) | (0 << 5) | (0 >> 1);
  const dosDate = ((2026 - 1980) << 9) | (11 << 5) | 8;

  for (const entry of entries) {
    const pathBytes = encoder.encode(entry.path);
    const dataCrc = crc32(entry.data);
    const size = entry.data.length;

    // Local File Header (30 bytes + path length)
    const localHeader = new Uint8Array(30 + pathBytes.length);
    const lView = new DataView(localHeader.buffer);
    lView.setUint32(0, 0x04034b50, true); // signature
    lView.setUint16(4, 20, true);         // version needed (2.0)
    lView.setUint16(6, 0, true);          // flags
    lView.setUint16(8, 0, true);          // compression method (0 = store)
    lView.setUint16(10, dosTime, true);   // mod time
    lView.setUint16(12, dosDate, true);   // mod date
    lView.setUint32(14, dataCrc, true);   // crc32
    lView.setUint32(18, size, true);      // compressed size
    lView.setUint32(22, size, true);      // uncompressed size
    lView.setUint16(26, pathBytes.length, true); // filename length
    lView.setUint16(28, 0, true);         // extra field length
    localHeader.set(pathBytes, 30);

    localHeaders.push(localHeader, entry.data);

    // Central Directory Header (46 bytes + path length)
    const centralHeader = new Uint8Array(46 + pathBytes.length);
    const cView = new DataView(centralHeader.buffer);
    cView.setUint32(0, 0x02014b50, true); // signature
    cView.setUint16(4, 20, true);         // version made by
    cView.setUint16(6, 20, true);         // version needed
    cView.setUint16(8, 0, true);          // flags
    cView.setUint16(10, 0, true);         // compression (0 = store)
    cView.setUint16(12, dosTime, true);   // mod time
    cView.setUint16(14, dosDate, true);   // mod date
    cView.setUint32(16, dataCrc, true);   // crc32
    cView.setUint32(20, size, true);      // compressed size
    cView.setUint32(24, size, true);      // uncompressed size
    cView.setUint16(28, pathBytes.length, true); // filename length
    cView.setUint16(30, 0, true);         // extra field length
    cView.setUint16(32, 0, true);         // comment length
    cView.setUint16(34, 0, true);         // disk start
    cView.setUint16(36, 0, true);         // internal attributes
    cView.setUint32(38, 0, true);         // external attributes
    cView.setUint32(42, offset, true);    // local header offset
    centralHeader.set(pathBytes, 46);

    centralHeaders.push(centralHeader);
    offset += localHeader.length + size;
  }

  const centralDirOffset = offset;
  let centralDirSize = 0;
  for (const h of centralHeaders) centralDirSize += h.length;

  // End of Central Directory Record (22 bytes)
  const eocd = new Uint8Array(22);
  const eView = new DataView(eocd.buffer);
  eView.setUint32(0, 0x06054b50, true);          // signature
  eView.setUint16(4, 0, true);                   // disk number
  eView.setUint16(6, 0, true);                   // disk with start of central directory
  eView.setUint16(8, entries.length, true);      // total entries on this disk
  eView.setUint16(10, entries.length, true);     // total entries
  eView.setUint32(12, centralDirSize, true);     // central dir size
  eView.setUint32(16, centralDirOffset, true);   // central dir offset
  eView.setUint16(20, 0, true);                  // comment length

  const allParts = [...localHeaders, ...centralHeaders, eocd];
  return new Blob(allParts as unknown as BlobPart[], { type: 'application/zip' });
}

/**
 * Triggers a real browser file download for a Blob
 */
export function triggerFileDownload(blob: Blob, filename: string): void {
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = filename;
  document.body.appendChild(anchor);
  anchor.click();
  setTimeout(() => {
    document.body.removeChild(anchor);
    URL.revokeObjectURL(url);
  }, 200);
}

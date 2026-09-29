// Renders theme/og.png, the 1200x630 link preview card behind the og:image /
// twitter:image tags (theme/ is copied into dist/ by build.mjs, so the card ships
// at /og.png). Run it when the wording or the palette changes:
//
//   node make-og.mjs
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const OUT = join(dirname(fileURLToPath(import.meta.url)), 'theme', 'og.png');

// Same palette as theme/style.css, with the favicon's Sheets glyph.
const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <rect width="1200" height="630" fill="#ffffff"/>
  <rect width="1200" height="10" fill="#0f9d58"/>

  <g transform="translate(80,74) scale(1.75)">
    <rect width="32" height="32" rx="7" fill="#0f9d58"/>
    <path fill="#fff" d="M9 8h14v16H9zm2 2v3h4v-3zm6 0v3h4v-3zm-6 5v3h4v-3zm6 0v3h4v-3zm-6 5v2h4v-2zm6 0v2h4v-2z"/>
  </g>
  <text x="166" y="106" font-family="Helvetica Neue" font-size="30" font-weight="600" fill="#1f2328">Sheet I18n</text>
  <text x="166" y="140" font-family="Helvetica Neue" font-size="24" fill="#59636e">Laravel package</text>

  <text x="80" y="268" font-family="Helvetica Neue" font-size="66" font-weight="700" fill="#1f2328" letter-spacing="-1.5">Your language files,</text>
  <text x="80" y="344" font-family="Helvetica Neue" font-size="66" font-weight="700" fill="#0f9d58" letter-spacing="-1.5">built in a spreadsheet</text>

  <text x="80" y="408" font-family="Helvetica Neue" font-size="28" fill="#59636e">Push lang/en to Google Sheets, translate it there, pull the PHP files back.</text>

  <rect x="80" y="466" width="860" height="76" rx="10" fill="#f6f8fa" stroke="#d1d9e0" stroke-width="2"/>
  <text x="112" y="513" font-family="SF Mono, Menlo" font-size="23" fill="#0f9d58">$</text>
  <text x="140" y="513" font-family="SF Mono, Menlo" font-size="23" fill="#1f2328">composer require momik/laravel-google-sheet-i18n</text>

  <text x="1120" y="580" text-anchor="end" font-family="Helvetica Neue" font-size="22" font-weight="600" letter-spacing="1" fill="#59636e">sheet-i18n.momik.dev</text>
</svg>`;

await sharp(Buffer.from(svg)).png({ compressionLevel: 9 }).toFile(OUT);
console.log(`✓ ${OUT}`);

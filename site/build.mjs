// Builds the static docs site for sheet-i18n.momik.dev from ../docs/*.md into ./dist.
//   node build.mjs
// Markdown stays the single source of truth (it also renders on GitHub).
import { createHash } from 'node:crypto';
import { cp, mkdir, readFile, readdir, rm, writeFile } from 'node:fs/promises';
import { basename, dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { Marked } from 'marked';
import sharp from 'sharp';
import { createHighlighter } from 'shiki';

const ROOT = dirname(fileURLToPath(import.meta.url));
const DOCS = join(ROOT, '..', 'docs');
const DIST = join(ROOT, 'dist');
const SITE_URL = 'https://sheet-i18n.momik.dev';
const REPO_URL = 'https://github.com/PG-Momik/laravel-google-sheet-i18n';
const PACKAGIST_URL = 'https://packagist.org/packages/momik/laravel-google-sheet-i18n';

// Sidebar order. `file` is relative to docs/, `slug` is the URL path.
const PAGES = [
  { file: 'index.md', slug: '', nav: 'Overview' },
  { file: 'google-setup.md', slug: 'google-setup', nav: 'Google setup' },
  { file: 'installation.md', slug: 'installation', nav: 'Installation' },
  { file: 'usage.md', slug: 'usage', nav: 'Usage' },
];

const IMAGE_WIDTHS = [800, 1600];
// Applies the saved light/dark choice before first paint. Allowed by hash in the CSP (theme/_headers).
const THEME_SCRIPT = "try{const t=localStorage.getItem('theme');if(t)document.documentElement.dataset.theme=t}catch{}";
const LANG_ALIASES = { env: 'ini', sh: 'bash', shell: 'bash' };

const escapeHtml = (s) =>
  s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
const slugify = (s) =>
  s
    .toLowerCase()
    .replace(/<[^>]+>/g, '')
    .replace(/[^\w\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-');
const pageUrl = (slug) => (slug ? `/${slug}/` : '/');

// ---------------------------------------------------------------- images

const images = new Map(); // "screenshots/x.png" -> { base, width, height }

async function processImage(src) {
  if (images.has(src)) return images.get(src);
  const input = join(DOCS, src);
  const base = basename(src).replace(/\.\w+$/, '');
  const { width, height } = await sharp(input).metadata();
  await mkdir(join(DIST, 'img'), { recursive: true });
  for (const w of IMAGE_WIDTHS) {
    await sharp(input)
      .resize({ width: Math.min(w, width), withoutEnlargement: true })
      .webp({ quality: 80 })
      .toFile(join(DIST, 'img', `${base}-${w}.webp`));
  }
  const info = { base, width, height };
  images.set(src, info);
  return info;
}

// ---------------------------------------------------------------- markdown

const highlighter = await createHighlighter({
  themes: ['github-light', 'github-dark'],
  langs: ['bash', 'ini', 'php', 'json'],
});

function highlight(code, lang) {
  const language = LANG_ALIASES[lang] ?? lang ?? 'text';
  const loaded = highlighter.getLoadedLanguages().includes(language) ? language : 'text';
  const html = highlighter.codeToHtml(code, {
    lang: loaded,
    themes: { light: 'github-light', dark: 'github-dark' },
    defaultColor: false,
  });
  const label = lang ? `<span class="code-lang">${escapeHtml(lang)}</span>` : '';
  return `<div class="code">${label}<button class="copy" type="button" aria-label="Copy code">Copy</button>${html}</div>`;
}

/** Rewrites links between docs ("usage.md#x" -> "/usage/#x") and marks external ones. */
function rewriteHref(href) {
  const match = href.match(/^([\w-]+)\.md(#.*)?$/);
  if (match) {
    const page = PAGES.find((p) => p.file === `${match[1]}.md`);
    if (page) return { href: pageUrl(page.slug) + (match[2] ?? ''), external: false };
  }
  return { href, external: /^https?:\/\//.test(href) };
}

async function renderPage(markdown) {
  const headings = [];
  const pendingImages = [];
  const marked = new Marked({
    gfm: true,
    renderer: {
      heading({ tokens, depth }) {
        const text = this.parser.parseInline(tokens);
        if (depth === 1) return `<h1>${text}</h1>\n`;
        const id = slugify(text);
        if (depth === 2) headings.push({ id, text: text.replace(/<[^>]+>/g, '') });
        return `<h${depth} id="${id}"><a class="anchor" href="#${id}" aria-hidden="true">#</a>${text}</h${depth}>\n`;
      },
      code({ text, lang }) {
        return highlight(text, (lang ?? '').trim().split(/\s/)[0] || undefined);
      },
      link({ href, title, tokens }) {
        const { href: to, external } = rewriteHref(href);
        const attrs = external ? ' target="_blank" rel="noopener"' : '';
        const t = title ? ` title="${escapeHtml(title)}"` : '';
        return `<a href="${escapeHtml(to)}"${t}${attrs}>${this.parser.parseInline(tokens)}</a>`;
      },
      image({ href, text }) {
        const key = `@@IMG${pendingImages.length}@@`;
        pendingImages.push({ key, href, alt: text });
        return key;
      },
    },
  });

  let html = marked.parse(markdown);
  for (const { key, href, alt } of pendingImages) {
    const { base, width, height } = await processImage(href);
    const w = Math.min(IMAGE_WIDTHS.at(-1), width);
    const h = Math.round((height * w) / width);
    const srcset = IMAGE_WIDTHS.map((iw) => `/img/${base}-${iw}.webp ${Math.min(iw, width)}w`).join(', ');
    html = html.replace(
      key,
      `<a class="shot" href="/img/${base}-${IMAGE_WIDTHS.at(-1)}.webp" target="_blank" rel="noopener">` +
        `<img src="/img/${base}-${IMAGE_WIDTHS[0]}.webp" srcset="${srcset}" sizes="(min-width: 1100px) 760px, 100vw"` +
        ` width="${w}" height="${h}" alt="${escapeHtml(alt)}" loading="lazy" decoding="async"></a>`,
    );
  }
  const title = markdown.match(/^#\s+(.+)$/m)?.[1]?.trim() ?? 'Docs';
  const description =
    markdown
      .split('\n')
      .find((l) => l.trim() && !l.startsWith('#') && !l.startsWith('-') && !l.startsWith('!'))
      ?.replace(/[*_`[\]]|\(.*?\)/g, '')
      .trim() ?? '';
  return { html, title, description, headings };
}

// ---------------------------------------------------------------- layout

const ICON_GITHUB =
  '<svg viewBox="0 0 16 16" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>';

function layout({ page, title, description, html, headings }) {
  const nav = PAGES.map(
    (p) =>
      `<li><a href="${pageUrl(p.slug)}"${p === page ? ' aria-current="page"' : ''}>${escapeHtml(p.nav)}</a></li>`,
  ).join('');
  const toc = headings.length
    ? `<nav class="toc" aria-label="On this page"><p>On this page</p><ul>${headings
        .map((h) => `<li><a href="#${h.id}">${escapeHtml(h.text)}</a></li>`)
        .join('')}</ul></nav>`
    : '';
  const index = PAGES.indexOf(page);
  const prev = PAGES[index - 1];
  const next = PAGES[index + 1];
  const pager = `<nav class="pager" aria-label="Pagination">${
    prev ? `<a class="prev" href="${pageUrl(prev.slug)}"><span>Previous</span>${escapeHtml(prev.nav)}</a>` : '<span></span>'
  }${next ? `<a class="next" href="${pageUrl(next.slug)}"><span>Next</span>${escapeHtml(next.nav)}</a>` : ''}</nav>`;
  const fullTitle = page.slug ? `${title} · Laravel Google Sheets I18n` : 'Laravel Google Sheets I18n';

  return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${escapeHtml(fullTitle)}</title>
<meta name="description" content="${escapeHtml(description)}">
<link rel="canonical" href="${SITE_URL}${pageUrl(page.slug)}">
<meta property="og:title" content="${escapeHtml(fullTitle)}">
<meta property="og:description" content="${escapeHtml(description)}">
<meta property="og:url" content="${SITE_URL}${pageUrl(page.slug)}">
<meta property="og:type" content="website">
<meta name="theme-color" content="#0f172a">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/style.css">
<script>${THEME_SCRIPT}</script>
</head>
<body>
<a class="skip" href="#content">Skip to content</a>
<header class="topbar">
  <button class="menu" type="button" aria-label="Menu" aria-expanded="false" aria-controls="sidebar">☰</button>
  <a class="brand" href="/"><img class="logo" src="/favicon.svg" width="24" height="24" alt=""> Sheet I18n <span class="tag">Laravel</span></a>
  <div class="actions">
    <a href="${PACKAGIST_URL}" target="_blank" rel="noopener">Packagist</a>
    <a class="icon" href="${REPO_URL}" target="_blank" rel="noopener" aria-label="GitHub repository">${ICON_GITHUB}</a>
    <button class="theme" type="button" aria-label="Toggle dark mode">◐</button>
  </div>
</header>
<div class="shell">
  <aside id="sidebar" class="sidebar"><nav aria-label="Docs"><ul>${nav}</ul></nav></aside>
  <main id="content" class="content">
    <article class="prose">${html}</article>
    ${pager}
    <footer class="footer">
      <a href="${REPO_URL}/edit/main/docs/${page.file}" target="_blank" rel="noopener">Edit this page on GitHub</a>
      <span>MIT licensed · by <a href="https://momik.dev" target="_blank" rel="noopener">Momik Shrestha</a></span>
    </footer>
  </main>
  ${toc}
</div>
<script src="/app.js" defer></script>
</body>
</html>
`;
}

// ---------------------------------------------------------------- build

await rm(DIST, { recursive: true, force: true });
await mkdir(DIST, { recursive: true });
await cp(join(ROOT, 'theme'), DIST, { recursive: true });
const scriptHash = createHash('sha256').update(THEME_SCRIPT).digest('base64');
const headers = await readFile(join(DIST, '_headers'), 'utf8');
await writeFile(join(DIST, '_headers'), headers.replace('THEME_SCRIPT_HASH', scriptHash));

const docFiles = new Set((await readdir(DOCS)).filter((f) => f.endsWith('.md')));
for (const page of PAGES) {
  if (!docFiles.has(page.file)) throw new Error(`docs/${page.file} is listed in PAGES but missing`);
  docFiles.delete(page.file);
  const rendered = await renderPage(await readFile(join(DOCS, page.file), 'utf8'));
  const outDir = join(DIST, page.slug);
  await mkdir(outDir, { recursive: true });
  await writeFile(join(outDir, 'index.html'), layout({ page, ...rendered }));
  console.log(`✓ ${pageUrl(page.slug)}  ${rendered.title}`);
}
if (docFiles.size) console.warn(`! not in the sidebar (add to PAGES): ${[...docFiles].join(', ')}`);

const notFound = {
  page: { file: 'index.md', slug: '404', nav: 'Not found' },
  title: 'Page not found',
  description: 'This page does not exist.',
  html: '<h1>Page not found</h1><p>That page doesn’t exist. Try the <a href="/">overview</a>.</p>',
  headings: [],
};
await writeFile(join(DIST, '404.html'), layout(notFound).replace(/<link rel="canonical"[^>]*>\n/, ''));
await writeFile(
  join(DIST, 'sitemap.xml'),
  `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${PAGES.map(
    (p) => `  <url><loc>${SITE_URL}${pageUrl(p.slug)}</loc></url>`,
  ).join('\n')}\n</urlset>\n`,
);
await writeFile(join(DIST, 'robots.txt'), `User-agent: *\nAllow: /\nSitemap: ${SITE_URL}/sitemap.xml\n`);
console.log(`✓ ${images.size} images → WebP (${IMAGE_WIDTHS.join('/')}px)`);

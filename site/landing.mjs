/*
  The landing page at /. Not generated from markdown: it has its own shell (no docs sidebar), its
  own stylesheet (theme/landing.css) and the sheet demo (theme/landing.js).

  Laid out as a spreadsheet is: a mono label column on the left, content on the right, hairlines
  between rows, nothing centred. The demo's rows come from theme/demo-data.js, shared with the
  browser, so every table and code block below is real package output rather than a mock-up.
*/
import { FILES, LOCALES, PLACEHOLDER_RE, ROWS, SOURCE, TOTAL_FILES, TOTAL_STRINGS } from './theme/demo-data.js';

const escapeHtml = (s) =>
  s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

/** ":seconds" is marked wherever it appears, because surviving the round trip is the whole claim. */
const withPlaceholders = (text) =>
  escapeHtml(text).replace(PLACEHOLDER_RE, (m) => `<span class="ph">${m}</span>`);

const DEFAULT_LOCALE = LOCALES[0];
const INSTALL = 'composer require momik/laravel-google-sheet-i18n';

/** The row the hero fans out, and the one the masking panel walks through. */
const HERO_ROW = ROWS.find((r) => r.key === 'throttle');
const HERO_LOCALES = ['fr', 'es', 'ne', 'ja'];

// ---------------------------------------------------------------- sections

const SECTIONS = [
  { id: 'how', nav: 'How it runs' },
  { id: 'details', nav: 'Details' },
  { id: 'dashboard', nav: 'Dashboard' },
  { id: 'start', nav: 'Get started' },
];

const row = (id, body, mod = '') =>
  `<section class="row reveal${mod}" id="${id}">${body}</section>`;

/** Sticky index down the left rail. It is navigation, so it earns the space the gutter takes. */
const indexNav = () =>
  `<nav class="index" aria-label="Sections"><ul>${SECTIONS.map(
    (x) => `<li><a href="#${x.id}">${escapeHtml(x.nav)}</a></li>`,
  ).join('')}</ul></nav>`;

function hero() {
  const fan = HERO_LOCALES.map(
    (code) =>
      `<div class="dst"><span class="who">${code}</span><span>${withPlaceholders(HERO_ROW.t[code])}</span></div>`,
  ).join('');

  return `<section class="hero">
  <div>
    <h1>Your language files, built in a <b>spreadsheet</b></h1>
    <p class="say">Push every string in <code>lang/en</code> up to Google Sheets, translate it there, and pull the finished PHP files back.</p>
    <div class="cta">
      <button class="install" type="button" data-copy="${escapeHtml(INSTALL)}" aria-label="Copy the install command">
        <span class="prompt">$</span>${escapeHtml(INSTALL)}<span class="copy-hint">Copy</span>
      </button>
      <a class="btn btn-primary" href="/docs/">Read the docs</a>
    </div>
  </div>
  <div class="fanout">
    <p class="file">lang/en/auth.php</p>
    <div class="src"><span class="who">${SOURCE}</span><span>${withPlaceholders(HERO_ROW.en)}</span></div>
    ${fan}
  </div>
</section>`;
}

/** The sheet, pre-filled for the default locale so it reads correctly with JavaScript off. */
function sheetTable(locale) {
  const rows = ROWS.map(
    (r, i) =>
      `<tr><th class="rownum" scope="row">${i + 2}</th>` +
      `<td class="file">${escapeHtml(r.file)}</td><td class="key">${escapeHtml(r.key)}</td>` +
      `<td class="tag">default</td><td>${withPlaceholders(r.en)}</td>` +
      `<td class="out done">${withPlaceholders(r.t[locale.code])}</td></tr>`,
  ).join('');
  return `<table>
<colgroup><col class="c-num"><col class="c-file"><col class="c-key"><col class="c-tag"><col><col></colgroup>
<thead>
<tr class="cols"><th class="corner"></th><th>A</th><th>B</th><th class="tag">C</th><th>D</th><th class="col-target">E</th></tr>
<tr class="hdr"><th class="rownum" scope="row">1</th><td class="file">File</td><td class="key">Key</td><td class="tag">Tag</td><td>${SOURCE.toUpperCase()}</td><td class="locale">${locale.code.toUpperCase()}</td></tr>
</thead>
<tbody>${rows}<tr class="more"><td class="rownum"></td><td colspan="5">${TOTAL_STRINGS - ROWS.length} more rows, from validation.php and pagination.php</td></tr></tbody>
</table>`;
}

/** The PHP file the demo writes, rendered statically for the first tab. */
function phpFile(file, locale) {
  const lines = ROWS.filter((r) => r.file === file)
    .map(
      (r) =>
        `<span class="ln">    <span class="tok-punc">'</span><span class="tok-key">${escapeHtml(r.key)}</span>` +
        `<span class="tok-punc">' => '</span><span class="tok-str">${withPlaceholders(
          r.t[locale.code].replace(/\\/g, '\\\\').replace(/'/g, "\\'"),
        )}</span><span class="tok-punc">',</span></span>`,
    )
    .join('');
  return (
    `<span class="ln"><span class="tok-tag">&lt;?php</span></span><span class="ln"> </span>` +
    `<span class="ln"><span class="tok-punc">return [</span></span>` +
    lines +
    `<span class="ln"><span class="tok-punc">];</span></span>`
  );
}

function demo() {
  const l = DEFAULT_LOCALE;
  const chips = LOCALES.map(
    (x) =>
      `<button class="chip" type="button" data-locale="${x.code}" aria-pressed="${x.code === l.code}"` +
      ` title="${escapeHtml(x.name)}">${escapeHtml(x.label)}</button>`,
  ).join('');
  const tabs = FILES.map(
    (f, i) =>
      `<button type="button" role="tab" data-file="${escapeHtml(f)}" aria-selected="${i === 0}">lang/${l.code}/${escapeHtml(f)}</button>`,
  ).join('');

  return `<h2>Pick a language and watch the column fill</h2>
<p class="say">The round trip the package makes, without the network. Every string and translation below is real.</p>
<div class="demo-frame" data-locale="${l.code}">
  <div class="pane sheet-pane">
    <div class="pane-head">Google Sheets<span class="sheet-name">one sheet per day</span></div>
    <div class="formula-bar"><span class="fx">fx</span><span class="formula idle">${ROWS.length} of ${TOTAL_STRINGS} rows shown</span></div>
    <div class="sheet">${sheetTable(l)}</div>
    <div class="locales"><span class="from">source <b>${SOURCE}</b></span>${chips}</div>
  </div>
  <div class="pane">
    <div class="out-tabs" role="tablist" aria-label="Generated language files">${tabs}</div>
    <pre class="out-code">${phpFile(FILES[0], l)}</pre>
  </div>
  <p class="demo-status done"><span class="dot"></span><span class="text">Pulled back into lang/${l.code}/, ${TOTAL_STRINGS} ${l.name} strings across ${TOTAL_FILES} files</span></p>
</div>
<p class="demo-note">The <code>=GOOGLETRANSLATE()</code> column is a first draft, not a final answer. Anyone with the link can correct a cell, and the correction is what ends up in your language file.</p>`;
}

function steps() {
  return `<h2>Three commands and a spreadsheet</h2>
<div class="steps">
  <div class="step">
    <h3>Connect</h3>
    <div>
      <p>Create a Google Cloud service account, share one spreadsheet with its email address, and put the sheet ID in your <code>.env</code>. This is the fiddly part, so it is documented click by click.</p>
      <p><a href="/google-setup/">The Google setup walkthrough</a></p>
    </div>
  </div>
  <div class="step">
    <h3>Generate</h3>
    <div>
      <p>Run <code>php artisan translate:sheet fr</code>. Every string in <code>lang/en</code> goes up to a sheet named for today, and a <code>=GOOGLETRANSLATE()</code> column is added for the target locale.</p>
    </div>
  </div>
  <div class="step">
    <h3>Pull back</h3>
    <div>
      <p>Once Sheets has computed the column, the values are written to <code>lang/fr/*.php</code> with placeholders restored and nested keys intact.</p>
      <p>Run it again next month and only the new keys are appended.</p>
    </div>
  </div>
</div>`;
}

function features() {
  const masked = escapeHtml(HERO_ROW.en).replace(PLACEHOLDER_RE, '<span class="tok">[[T_0]]</span>');
  const maskedFr = escapeHtml(HERO_ROW.t.fr).replace(PLACEHOLDER_RE, '<span class="tok">[[T_0]]</span>');

  return `<h2>The parts a naive script gets wrong</h2>
<div class="bento">
  <div class="feature wide">
    <h3>Placeholders come back intact</h3>
    <p>Google Translate will happily translate the word <code>seconds</code> inside <code>:seconds</code> and hand you a broken string. So the placeholders are swapped for opaque tokens on the way out and restored on the way back.</p>
    <dl class="mask">
      <div><dt>source</dt><dd>${withPlaceholders(HERO_ROW.en)}</dd></div>
      <div><dt>masked</dt><dd>${masked}</dd></div>
      <div><dt>translated</dt><dd>${maskedFr}</dd></div>
      <div><dt>restored</dt><dd>${withPlaceholders(HERO_ROW.t.fr)}</dd></div>
    </dl>
  </div>
  <div class="feature tinted">
    <h3>Translators never touch Git</h3>
    <p>Share one spreadsheet link. No repository access, no pull requests, and nobody editing JSON by hand at midnight.</p>
  </div>
  <div class="feature">
    <h3>Only new keys are added</h3>
    <p>Rows are matched on file, key and tag, so the wording your team corrected last quarter is still there after the next run.</p>
  </div>
  <div class="feature">
    <h3>A formula starts it, a person finishes it</h3>
    <p>Overwrite any cell in the target column and your version is what lands in the language file. The formula is only the first pass.</p>
  </div>
  <div class="feature">
    <h3>PHP and JSON, nested keys included</h3>
    <p>Everything in <code>lang/en/</code> and <code>lang/en.json</code> goes up and comes back in the same shape. Tag a run with <code>--tag=JIRA-4367</code> to keep a release traceable.</p>
  </div>
</div>`;
}

function dashboard(shotImg) {
  return `<h2>Or drive it from the browser</h2>
<p class="say">The dashboard runs the same generator, streams the console output as it goes, and takes several locales in one submission. Gate it with your own middleware, or switch it off.</p>
<div class="shot-frame">
  <div class="bar">/translation-manager</div>
  ${shotImg}
</div>`;
}

function start(cliCode, envCode, REPO_URL, PACKAGIST_URL) {
  return `<h2>What you actually type</h2>
<div class="start-grid">
  <div>
    ${cliCode}
    ${envCode}
  </div>
  <div>
    <dl class="spec">
      <div><dt>PHP</dt><dd>8.0+</dd></div>
      <div><dt>Laravel</dt><dd>8 to 12</dd></div>
      <div><dt>License</dt><dd>MIT</dd></div>
      <div><dt>Source</dt><dd><a href="${REPO_URL}" target="_blank" rel="noopener">GitHub</a></dd></div>
      <div><dt>Package</dt><dd><a href="${PACKAGIST_URL}" target="_blank" rel="noopener">Packagist</a></dd></div>
    </dl>
  </div>
</div>`;
}

// ---------------------------------------------------------------- page

export function renderLandingPage({ SITE_URL, REPO_URL, PACKAGIST_URL, ICON_GITHUB, THEME_SCRIPT, highlight, shot }) {
  const title = 'Laravel Google Sheets I18n';
  const description =
    'Manage Laravel translations in Google Sheets. Push your language keys to a spreadsheet, translate them with =GOOGLETRANSLATE(), and pull finished lang files back.';

  const cliCode = highlight(
    `# Generate French from English\nphp artisan translate:sheet fr\n\n# Any source locale, and a tag for traceability\nphp artisan translate:sheet ne --source=en --tag=JIRA-4367`,
    'bash',
  );
  const envCode = highlight(
    `GOOGLE_SHEET_I18N_ID=your_spreadsheet_id\nGOOGLE_APPLICATION_CREDENTIALS=storage/app/google-service-account.json`,
    'env',
  );

  const shotImg = shot
    ? `<img src="/img/${shot.base}-800.webp" srcset="/img/${shot.base}-800.webp 800w, /img/${shot.base}-1600.webp 1600w"` +
      ` sizes="(min-width: 1180px) 990px, 100vw" width="${shot.w}" height="${shot.h}"` +
      ` alt="The Translation Manager dashboard: a locale generator form beside a live console listing each file as it is uploaded and saved."` +
      ` loading="lazy" decoding="async">`
    : '';

  return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${escapeHtml(title)}</title>
<meta name="description" content="${escapeHtml(description)}">
<link rel="canonical" href="${SITE_URL}/">
<meta property="og:title" content="${escapeHtml(title)}">
<meta property="og:description" content="${escapeHtml(description)}">
<meta property="og:url" content="${SITE_URL}/">
<meta property="og:type" content="website">
<meta name="theme-color" content="#1a1a1a">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/style.css">
<link rel="stylesheet" href="/landing.css">
<script>${THEME_SCRIPT}</script>
</head>
<body class="is-landing">
<div class="atmosphere" aria-hidden="true"><div class="grid"></div><div class="noise"></div></div>
<a class="skip" href="#content">Skip to content</a>
<header class="topbar">
  <a class="brand" href="/"><img class="logo" src="/favicon.svg" width="24" height="24" alt=""> Sheet I18n <span class="tag">Laravel</span></a>
  <div class="actions">
    <a href="/docs/">Docs</a>
    <a href="${PACKAGIST_URL}" target="_blank" rel="noopener">Packagist</a>
    <a class="icon" href="${REPO_URL}" target="_blank" rel="noopener" aria-label="GitHub repository">${ICON_GITHUB}</a>
    <button class="theme" type="button" aria-label="Toggle dark mode">◐</button>
  </div>
</header>
<main id="content" class="landing">
${hero()}
${row('demo', demo(), ' full')}
<div class="shell2">
  ${indexNav()}
  <div class="stack">
    ${row('how', steps())}
    ${row('details', features())}
    ${row('dashboard', dashboard(shotImg))}
    ${row('start', start(cliCode, envCode, REPO_URL, PACKAGIST_URL))}
  </div>
</div>
<footer class="footer">
  <span>Built with purpose</span>
  <span><a href="https://momik.dev" target="_blank" rel="noopener">Momik Shrestha</a></span>
</footer>
</main>
<script src="/app.js" defer></script>
<script type="module" src="/landing.js"></script>
</body>
</html>
`;
}

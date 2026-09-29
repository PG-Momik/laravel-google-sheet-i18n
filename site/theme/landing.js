/*
  The landing page demo: picking a locale fills the sheet's target column row by row, and the PHP
  language files build up beside it: the same round trip the package makes, minus the network.

  The markup is rendered by build.mjs and is already correct without this file; everything here is
  animation on top of it.
*/
import { FILES, LOCALES, PLACEHOLDER_RE, ROWS, SOURCE, TOTAL_FILES, TOTAL_STRINGS } from './demo-data.js';

const demo = document.querySelector('.demo-frame');
if (demo) setup(demo);

function setup(root) {
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const formula = root.querySelector('.formula');
  const status = root.querySelector('.demo-status');
  const statusText = status.querySelector('.text');
  // The locale name lives in the sheet's own header row (row 1); the data cells are rows 2 and down.
  const targetHead = root.querySelector('.sheet td.locale');
  const cells = [...root.querySelectorAll('.sheet tbody td.out')];
  const chips = [...root.querySelectorAll('.chip')];
  const tabs = [...root.querySelectorAll('.out-tabs button')];
  const code = root.querySelector('.out-code');

  // One rendered PHP file per tab; `lines` is filled as the matching rows land.
  let activeFile = FILES[0];
  let locale = root.dataset.locale || LOCALES[0].code;
  let run = 0; // bumped on every new run so a run in flight stops touching the DOM

  const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  const wait = (ms) => new Promise((r) => setTimeout(r, ms));

  /** ":seconds" is shown as a chip, in the source and the translation alike. */
  const withPlaceholders = (text) =>
    esc(text).replace(PLACEHOLDER_RE, (m) => `<span class="ph">${m}</span>`);

  const phpLine = (row) =>
    `    <span class="tok-punc">'</span><span class="tok-key">${esc(row.key)}</span>` +
    `<span class="tok-punc">' => '</span><span class="tok-str">${withPlaceholders(
      row.t[locale].replace(/\\/g, '\\\\').replace(/'/g, "\\'"),
    )}</span><span class="tok-punc">',</span>`;

  /** Redraws the code pane for the active tab, showing only the rows filled so far. */
  function renderCode(filledKeys, freshKey) {
    const rows = ROWS.filter((r) => r.file === activeFile && filledKeys.has(r.key));
    const body = rows
      .map((r) => `<span class="ln${r.key === freshKey ? ' fresh' : ''}">${phpLine(r)}</span>`)
      .join('');
    code.innerHTML =
      `<span class="ln"><span class="tok-tag">&lt;?php</span></span><span class="ln"> </span>` +
      `<span class="ln"><span class="tok-punc">return [</span></span>` +
      body +
      `<span class="ln"><span class="tok-punc">];</span></span>`;
  }

  function setTab(file) {
    activeFile = file;
    tabs.forEach((t) => t.setAttribute('aria-selected', String(t.dataset.file === file)));
    renderCode(filled);
  }

  let filled = new Set();

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => setTab(tab.dataset.file));
    tab.textContent = tab.textContent.replace(/lang\/\w+\//, `lang/${locale}/`);
  });

  chips.forEach((chip) => {
    chip.addEventListener('click', () => {
      if (chip.dataset.locale === locale && status.classList.contains('done')) return;
      generate(chip.dataset.locale);
    });
  });

  /** Retitles the target column, the tabs and the chips for the locale being generated. */
  function relabel(next) {
    locale = next;
    root.dataset.locale = next;
    targetHead.textContent = next.toUpperCase();
    chips.forEach((c) => c.setAttribute('aria-pressed', String(c.dataset.locale === next)));
    tabs.forEach((t) => (t.textContent = `lang/${next}/${t.dataset.file}`));
  }

  async function generate(next) {
    const me = ++run;
    relabel(next);
    filled = new Set();
    cells.forEach((cell) => {
      cell.innerHTML = '';
      cell.classList.remove('done', 'pending', 'active');
    });
    renderCode(filled);

    const name = LOCALES.find((l) => l.code === next)?.name ?? next;
    status.className = 'demo-status busy';
    statusText.textContent = `Pushing ${TOTAL_STRINGS} strings to sheet ${sheetName()}…`;
    if (!reduced) await wait(650);
    if (me !== run) return;

    for (const [i, cell] of cells.entries()) {
      const row = ROWS[i];
      // Column D holds the source text; +2 skips the header row and 1-indexes it, exactly as
      // TranslateCommand builds the formula.
      formula.classList.remove('idle');
      formula.textContent = `=GOOGLETRANSLATE(D${i + 2}, "${SOURCE}", "${next}")`;
      cells.forEach((c) => c.classList.remove('active'));
      cell.classList.add('pending', 'active');
      statusText.textContent = `Waiting for Google Sheets: ${row.file}, ${row.key}`;
      if (!reduced) await wait(520);
      if (me !== run) return;

      cell.classList.remove('pending');
      cell.classList.add('done');
      cell.innerHTML = withPlaceholders(row.t[next]);
      filled.add(row.key);
      // Follow the rows into whichever file they belong to.
      if (row.file !== activeFile) setTab(row.file);
      else renderCode(filled, row.key);
      if (!reduced) await wait(160);
      if (me !== run) return;
    }

    cells.forEach((c) => c.classList.remove('active'));
    formula.classList.add('idle');
    formula.textContent = `${ROWS.length} of ${TOTAL_STRINGS} rows shown`;
    status.className = 'demo-status done';
    statusText.textContent = `Pulled back into lang/${next}/, ${TOTAL_STRINGS} ${name} strings across ${TOTAL_FILES} files`;
  }

  // The sheet is named for the day it was created (TranslateCommand::generateSheetName).
  function sheetName() {
    return new Date().toISOString().slice(0, 10);
  }

  // Runs once, when the demo first scrolls into view, so it isn't already over by the time it's seen.
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(
      (entries) => {
        if (!entries.some((e) => e.isIntersecting)) return;
        io.disconnect();
        generate(locale);
      },
      { threshold: 0.35 },
    );
    io.observe(root);
  } else {
    generate(locale);
  }
}

/*
  Sections settle in as they scroll into view. Three rules, because a decorative animation must
  never be able to hide the page:
    1. hiding is opt-in via .js-reveal, so no JavaScript means nothing is ever hidden;
    2. anything already on screen is revealed synchronously, without waiting for an observer;
    3. a timer reveals whatever is left, so a missed observer callback cannot strand a section.
*/
const revealing = [...document.querySelectorAll('.reveal')];
if (revealing.length && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
  document.documentElement.classList.add('js-reveal');
  const show = (el) => el.classList.add('seen');
  const onScreen = (el) => {
    const r = el.getBoundingClientRect();
    return r.top < innerHeight && r.bottom > 0;
  };

  revealing.filter(onScreen).forEach(show);

  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (!entry.isIntersecting) continue;
          show(entry.target);
          io.unobserve(entry.target);
        }
      },
      { threshold: 0 },
    );
    revealing.filter((el) => !el.classList.contains('seen')).forEach((el) => io.observe(el));
  }

  // Last resort. If the observer never reports, the page still ends up fully visible.
  setTimeout(() => revealing.forEach(show), 2500);
}

// The index tracks which section you are in. IntersectionObserver, never a scroll listener.
const indexLinks = [...document.querySelectorAll('.index a')];
if (indexLinks.length && 'IntersectionObserver' in window) {
  const byId = new Map(indexLinks.map((a) => [a.hash.slice(1), a]));
  const spy = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        indexLinks.forEach((a) => a.classList.remove('active'));
        byId.get(entry.target.id)?.classList.add('active');
      }
    },
    { rootMargin: '-25% 0px -60% 0px' },
  );
  byId.forEach((_, id) => {
    const section = document.getElementById(id);
    if (section) spy.observe(section);
  });
}

// The hero's install command copies itself.
document.querySelector('.install')?.addEventListener('click', async function () {
  const hint = this.querySelector('.copy-hint');
  try {
    await navigator.clipboard.writeText(this.dataset.copy);
    this.classList.add('copied');
    hint.textContent = 'Copied';
  } catch {
    hint.textContent = 'Press ⌘C';
  }
  setTimeout(() => {
    this.classList.remove('copied');
    hint.textContent = 'Copy';
  }, 1600);
});

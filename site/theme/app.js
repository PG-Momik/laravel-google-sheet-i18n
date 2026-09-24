// Theme toggle, mobile menu, copy buttons, and "on this page" highlighting.
(() => {
  const root = document.documentElement;

  document.querySelector('.theme')?.addEventListener('click', () => {
    const dark = root.dataset.theme
      ? root.dataset.theme === 'dark'
      : matchMedia('(prefers-color-scheme: dark)').matches;
    root.dataset.theme = dark ? 'light' : 'dark';
    try {
      localStorage.setItem('theme', root.dataset.theme);
    } catch {}
  });

  const menu = document.querySelector('.menu');
  menu?.addEventListener('click', () => {
    const open = document.body.classList.toggle('nav-open');
    menu.setAttribute('aria-expanded', String(open));
  });

  document.querySelectorAll('.copy').forEach((button) => {
    button.addEventListener('click', async () => {
      const code = button.parentElement.querySelector('pre')?.innerText ?? '';
      try {
        await navigator.clipboard.writeText(code.replace(/\n$/, ''));
        button.textContent = 'Copied';
      } catch {
        button.textContent = 'Press ⌘C';
      }
      setTimeout(() => (button.textContent = 'Copy'), 1500);
    });
  });

  const links = [...document.querySelectorAll('.toc a')];
  if (links.length && 'IntersectionObserver' in window) {
    const byId = new Map(links.map((a) => [a.hash.slice(1), a]));
    const observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (!entry.isIntersecting) continue;
          links.forEach((a) => a.classList.remove('active'));
          byId.get(entry.target.id)?.classList.add('active');
        }
      },
      { rootMargin: '-60px 0px -70% 0px' },
    );
    byId.forEach((_, id) => {
      const heading = document.getElementById(id);
      if (heading) observer.observe(heading);
    });
  }
})();

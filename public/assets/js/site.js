// Theme toggle
(function () {
  const btn = document.getElementById('themeToggle');
  const saved = localStorage.getItem('sc-theme');
  if (saved === 'dark') {
    document.body.classList.add('dark');
    if (btn) btn.textContent = '☀ Light';
  }
  if (btn) {
    btn.addEventListener('click', () => {
      const isDark = document.body.classList.toggle('dark');
      localStorage.setItem('sc-theme', isDark ? 'dark' : 'light');
      btn.textContent = isDark ? '☀ Light' : '🌙 Dark';
    });
  }
})();

// Generic carousel: pass a root element containing .carousel-track, .dots, prev/next buttons
function initCarousel(rootId) {
  const root = document.getElementById(rootId);
  if (!root) return;
  const track = root.querySelector('.carousel-track');
  const slides = Array.from(track.children);
  const dotsWrap = root.querySelector('.dots');
  const prevBtn = root.querySelector('.prev-slide');
  const nextBtn = root.querySelector('.next-slide');
  let index = 0;

  if (!slides.length) return;

  slides.forEach((_, i) => {
    const dot = document.createElement('button');
    dot.className = 'dot' + (i === 0 ? ' active' : '');
    dot.addEventListener('click', () => go(i));
    dotsWrap.appendChild(dot);
  });

  function go(i) {
    index = (i + slides.length) % slides.length;
    track.style.transform = `translateX(-${index * 100}%)`;
    dotsWrap.querySelectorAll('.dot').forEach((d, di) => d.classList.toggle('active', di === index));
  }

  if (prevBtn) prevBtn.addEventListener('click', () => go(index - 1));
  if (nextBtn) nextBtn.addEventListener('click', () => go(index + 1));
}

// Science block tabs (Physical Mechanism / Physiological Impact / Psychological Impact)
function initScienceTabs() {
  document.querySelectorAll('.science-block').forEach((block) => {
    const tabs = block.querySelectorAll('.sci-tab');
    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const key = tab.getAttribute('data-sci-tab');
        block.querySelectorAll('.sci-tab').forEach((t) => t.classList.toggle('active', t === tab));
        block.querySelectorAll('.sci-panel').forEach((p) => p.classList.toggle('active', p.getAttribute('data-sci-panel') === key));
      });
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initCarousel('toolsCarousel');
  initCarousel('eventsCarousel');
  initScienceTabs();
  initInfiniteArticles();
  initPhoneFields();
  initProfessionOther();
  initMobileNav();
});

function initInfiniteArticles() {
  const sentinel = document.getElementById('articlesSentinel');
  const grid = document.getElementById('articlesGrid');
  if (!sentinel || !grid) return;

  let page = parseInt(sentinel.dataset.page || '1', 10);
  let hasMore = sentinel.dataset.hasMore === '1';
  let loading = false;
  const q = sentinel.dataset.q || '';
  const topic = sentinel.dataset.topic || '';

  const observer = new IntersectionObserver((entries) => {
    if (!entries[0] || !entries[0].isIntersecting) return;
    loadNext();
  }, { rootMargin: '240px 0px' });

  observer.observe(sentinel);

  function loadNext() {
    if (loading || !hasMore) return;
    loading = true;
    const next = page + 1;
    const params = new URLSearchParams({ partial: '1', page: String(next) });
    if (q) params.set('q', q);
    if (topic) params.set('topic', topic);

    fetch('articles?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then((res) => res.json())
      .then((data) => {
        if (data.html) {
          const wrap = document.createElement('div');
          wrap.innerHTML = data.html;
          while (wrap.firstChild) grid.appendChild(wrap.firstChild);
        }
        page = data.page || next;
        hasMore = !!data.hasMore;
        sentinel.dataset.page = String(page);
        sentinel.dataset.hasMore = hasMore ? '1' : '0';
        if (!hasMore) {
          observer.disconnect();
          sentinel.classList.add('infinite-end');
          sentinel.innerHTML = '<p>You\'ve reached the end.</p>';
        }
      })
      .catch(() => {
        sentinel.innerHTML = '<p>Couldn\'t load more articles. Scroll to try again.</p>';
      })
      .finally(() => { loading = false; });
  }
}

function initPhoneFields() {
  document.querySelectorAll('.phone-row').forEach((row) => {
    const select = row.querySelector('select');
    const input = row.querySelector('input[type="tel"]');
    const hint = row.parentElement.querySelector('.phone-len-hint');
    if (!select || !input) return;

    function digitsOnly(value) {
      return String(value || '').replace(/\D+/g, '').replace(/^0+/, '');
    }

    function selectedCountry() {
      const opt = select.options[select.selectedIndex];
      return {
        name: opt.getAttribute('data-name') || 'This country',
        min: parseInt(opt.getAttribute('data-min') || '0', 10),
        max: parseInt(opt.getAttribute('data-max') || '15', 10),
      };
    }

    function updateHint() {
      const c = selectedCountry();
      input.maxLength = c.max;
      const digits = digitsOnly(input.value);
      const range = c.min === c.max ? c.min + ' digits' : c.min + '–' + c.max + ' digits';
      if (!digits) {
        if (hint) {
          hint.textContent = 'Optional. ' + c.name + ' mobile numbers are ' + range + ' (no country code, no leading 0).';
          hint.classList.remove('is-error', 'is-ok');
        }
        return true;
      }
      if (digits.length < c.min || digits.length > c.max) {
        if (hint) {
          hint.textContent = c.name + ' numbers must be ' + range + '. You entered ' + digits.length + '.';
          hint.classList.add('is-error');
          hint.classList.remove('is-ok');
        }
        return false;
      }
      if (hint) {
        hint.textContent = 'Looks good — ' + digits.length + ' digits.';
        hint.classList.add('is-ok');
        hint.classList.remove('is-error');
      }
      return true;
    }

    select.addEventListener('change', updateHint);
    input.addEventListener('input', () => {
      const cleaned = digitsOnly(input.value);
      if (input.value !== cleaned) input.value = cleaned;
      updateHint();
    });
    updateHint();

    const form = row.closest('form');
    if (form && !form.dataset.phoneBound) {
      form.dataset.phoneBound = '1';
      form.addEventListener('submit', (ev) => {
        if (!updateHint()) ev.preventDefault();
      });
    }
  });
}

function initProfessionOther() {
  document.querySelectorAll('#professionSelect').forEach((select) => {
    const other = select.form && select.form.querySelector('.js-profession-other');
    if (!other) return;
    const input = other.querySelector('input');
    function sync() {
      const isOther = select.value === 'other';
      other.hidden = !isOther;
      if (input) input.required = isOther;
      if (!isOther && input) input.value = '';
    }
    select.addEventListener('change', sync);
    sync();
  });
}

function initMobileNav() {
  const toggle = document.getElementById('navToggle');
  const nav = document.getElementById('mainNav');
  const backdrop = document.getElementById('navBackdrop');
  if (!toggle || !nav) return;

  function close() {
    nav.classList.remove('is-open');
    document.body.classList.remove('nav-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open menu');
    if (backdrop) backdrop.hidden = true;
  }

  function open() {
    nav.classList.add('is-open');
    document.body.classList.add('nav-open');
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'Close menu');
    if (backdrop) backdrop.hidden = false;
  }

  toggle.addEventListener('click', () => {
    nav.classList.contains('is-open') ? close() : open();
  });
  if (backdrop) backdrop.addEventListener('click', close);
  nav.querySelectorAll('a').forEach((a) => a.addEventListener('click', close));
  window.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
}

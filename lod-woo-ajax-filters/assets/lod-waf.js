(() => {
  'use strict';

  const qs = (s, c = document) => c.querySelector(s);
  const qsa = (s, c = document) => Array.from(c.querySelectorAll(s));

  function money(value) {
    const n = Number(value || 0);
    try {
      return new Intl.NumberFormat(document.documentElement.lang || 'bg-BG', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      }).format(n);
    } catch (e) {
      return n + ' €';
    }
  }

  function preparePrice(form) {
    const section = qs('.lod-waf-price', form);
    if (!section) return;

    const minRange = qs('[data-role="min-range"]', section);
    const maxRange = qs('[data-role="max-range"]', section);
    const minHidden = qs('[data-role="min-price"]', section);
    const maxHidden = qs('[data-role="max-price"]', section);
    const label = qs('[data-role="price-label"]', section);
    const defaultMin = Number(section.dataset.defaultMin || minRange.min || 0);
    const defaultMax = Number(section.dataset.defaultMax || maxRange.max || 0);

    const update = (changed) => {
      let min = Number(minRange.value);
      let max = Number(maxRange.value);
      if (min > max) {
        if (changed === 'min') max = min;
        else min = max;
        minRange.value = String(min);
        maxRange.value = String(max);
      }
      label.textContent = `${money(min)} — ${money(max)}`;

      // Critical fix: never send the untouched archive bounds.
      minHidden.value = min > defaultMin ? String(min) : '';
      maxHidden.value = max < defaultMax ? String(max) : '';
    };

    minRange.addEventListener('input', () => update('min'));
    maxRange.addEventListener('input', () => update('max'));
  }

  function buildUrl(form) {
    const url = new URL(window.location.href);
    const params = url.searchParams;

    // Remove only filters controlled by this form.
    qsa('input[name]', form).forEach((input) => {
      const name = input.name.replace(/\[\]$/, '');
      if (name.startsWith('filter_') || name === 'min_price' || name === 'max_price') {
        params.delete(name);
      }
    });
    params.delete('paged');
    params.delete('product-page');

    const grouped = new Map();
    const data = new FormData(form);
    for (const [rawName, rawValue] of data.entries()) {
      const name = rawName.replace(/\[\]$/, '');
      const value = String(rawValue).trim();
      if (!value) continue;
      if (name.startsWith('filter_')) {
        if (!grouped.has(name)) grouped.set(name, []);
        grouped.get(name).push(value);
      } else {
        params.set(name, value);
      }
    }

    grouped.forEach((values, name) => {
      params.set(name, [...new Set(values)].join(','));
      params.set('query_type_' + name.replace(/^filter_/, ''), 'or');
    });

    return url;
  }

  function productContainer(doc = document) {
    return qs('.woocommerce ul.products', doc) || qs('ul.products', doc);
  }

  async function load(url, push = true) {
    const current = productContainer();
    if (!current) {
      window.location.href = url.toString();
      return;
    }

    document.documentElement.classList.add('lod-waf-loading');
    current.setAttribute('aria-busy', 'true');

    try {
      const response = await fetch(url.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      });
      if (!response.ok) throw new Error('HTTP ' + response.status);

      const html = await response.text();
      const parsed = new DOMParser().parseFromString(html, 'text/html');
      const incoming = productContainer(parsed);

      if (incoming) {
        current.replaceWith(incoming);
      } else {
        const notice = qs('.woocommerce-info, .woocommerce-no-products-found', parsed);
        current.outerHTML = notice ? notice.outerHTML : `<p class="woocommerce-info">${(window.LOD_WAF && LOD_WAF.noProducts) || 'Няма намерени продукти.'}</p>`;
      }

      const replacePairs = [
        ['.woocommerce-result-count', '.woocommerce-result-count'],
        ['.woocommerce-ordering', '.woocommerce-ordering'],
        ['.woocommerce-pagination', '.woocommerce-pagination'],
        ['.woocommerce-notices-wrapper', '.woocommerce-notices-wrapper']
      ];

      replacePairs.forEach(([oldSel, newSel]) => {
        const oldNode = qs(oldSel);
        const newNode = qs(newSel, parsed);
        if (oldNode && newNode) oldNode.replaceWith(newNode);
        else if (oldNode && !newNode && oldSel === '.woocommerce-pagination') oldNode.remove();
      });

      if (push) history.pushState({ lodWaf: true }, '', url.toString());
      document.dispatchEvent(new CustomEvent('lod:waf:updated', { detail: { url: url.toString() } }));
    } catch (error) {
      window.location.href = url.toString();
    } finally {
      document.documentElement.classList.remove('lod-waf-loading');
      const fresh = productContainer();
      if (fresh) fresh.removeAttribute('aria-busy');
    }
  }

  function bind(form) {
    if (form.dataset.bound === '1') return;
    form.dataset.bound = '1';
    preparePrice(form);

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      load(buildUrl(form));
    });

    qsa('input[type="checkbox"]', form).forEach((checkbox) => {
      checkbox.addEventListener('change', () => load(buildUrl(form)));
    });

    const clear = qs('.lod-waf-clear', form);
    if (clear) {
      clear.addEventListener('click', () => {
        qsa('input[type="checkbox"]', form).forEach((el) => { el.checked = false; });
        const price = qs('.lod-waf-price', form);
        if (price) {
          const min = qs('[data-role="min-range"]', price);
          const max = qs('[data-role="max-range"]', price);
          min.value = price.dataset.defaultMin;
          max.value = price.dataset.defaultMax;
          qs('[data-role="min-price"]', price).value = '';
          qs('[data-role="max-price"]', price).value = '';
          qs('[data-role="price-label"]', price).textContent = `${money(min.value)} — ${money(max.value)}`;
        }
        load(buildUrl(form));
      });
    }
  }

  function init() {
    qsa('.lod-waf').forEach(bind);
  }

  document.addEventListener('DOMContentLoaded', init);
  document.addEventListener('lod:waf:updated', init);
  window.addEventListener('popstate', () => load(new URL(window.location.href), false));

  document.addEventListener('click', (event) => {
    const link = event.target.closest('.woocommerce-pagination a');
    if (!link) return;
    event.preventDefault();
    load(new URL(link.href));
  });

  document.addEventListener('change', (event) => {
    const select = event.target.closest('.woocommerce-ordering select.orderby');
    if (!select) return;
    event.preventDefault();
    const url = new URL(window.location.href);
    url.searchParams.set('orderby', select.value);
    url.searchParams.delete('paged');
    load(url);
  });
})();

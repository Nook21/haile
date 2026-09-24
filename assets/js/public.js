(function () {
  'use strict';

  /* ── Theme ─────────────────────────────────────────────── */
  const html = document.documentElement;
  const saved = localStorage.getItem('theme') || 'light';
  html.setAttribute('data-theme', saved);

  document.querySelectorAll('#themeToggle, #themeToggleDesktop').forEach(btn => {
    btn.addEventListener('click', () => {
      const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', next);
      html.style.background = next === 'light' ? '#f8f6f1' : '#0d0d0a';
      localStorage.setItem('theme', next);
    });
  });

  /* ── Nav scroll ────────────────────────────────────────── */
  const nav = document.getElementById('siteNav');
  if (nav) {
    const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 1);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ── Mobile menu ───────────────────────────────────────── */
  const burger = document.getElementById('navBurger');
  const mobileMenu = document.getElementById('mobileMenu');
  if (burger && mobileMenu) {
    burger.addEventListener('click', () => {
      const open = mobileMenu.classList.toggle('open');
      burger.classList.toggle('open', open);
      burger.setAttribute('aria-expanded', open);
      document.body.style.overflow = open ? 'hidden' : '';
    });
    mobileMenu.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        mobileMenu.classList.remove('open');
        burger.classList.remove('open');
        burger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      });
    });
  }

  /* ── Fade-up observer ──────────────────────────────────── */
  const fadeEls = document.querySelectorAll('.fade-up');
  if (fadeEls.length) {
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    fadeEls.forEach(el => io.observe(el));
  }

  /* ── About section entrance ────────────────────────────── */
  const aboutImg     = document.getElementById('aboutImg');
  const aboutContent = document.getElementById('aboutContent');
  if (aboutImg || aboutContent) {
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('about-visible'); io.unobserve(e.target); } });
    }, { threshold: 0.15 });
    if (aboutImg)     io.observe(aboutImg);
    if (aboutContent) io.observe(aboutContent);
  }

  /* ── Contact section entrance ──────────────────────────── */
  const contactInfo    = document.getElementById('contactInfo');
  const contactFormWrap = document.getElementById('contactFormWrap');
  if (contactInfo || contactFormWrap) {
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('contact-visible'); io.unobserve(e.target); } });
    }, { threshold: 0.1 });
    if (contactInfo)     io.observe(contactInfo);
    if (contactFormWrap) io.observe(contactFormWrap);
  }

  /* ── Stat count-up ─────────────────────────────────────── */
  document.querySelectorAll('[data-count]').forEach(el => {
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (!e.isIntersecting) return;
        const target = +el.dataset.count;
        const suffix = el.dataset.suffix || '';
        const dur = 2200;
        const start = performance.now();
        const tick = now => {
          const p = Math.min((now - start) / dur, 1);
          const ease = 1 - Math.pow(1 - p, 4);
          el.textContent = Math.floor(ease * target) + suffix;
          if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
        io.unobserve(el);
      });
    }, { threshold: 0.5 });
    io.observe(el);
  });

  /* ── Property filter + View More/Less ─────────────────── */
  const filterBtns = document.querySelectorAll('.filter-btn');
  const propCards  = document.querySelectorAll('.prop-card');
  const viewMoreBtn  = document.getElementById('propViewMoreBtn');
  const viewMoreWrap = document.getElementById('propViewMoreWrap');

  const DESKTOP_INIT = 6, MOBILE_INIT = 3, STEP = 3;
  let isMobile = () => window.innerWidth <= 768;
  let visibleCount = isMobile() ? MOBILE_INIT : DESKTOP_INIT;
  let activeFilter = 'all';

  function getVisible() {
    return Array.from(propCards).filter(c =>
      activeFilter === 'all' || c.dataset.cat === activeFilter
    );
  }

  function applyView() {
    const visible = getVisible();
    const init = isMobile() ? MOBILE_INIT : DESKTOP_INIT;
    visible.forEach((card, i) => {
      card.style.display = i < visibleCount ? '' : 'none';
    });
    // hide cards not in filter
    Array.from(propCards).forEach(c => {
      if (activeFilter !== 'all' && c.dataset.cat !== activeFilter) c.style.display = 'none';
    });
    if (!viewMoreWrap) return;
    if (visible.length <= init) {
      viewMoreWrap.classList.add('hidden');
      return;
    }
    viewMoreWrap.classList.remove('hidden');
    if (visibleCount >= visible.length) {
      viewMoreBtn.innerHTML = 'View Less <i class="bi bi-arrow-up"></i>';
    } else {
      viewMoreBtn.innerHTML = 'View More <i class="bi bi-arrow-down"></i>';
    }
  }

  if (viewMoreBtn) {
    viewMoreBtn.addEventListener('click', () => {
      const visible = getVisible();
      const init = isMobile() ? MOBILE_INIT : DESKTOP_INIT;
      if (visibleCount >= visible.length) {
        visibleCount = init;
      } else {
        visibleCount = Math.min(visibleCount + STEP, visible.length);
      }
      applyView();
    });
  }

  if (filterBtns.length && propCards.length) {
    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeFilter = btn.dataset.cat;
        visibleCount = isMobile() ? MOBILE_INIT : DESKTOP_INIT;
        applyView();
        getVisible().forEach(card => setTimeout(() => card.classList.add('visible'), 50));
      });
    });
  }

  window.addEventListener('resize', () => {
    visibleCount = isMobile() ? MOBILE_INIT : DESKTOP_INIT;
    applyView();
  });

  applyView();

  /* ── Category tile filter (scrolls to properties) ─────── */
  window.filterByCategory = function(slug) {
    const propertiesSection = document.getElementById('properties');
    if (propertiesSection) propertiesSection.scrollIntoView({ behavior: 'smooth' });
    setTimeout(() => {
      const btn = document.querySelector('.filter-btn[data-cat="' + slug + '"]');
      if (btn) btn.click();
    }, 600);
  };

  /* ── Contact form AJAX ─────────────────────────────────── */
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const btn = contactForm.querySelector('[type="submit"]');
      const existingErr = contactForm.querySelector('.form-errors');
      if (existingErr) existingErr.remove();
      if (btn) { btn.disabled = true; btn.querySelector('span').textContent = 'Sending…'; }
      try {
        const res  = await fetch(contactForm.action, { method: 'POST', body: new FormData(contactForm) });
        const text = await res.text();
        const doc  = new DOMParser().parseFromString(text, 'text/html');
        const success = doc.querySelector('.form-success');
        const errs = Array.from(doc.querySelectorAll('.form-errors p')).map(p => p.textContent);
        if (success) {
          contactForm.reset();
          showToast('Enquiry sent — we\'ll be in touch shortly.');
        } else if (errs.length) {
          const box = document.createElement('div');
          box.className = 'form-errors';
          box.innerHTML = errs.map(m => '<p>' + m + '</p>').join('');
          contactForm.prepend(box);
        }
      } catch (_) { showToast('Something went wrong. Please try again.'); }
      if (btn) { btn.disabled = false; btn.querySelector('span').textContent = 'Send Enquiry'; }
    });
  }

  function showToast(msg) {
    const t = document.createElement('div');
    t.className = 'cp-toast';
    t.innerHTML = '<i class="bi bi-check-circle"></i> ' + msg;
    document.body.appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('cp-toast--in')));
    setTimeout(() => {
      t.classList.remove('cp-toast--in');
      t.addEventListener('transitionend', () => t.remove(), { once: true });
    }, 4500);
  }

  /* ── Property gallery lightbox ─────────────────────────── */
  (function () {
    const media = window.PROP_MEDIA;
    if (!media || !media.length) return;
    const lb     = document.getElementById('propLightbox');
    const lbImg  = document.getElementById('propLbImg');
    const lbVid  = document.getElementById('propLbVideo');
    const lbClose = document.getElementById('propLbClose');
    const lbPrev  = document.getElementById('propLbPrev');
    const lbNext  = document.getElementById('propLbNext');
    if (!lb) return;
    let cur = 0;

    function open(idx) {
      cur = idx;
      const item = media[idx];
      if (item.type === 'video') {
        lbImg.style.display = 'none';
        lbVid.style.display = 'block';
        lbVid.src = item.src; lbVid.play();
      } else {
        if (lbVid) { lbVid.pause(); lbVid.src = ''; lbVid.style.display = 'none'; }
        lbImg.style.display = 'block'; lbImg.style.opacity = '0';
        lbImg.src = item.src; lbImg.alt = item.alt || '';
        lbImg.onload = () => { lbImg.style.opacity = '1'; };
      }
      lb.classList.add('open');
      document.body.style.overflow = 'hidden';
      const multi = media.length > 1;
      lbPrev.style.display = multi ? '' : 'none';
      lbNext.style.display = multi ? '' : 'none';
    }
    function close() {
      lb.classList.remove('open');
      document.body.style.overflow = '';
      if (lbVid) { lbVid.pause(); lbVid.src = ''; }
    }
    function prev() { open((cur - 1 + media.length) % media.length); }
    function next() { open((cur + 1) % media.length); }

    const imgCount = media.filter(m => m.type === 'image').length;
    document.querySelectorAll('.gallery-zoom:not(.gallery-zoom--video)').forEach((btn, i) => {
      btn.addEventListener('click', e => { e.stopPropagation(); open(i); });
    });
    document.querySelectorAll('.gallery-zoom--video').forEach((btn, i) => {
      btn.addEventListener('click', e => { e.stopPropagation(); open(imgCount + i); });
    });
    document.querySelectorAll('.gallery-frame:not(.gallery-frame--video)').forEach((f, i) => {
      f.addEventListener('click', () => open(i));
    });
    document.querySelectorAll('.gallery-frame--video').forEach(f => {
      const vid = f.querySelector('video');
      const poster = f.querySelector('.gallery-video-poster');
      if (!vid) return;
      f.addEventListener('mouseenter', () => { vid.play(); if (poster) poster.style.opacity = '0'; });
      f.addEventListener('mouseleave', () => { vid.pause(); vid.currentTime = 0; if (poster) poster.style.opacity = '1'; });
    });

    lbClose.addEventListener('click', close);
    lbPrev.addEventListener('click', prev);
    lbNext.addEventListener('click', next);
    lb.addEventListener('click', e => { if (e.target === lb) close(); });
    document.addEventListener('keydown', e => {
      if (!lb.classList.contains('open')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') prev();
      if (e.key === 'ArrowRight') next();
    });
    let tx = 0;
    lb.addEventListener('touchstart', e => { tx = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', e => {
      const dx = e.changedTouches[0].clientX - tx;
      if (Math.abs(dx) > 45) dx < 0 ? next() : prev();
    }, { passive: true });
  })();

  /* ── Testimonials drag-to-scroll ──────────────────────── */
  (function () {
    const wrap  = document.querySelector('.testimonials-track-wrap');
    const track = document.getElementById('testimonialsTrack');
    if (!wrap || !track) return;
    let isDragging = false, startX = 0, scrollLeft = 0;

    wrap.addEventListener('mousedown', e => {
      isDragging = true;
      startX = e.pageX - wrap.offsetLeft;
      scrollLeft = wrap.scrollLeft;
      track.style.animationPlayState = 'paused';
    });
    wrap.addEventListener('mouseleave', () => { isDragging = false; track.style.animationPlayState = ''; });
    wrap.addEventListener('mouseup',    () => { isDragging = false; track.style.animationPlayState = ''; });
    wrap.addEventListener('mousemove',  e => {
      if (!isDragging) return;
      e.preventDefault();
      const x    = e.pageX - wrap.offsetLeft;
      const walk = (x - startX) * 1.5;
      wrap.scrollLeft = scrollLeft - walk;
    });
  })();

  /* ── Hero title typing animation ─────────────────────── */
  (function () {
    const el     = document.getElementById('heroTyped');
    const cursor = document.getElementById('heroCursor');
    if (!el || !cursor) return;
    const text  = el.closest('.hero-title').dataset.text ||
                  document.title.split('—')[1]?.trim() ||
                  'HAILE';
    // read the site title from a data attribute we'll set, fallback to HAILE
    const word  = (window.HERO_TITLE || 'HAILE').toUpperCase();
    let i = 0;
    // start after hero rise animation (0.35s delay + 0.9s duration)
    function typeLoop() {
      i = 0;
      el.textContent = '';
      cursor.classList.remove('done');
      // type forward
      const typing = setInterval(() => {
        el.textContent = word.slice(0, ++i);
        if (i >= word.length) {
          clearInterval(typing);
          // pause, then erase
          setTimeout(() => {
            const erasing = setInterval(() => {
              el.textContent = word.slice(0, --i);
              if (i <= 0) {
                clearInterval(erasing);
                // pause, then loop again
                setTimeout(typeLoop, 600);
              }
            }, 60);
          }, 1800);
        }
      }, 110);
    }
    setTimeout(typeLoop, 1300);
  })();

  /* ── GSAP hero entrance ────────────────────────────────── */
  if (typeof gsap !== 'undefined') {
    const heroEyebrow = document.querySelector('.hero-eyebrow');
    const heroTitle   = document.querySelector('.hero-title');
    const heroSub     = document.querySelector('.hero-sub');
    const heroActions = document.querySelector('.hero-actions');
    const heroStats   = document.querySelector('.hero-stats');
    if (heroTitle) {
      gsap.set([heroEyebrow, heroTitle, heroSub, heroActions, heroStats], { opacity: 0, y: 40 });
      gsap.to(heroEyebrow, { opacity: 1, y: 0, duration: 1,   ease: 'expo.out', delay: 0.2 });
      gsap.to(heroTitle,   { opacity: 1, y: 0, duration: 1.2, ease: 'expo.out', delay: 0.4 });
      gsap.to(heroSub,     { opacity: 1, y: 0, duration: 1,   ease: 'expo.out', delay: 0.65 });
      gsap.to(heroActions, { opacity: 1, y: 0, duration: 0.9, ease: 'expo.out', delay: 0.85 });
      gsap.to(heroStats,   { opacity: 1, y: 0, duration: 0.9, ease: 'expo.out', delay: 1.0 });
    }
  }

  /* ── Parallax scroll on image sections ─────────────────── */
  (function () {
    const parallaxItems = [
      { sel: '.hero-bg-img',       speed: 0.35 },
      { sel: '.investment-bg-img', speed: 0.3  },
      { sel: '.contact-cta-bg-img',speed: 0.3  },
      { sel: '.insight-hero-bg img', speed: 0.3 },
      { sel: '.property-hero-bg img', speed: 0.3 },

      { sel: '.cat-tile-bg',        speed: 0.1  },
    ];

    const items = [];
    parallaxItems.forEach(({ sel, speed }) => {
      document.querySelectorAll(sel).forEach(el => {
        el.style.willChange = 'transform';
        items.push({ el, speed });
      });
    });

    if (!items.length) return;

    let ticking = false;
    function applyParallax() {
      const sy = window.scrollY;
      items.forEach(({ el, speed }) => {
        const rect = el.closest('section, .hero, .insight-hero, .property-hero') ||
                     el.parentElement;
        if (!rect) return;
        const r = rect.getBoundingClientRect();
        if (r.bottom < 0 || r.top > window.innerHeight) return;
        const offset = (r.top + r.height / 2 - window.innerHeight / 2) * speed;
        el.style.transform = 'translateY(' + offset.toFixed(2) + 'px) scale(1.08)';
      });
      ticking = false;
    }

    window.addEventListener('scroll', () => {
      if (!ticking) { requestAnimationFrame(applyParallax); ticking = true; }
    }, { passive: true });
    applyParallax();
  })();

  /* -- Scroll-triggered left-slide animations -- */
  (function () {
    if (typeof IntersectionObserver === 'undefined') return;
    var EASE = 'cubic-bezier(0.16,1,0.3,1)';
    function slideFrom(el, delay, x) {
      el.style.opacity = '0';
      el.style.transform = 'translateX(' + x + 'px)';
      el.style.willChange = 'opacity,transform';
      var io = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
          if (!e.isIntersecting) return;
          setTimeout(function() {
            el.style.transition = 'opacity 0.85s ' + EASE + ', transform 0.85s ' + EASE;
            el.style.opacity = '1';
            el.style.transform = 'translateX(0)';
          }, delay * 1000);
          io.unobserve(el);
        });
      }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });
      io.observe(el);
    }
    document.querySelectorAll('.section-eyebrow,.section-title,.section-sub,.insights-eyebrow,.insights-heading,.why-eyebrow,.why-heading,.journey-eyebrow,.journey-heading,.contact-cta-heading,.contact-cta-sub,.contact-lv-eyebrow,.contact-lv-name,.contact-lv-role,.testimonials-eyebrow,.testimonials-rule,.insights-rule,.filter-bar').forEach(function(el,i) {
      slideFrom(el, (i % 3) * 0.07, -50);
    });
    document.querySelectorAll('.service-card').forEach(function(el,i){ slideFrom(el, i*0.1, -60); });
    document.querySelectorAll('.why-item').forEach(function(el,i){ slideFrom(el, i*0.1, -60); });
    document.querySelectorAll('.journey-step').forEach(function(el,i){ slideFrom(el, i*0.1, -60); });
    document.querySelectorAll('.prop-card').forEach(function(el,i){ slideFrom(el, Math.min(i*0.08,0.4), -60); });
    document.querySelectorAll('.cat-tile').forEach(function(el,i){ slideFrom(el, Math.min(i*0.08,0.32), -60); });
    document.querySelectorAll('.insights-article').forEach(function(el,i){ slideFrom(el, i*0.12, -60); });
    document.querySelectorAll('.footer-top > *').forEach(function(el,i){ slideFrom(el, i*0.1, -50); });
    var aImg = document.querySelector('.about-img-wrap');
    var aCnt = document.querySelector('.about-content');
    if (aImg) slideFrom(aImg, 0, -60);
    if (aCnt) slideFrom(aCnt, 0.18, -60);
    var cInfo = document.querySelector('.contact-lv-info');
    var cForm = document.querySelector('.contact-lv-form-wrap');
    if (cInfo) slideFrom(cInfo, 0, -60);
    if (cForm) slideFrom(cForm, 0.15, -60);
    document.querySelectorAll('.investment-content,.contact-cta-inner').forEach(function(el){ slideFrom(el, 0, -60); });
  })();

})();

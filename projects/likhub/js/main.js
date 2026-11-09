(function () {
  'use strict';



  const hamburger = document.getElementById('hamburger');
  const mobileMenu = document.getElementById('mobileMenu');
  const sidebar = document.querySelector('.sidebar');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  // If the page has its own dedicated hamburger (e.g. dashboard), skip main.js sidebar logic entirely
  const hasDedicatedHamburger = !!document.getElementById('dashHamburger');

  if (hamburger && !hasDedicatedHamburger) {
    if (sidebar && sidebarBackdrop) {
      function openSidebar() {
        sidebar.classList.add('open');
        sidebarBackdrop.classList.add('open');
        hamburger.classList.add('open');
        hamburger.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
      }
      function closeSidebar() {
        sidebar.classList.remove('open');
        sidebarBackdrop.classList.remove('open');
        hamburger.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      }
      hamburger.addEventListener('click', () => {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
      });
      sidebarBackdrop.addEventListener('click', closeSidebar);
      sidebar.querySelectorAll('a').forEach(a => a.addEventListener('click', closeSidebar));
    } else if (mobileMenu) {
      const closeMenuBtn = document.getElementById('mobileMenuClose');
      function closeMenu() {
        mobileMenu.classList.remove('open');
        hamburger.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
      }
      hamburger.addEventListener('click', () => {
        const open = mobileMenu.classList.toggle('open');
        hamburger.classList.toggle('open', open);
        hamburger.setAttribute('aria-expanded', open);
      });
      if (closeMenuBtn) closeMenuBtn.addEventListener('click', closeMenu);
      mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));
      document.addEventListener('click', (e) => {
        if (!hamburger.contains(e.target) && !mobileMenu.contains(e.target)) closeMenu();
      });
    }
  }

  
  const scrollBtn = document.getElementById('scrollTop');
  if (scrollBtn) {
    window.addEventListener('scroll', () => {
      scrollBtn.classList.toggle('visible', window.scrollY > 400);
    }, { passive: true });
    scrollBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  
  const fadeEls = document.querySelectorAll('.fade-up');
  if (fadeEls.length && 'IntersectionObserver' in window) {
    fadeEls.forEach(el => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(20px)';
      el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    });
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el = entry.target;
          const delay = parseFloat(getComputedStyle(el).animationDelay) || 0;
          setTimeout(() => {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
          }, delay * 1000);
          obs.unobserve(el);
        }
      });
    }, { threshold: 0.12 });
    fadeEls.forEach(el => obs.observe(el));
  }

  
  document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.faq-item');
      const isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item.open').forEach(i => i.classList.remove('open'));
      if (!isOpen) item.classList.add('open');
    });
  });

  
  const flash = document.querySelector('.alert[role="alert"]');
  if (flash) {
    setTimeout(() => {
      flash.style.transition = 'opacity 0.4s ease';
      flash.style.opacity = '0';
      setTimeout(() => flash.remove(), 400);
    }, 5000);
  }

  
  const path = window.location.pathname.replace(/\/$/, '') || '/index.php';
  document.querySelectorAll('.navbar__links a, .mobile-menu a').forEach(a => {
    const href = (a.getAttribute('href') || '').split('?')[0];
    if (href && path.endsWith(href)) {
      a.classList.add('active');
    }
  });

  
  const commModal = document.getElementById('commissionModal');
  if (commModal) {
    const closeCommModal = () => {
      commModal.classList.remove('open');
      document.body.style.overflow = '';
    };

    document.querySelectorAll('.js-open-commission').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('modalArtistId').value = btn.dataset.id;
        document.getElementById('modalArtistName').textContent = btn.dataset.name;
        commModal.classList.add('open');
        document.body.style.overflow = 'hidden';
      });
    });

    const closeBtn = commModal.querySelector('.modal__close');
    if (closeBtn) closeBtn.addEventListener('click', closeCommModal);
    commModal.addEventListener('click', e => {
      if (e.target === commModal) closeCommModal();
    });

    if (commModal.dataset.hasErrors === 'true') {
      commModal.classList.add('open');
    }
  }

  
  document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      const csrf = btn.dataset.csrf;
      const endpoint = btn.dataset.endpoint || '/create.php';

      const body = new FormData();
      body.append('action', 'toggle_like');
      body.append('artwork_id', id);
      body.append('csrf', csrf);

      fetch(endpoint, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
          if (data.error) { window.location = '/login.php'; return; }

          const icon = btn.querySelector('i');
          const countEl = btn.querySelector('.like-count');
          let currentCount = parseInt(countEl.textContent, 10);

          if (data.liked) {
            icon.className = 'fa-solid fa-heart';
            btn.style.color = '#dc2626';
            if (btn.style.borderColor) btn.style.borderColor = '#dc2626';
            btn.dataset.liked = '1';
            countEl.textContent = currentCount + 1;
          } else {
            icon.className = 'fa-regular fa-heart';
            btn.style.color = 'var(--text-muted)';
            if (btn.style.borderColor) btn.style.borderColor = 'var(--grey-pale)';
            btn.dataset.liked = '0';
            countEl.textContent = currentCount - 1;
          }

          const sbCnt = document.getElementById('sidebarLikeCount');
          if (sbCnt) sbCnt.textContent = countEl.textContent;
        })
        .catch(() => { window.location = '/login.php'; });
    });
  });

  
  const stars = document.querySelectorAll('.star-btn');
  if (stars.length > 0) {
    let selectedRating = 0;
    const ratingInput = document.getElementById('ratingInput');

    stars.forEach((btn, i) => {
      btn.addEventListener('mouseenter', () => {
        stars.forEach((s, j) => s.style.color = j <= i ? 'var(--star-gold)' : 'var(--grey-pale)');
      });
      btn.addEventListener('mouseleave', () => {
        stars.forEach((s, j) => s.style.color = j < selectedRating ? 'var(--star-gold)' : 'var(--grey-pale)');
      });
      btn.addEventListener('click', () => {
        selectedRating = i + 1;
        if (ratingInput) ratingInput.value = selectedRating;
        stars.forEach((s, j) => s.style.color = j < selectedRating ? 'var(--star-gold)' : 'var(--grey-pale)');
      });
    });
  }

  
  const tutorialBody = document.getElementById('tutorialBody');
  const progressBar  = document.getElementById('progressBar');
  const progressLabel = document.getElementById('progressLabel');

  if (tutorialBody && progressBar) {
    const updateProgress = () => {
      const rect  = tutorialBody.getBoundingClientRect();
      const total = tutorialBody.offsetHeight;
      const start = window.scrollY + rect.top;
      const end   = start + total - window.innerHeight;
      const scroll = Math.max(0, window.scrollY - start);
      const pct = end > 0 ? Math.min(100, Math.round((scroll / end) * 100)) : 100;

      progressBar.style.width = pct + '%';
      if (progressLabel) progressLabel.textContent = pct + '%';
    };

    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
  }

  
  document.querySelectorAll('.artist-card__cover').forEach(cover => {
    const slides = cover.querySelectorAll('.slide');
    const dots   = cover.querySelectorAll('.slide-dot');
    if (slides.length <= 1) return;
    let current = 0;
    let timer;

    function goTo(n) {
      slides[current].classList.remove('slide--active');
      dots[current] && dots[current].classList.remove('slide-dot--active');
      current = (n + slides.length) % slides.length;
      slides[current].classList.add('slide--active');
      dots[current] && dots[current].classList.add('slide-dot--active');
    }

    function start() { timer = setInterval(() => goTo(current + 1), 2800); }
    function stop()  { clearInterval(timer); }

    start();
    cover.addEventListener('mouseenter', stop);
    cover.addEventListener('mouseleave', start);
  });

})();
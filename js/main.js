/* Lemuel D. Chua - portfolio scripts */
(function () {
    'use strict';

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

    /* ---------- NAV: mobile menu, height var, scroll progress ---------- */
    const nav = $('nav');
    const toggle = $('.nav-toggle');
    const links = $('.nav-links');

    function setNavHeight() {
        if (nav) document.documentElement.style.setProperty('--nav-h', nav.offsetHeight + 'px');
    }
    setNavHeight();
    window.addEventListener('resize', setNavHeight);

    if (toggle && links) {
        toggle.addEventListener('click', () => {
            const open = nav.classList.toggle('menu-open');
            toggle.setAttribute('aria-expanded', open);
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        });
        $$('a', links).forEach(a => a.addEventListener('click', () => {
            nav.classList.remove('menu-open');
            toggle.setAttribute('aria-expanded', 'false');
        }));
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && nav.classList.contains('menu-open')) toggle.click();
        });
    }

    const toTop = document.createElement('button');
    toTop.className = 'to-top';
    toTop.type = 'button';
    toTop.setAttribute('aria-label', 'Back to top');
    toTop.innerHTML = '&#9650;';
    toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }));
    document.body.appendChild(toTop);

    function onScroll() {
        const h = document.documentElement.scrollHeight - window.innerHeight;
        const p = h > 0 ? window.scrollY / h : 0;
        toTop.classList.toggle('show', window.scrollY > 600);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* ---------- COUNTERS ---------- */
    const counters = $$('[data-count]');
    if (counters.length) {
        const run = el => {
            const end = parseInt(el.dataset.count, 10);
            const pad = el.dataset.pad ? parseInt(el.dataset.pad, 10) : 0;
            const fmt = n => String(n).padStart(pad, '0') + (el.dataset.suffix || '');
            if (reduceMotion) { el.textContent = fmt(end); return; }
            const t0 = performance.now(), dur = 1100;
            const step = t => {
                const k = Math.min(1, (t - t0) / dur);
                el.textContent = fmt(Math.round(end * (1 - Math.pow(1 - k, 3))));
                if (k < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        };
        const co = new IntersectionObserver(es => es.forEach(e => {
            if (e.isIntersecting) { run(e.target); co.unobserve(e.target); }
        }), { threshold: 0.6 });
        counters.forEach(c => co.observe(c));
    }

    /* ---------- LIGHTBOX ---------- */
    const lb = document.createElement('div');
    lb.className = 'lb';
    lb.setAttribute('role', 'dialog');
    lb.setAttribute('aria-modal', 'true');
    lb.setAttribute('aria-label', 'Image viewer');
    lb.innerHTML = `
        <div class="lb-bar">
            <p class="lb-count"></p>
            <p class="lb-title"></p>
            <button class="lb-close" type="button" aria-label="Close">&#10005;</button>
        </div>
        <div class="lb-stage">
            <button class="lb-nav lb-prev" type="button" aria-label="Previous">&#9664;</button>
            <figure class="lb-figure"><img alt="" /></figure>
            <button class="lb-nav lb-next" type="button" aria-label="Next">&#9654;</button>
        </div>
        <p class="lb-caption"></p>`;
    document.body.appendChild(lb);

    const lbImg = $('img', lb), lbTitle = $('.lb-title', lb), lbCap = $('.lb-caption', lb), lbCount = $('.lb-count', lb);
    let lbItems = [], lbIndex = 0, lbReturn = null;

    function lbShow(i) {
        lbIndex = (i + lbItems.length) % lbItems.length;
        const it = lbItems[lbIndex];
        lb.classList.remove('lb-swap');
        void lb.offsetWidth;
        lb.classList.add('lb-swap');
        lbImg.src = it.src;
        lbImg.alt = it.title || '';
        lbTitle.textContent = it.title || '';
        lbCap.textContent = it.caption ? `[ ${it.caption} ]` : '';
        lbCount.textContent = `${String(lbIndex + 1).padStart(2, '0')} / ${String(lbItems.length).padStart(2, '0')}`;
        lb.classList.toggle('single', lbItems.length < 2);
        // preload neighbours
        [1, -1].forEach(d => { const n = lbItems[(lbIndex + d + lbItems.length) % lbItems.length]; if (n) new Image().src = n.src; });
    }
    function lbOpen(items, i) {
        lbItems = items; lbReturn = document.activeElement;
        lbShow(i);
        lb.classList.add('open');
        document.body.classList.add('no-scroll');
        $('.lb-close', lb).focus();
    }
    function lbClose() {
        lb.classList.remove('open');
        document.body.classList.remove('no-scroll');
        if (lbReturn) lbReturn.focus();
    }
    $('.lb-close', lb).addEventListener('click', lbClose);
    $('.lb-prev', lb).addEventListener('click', () => lbShow(lbIndex - 1));
    $('.lb-next', lb).addEventListener('click', () => lbShow(lbIndex + 1));
    lb.addEventListener('click', e => { if (e.target === lb || e.target.classList.contains('lb-stage')) lbClose(); });
    document.addEventListener('keydown', e => {
        if (!lb.classList.contains('open')) return;
        if (e.key === 'Escape') lbClose();
        else if (e.key === 'ArrowLeft') lbShow(lbIndex - 1);
        else if (e.key === 'ArrowRight') lbShow(lbIndex + 1);
        else if (e.key === 'Tab') { // keep focus inside
            const f = $$('button', lb).filter(b => b.offsetParent !== null);
            const first = f[0], last = f[f.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    });
    addSwipe(lb, () => lbShow(lbIndex + 1), () => lbShow(lbIndex - 1));

    // Any element with [data-gallery] becomes a group; items are [data-lb] inside it.
    function itemsOf(group) {
        return $$('[data-lb]', group).map(el => ({
            src: el.dataset.lb,
            title: el.dataset.title || '',
            caption: el.dataset.caption || ''
        }));
    }
    $$('[data-gallery]').forEach(group => {
        $$('[data-lb]', group).forEach((el, i) => {
            if (!el.matches('a, button')) {
                el.setAttribute('tabindex', '0');
                el.setAttribute('role', 'button');
                el.setAttribute('aria-label', 'Open ' + (el.dataset.title || 'image'));
            }
            const open = e => { e.preventDefault(); lbOpen(itemsOf(group), i); };
            el.addEventListener('click', open);
            el.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') open(e); });
        });
    });

    // "VIEW" buttons in card footers open that card's image
    $$('.card-link[data-open]').forEach(b => b.addEventListener('click', () => {
        const target = b.closest('.card')?.querySelector('[data-lb]');
        if (target) target.click();
    }));

    /* ---------- SWIPE helper ---------- */
    function addSwipe(el, onLeft, onRight) {
        let x0 = null, y0 = null;
        el.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; }, { passive: true });
        el.addEventListener('touchend', e => {
            if (x0 === null) return;
            const dx = e.changedTouches[0].clientX - x0, dy = e.changedTouches[0].clientY - y0;
            if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy)) (dx < 0 ? onLeft : onRight)();
            x0 = null;
        }, { passive: true });
    }

    /* ---------- SLIDESHOW ---------- */
    $$('.slideshow').forEach(ss => {
        const slides = $$('.ss-slide', ss);
        const thumbs = $$('.ss-thumb', ss);
        const count = $('.ss-count', ss);
        const bar = $('.ss-progress', ss);
        const playBtn = $('.ss-play', ss);
        const delay = parseInt(ss.dataset.autoplay || '0', 10);
        let i = 0, timer = null, playing = delay > 0 && !reduceMotion;

        function go(n, user) {
            slides[i].classList.remove('active');
            if (thumbs[i]) { thumbs[i].classList.remove('active'); thumbs[i].setAttribute('aria-current', 'false'); }
            i = (n + slides.length) % slides.length;
            slides[i].classList.add('active');
            if (thumbs[i]) {
                thumbs[i].classList.add('active');
                thumbs[i].setAttribute('aria-current', 'true');
                const strip = thumbs[i].parentElement;
                strip.scrollTo({ left: thumbs[i].offsetLeft - strip.clientWidth / 2 + thumbs[i].clientWidth / 2, behavior: reduceMotion ? 'auto' : 'smooth' });
            }
            if (count) count.textContent = `${String(i + 1).padStart(2, '0')} / ${String(slides.length).padStart(2, '0')}`;
            if (user) restart();
        }
        function restart() {
            clearTimeout(timer);
            if (bar) { bar.style.transition = 'none'; bar.style.transform = 'scaleX(0)'; }
            if (!playing) return;
            requestAnimationFrame(() => requestAnimationFrame(() => {
                if (bar) { bar.style.transition = `transform ${delay}ms linear`; bar.style.transform = 'scaleX(1)'; }
            }));
            timer = setTimeout(() => { go(i + 1); restart(); }, delay);
        }
        function setPlaying(p) {
            playing = p;
            if (playBtn) {
                playBtn.innerHTML = p ? '&#10074;&#10074;' : '&#9654;';
                playBtn.setAttribute('aria-label', p ? 'Pause slideshow' : 'Play slideshow');
            }
            restart();
        }

        $('.ss-prev', ss)?.addEventListener('click', () => go(i - 1, true));
        $('.ss-next', ss)?.addEventListener('click', () => go(i + 1, true));
        thumbs.forEach((t, n) => t.addEventListener('click', () => go(n, true)));
        playBtn?.addEventListener('click', () => setPlaying(!playing));
        ss.addEventListener('keydown', e => {
            if (e.target.closest('.ss-thumbs, .ss-stage, .ss-controls')) {
                if (e.key === 'ArrowLeft') go(i - 1, true);
                if (e.key === 'ArrowRight') go(i + 1, true);
            }
        });
        addSwipe($('.ss-stage', ss), () => go(i + 1, true), () => go(i - 1, true));
        // pause while hovering / off screen
        ss.addEventListener('mouseenter', () => { if (playing) { clearTimeout(timer); if (bar) { bar.style.transition = 'none'; bar.style.transform = 'scaleX(0)'; } } });
        ss.addEventListener('mouseleave', restart);
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(es => es.forEach(e => {
                if (e.isIntersecting) restart(); else clearTimeout(timer);
            }), { threshold: 0.3 }).observe(ss);
        }
        // the stage image opens the lightbox for the current slide
        $('.ss-stage', ss).addEventListener('click', e => {
            if (e.target.closest('button')) return;
            lbOpen(slides.map(s => ({ src: s.dataset.full, title: s.dataset.title, caption: s.dataset.caption })), i);
        });
        setPlaying(playing);
        go(0);
    });

    /* ---------- CAROUSEL (scroll-snap track with arrows) ---------- */
    $$('.carousel').forEach(c => {
        const track = $('.car-track', c);
        const prev = $('.car-prev', c), next = $('.car-next', c);
        const stepBy = () => track.clientWidth * 0.8;
        prev.addEventListener('click', () => track.scrollBy({ left: -stepBy(), behavior: reduceMotion ? 'auto' : 'smooth' }));
        next.addEventListener('click', () => track.scrollBy({ left: stepBy(), behavior: reduceMotion ? 'auto' : 'smooth' }));
        const update = () => {
            prev.disabled = track.scrollLeft < 16;
            next.disabled = track.scrollLeft + track.clientWidth > track.scrollWidth - 16;
        };
        track.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    });

    /* ---------- TABS ---------- */
    $$('[data-tabs]').forEach(t => {
        const btns = $$('[role="tab"]', t);
        const select = b => {
            btns.forEach(x => {
                const on = x === b;
                x.setAttribute('aria-selected', on);
                x.tabIndex = on ? 0 : -1;
                const panel = document.getElementById(x.getAttribute('aria-controls'));
                panel.hidden = !on;
                if (on && !reduceMotion) { panel.classList.remove('tab-in'); void panel.offsetWidth; panel.classList.add('tab-in'); }
            });
        };
        btns.forEach((b, n) => {
            b.addEventListener('click', () => select(b));
            b.addEventListener('keydown', e => {
                if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                    const nb = btns[(n + (e.key === 'ArrowRight' ? 1 : -1) + btns.length) % btns.length];
                    nb.focus(); select(nb);
                }
            });
        });
    });

    /* ---------- PORTFOLIO: jump bar + scrollspy ---------- */
    const jumpBtns = $$('.filter-btn[data-target]');
    if (jumpBtns.length) {
        const bar = $('.filter-bar');
        const offset = () => (nav ? nav.offsetHeight : 0) + (bar ? bar.offsetHeight : 0) + 12;
        const setActive = id => jumpBtns.forEach(b => {
            const on = b.dataset.target === id;
            b.classList.toggle('active', on);
            if (on && bar) bar.scrollTo({ left: b.offsetLeft - 20, behavior: reduceMotion ? 'auto' : 'smooth' });
        });
        jumpBtns.forEach(b => b.addEventListener('click', () => {
            const el = document.getElementById(b.dataset.target);
            if (!el) return;
            window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - offset(), behavior: reduceMotion ? 'auto' : 'smooth' });
            history.replaceState(null, '', '#' + b.dataset.target);
        }));
        const sections = jumpBtns.map(b => document.getElementById(b.dataset.target)).filter(Boolean);
        let current = null;
        window.addEventListener('scroll', () => {
            const y = offset() + 40;
            let id = sections[0] && sections[0].id;
            sections.forEach(s => { if (s.getBoundingClientRect().top <= y) id = s.id; });
            if (id !== current) { current = id; setActive(id); }
        }, { passive: true });
        // arriving with a hash (e.g. from the home page)
        if (location.hash) {
            const el = document.getElementById(location.hash.slice(1));
            const jump = () => window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - offset() });
            if (el) {
                setTimeout(jump, 60);
                // images above can change the height while loading, so jump again once everything is in
                if (document.readyState === 'complete') setTimeout(jump, 300);
                else window.addEventListener('load', () => setTimeout(jump, 50), { once: true });
            }
        }
    }

    /* ---------- CONTACT ---------- */
    const form = $('#contactForm');
    if (form) {
        const email = form.dataset.email;
        form.addEventListener('submit', e => {
            e.preventDefault();
            const f = new FormData(form);
            const subject = f.get('subject') || `Portfolio message from ${f.get('name')}`;
            const body = `${f.get('message')}\n\n- ${f.get('name')} (${f.get('email')})`;
            if (email) window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
            form.style.display = 'none';
            $('#successMsg').classList.add('visible');
        });
        $('#sendAnother')?.addEventListener('click', () => {
            form.reset();
            form.style.display = '';
            $('#successMsg').classList.remove('visible');
        });
    }
    // hide social links that haven't been filled in yet
    $$('.social-list a[href="#"]').forEach(a => a.closest('li').remove());
})();

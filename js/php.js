/* Live (AJAX) parts of the PHP pages: forms that send without reloading,
   the email check on sign up, and the inbox that updates by itself. */
(function () {
    'use strict';
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

    // POST a form in the background and get JSON back
    async function send(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' }
        });
        let data = {};
        try { data = await res.json(); } catch (_) { data = { ok: false, errors: ['Something went wrong. Try again.'] }; }
        if (data.redirect && (res.status === 401 || data.ok)) window.location.href = data.redirect;
        return data;
    }

    function showErrors(form, errors) {
        let box = form.previousElementSibling;
        if (!box || !box.classList.contains('alert')) {
            box = document.createElement('div');
            box.className = 'alert err';
            box.setAttribute('role', 'alert');
            form.parentNode.insertBefore(box, form);
        }
        box.className = 'alert err';
        box.innerHTML = '';
        (errors && errors.length ? errors : ['Something went wrong.']).forEach(t => {
            const p = document.createElement('p');
            p.textContent = t;
            box.appendChild(p);
        });
        box.style.animation = 'none'; void box.offsetWidth; box.style.animation = '';
    }

    /* ---------- Manage Portfolio: reorder / hide / delete without reloading ---------- */
    const mngList = $('#mngList');
    if (mngList) {
        const note = $('#mngNotice');
        const say = (text, bad) => {
            if (!text) return;
            note.textContent = text;
            note.className = 'alert ' + (bad ? 'err' : 'ok');
            note.hidden = false;
            clearTimeout(say.t);
            say.t = setTimeout(() => { note.hidden = true; }, 3000);
        };
        mngList.addEventListener('submit', async e => {
            const form = e.target.closest('form[data-ajax="mng"]');
            if (!form) return;
            e.preventDefault();
            const btn = e.submitter;
            if (!btn) return;
            if (btn.dataset.confirm && !window.confirm(btn.dataset.confirm)) return;
            const fd = new FormData(form);
            fd.set('action', btn.value);
            const row = form.closest('.mng-row');
            row.classList.add('busy');
            const d = await send(location.pathname + location.search, fd);
            if (!d.ok) { row.classList.remove('busy'); say((d.errors || ['Could not do that.'])[0], true); return; }
            mngList.innerHTML = d.html;
            say(d.notice);
            const moved = mngList.querySelector(`.mng-row[data-id="${fd.get('id')}"]`);
            if (moved) { moved.classList.add('flash'); }
        });
    }

    // preview the picked image before uploading
    const pick = $('#aimg');
    const prev = $('#aPreview');
    if (pick && prev) {
        pick.addEventListener('change', () => {
            const f = pick.files && pick.files[0];
            if (!f || !f.type.startsWith('image/')) return;
            prev.src = URL.createObjectURL(f);
            prev.hidden = false;
        });
    }

    function busy(btn, on, label) {
        if (!btn) return;
        if (on) { btn.dataset.label = btn.innerHTML; btn.innerHTML = label; btn.disabled = true; }
        else { btn.innerHTML = btn.dataset.label || btn.innerHTML; btn.disabled = false; }
    }

    /* ---------- login / sign up / contact: send without reloading ---------- */
    $$('form.php-form').forEach(form => {
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const btn = form.querySelector('[type="submit"]');
            busy(btn, true, '&#9654; ' + (form.dataset.busy || 'SENDING...'));
            const data = await send(form.getAttribute('action'), new FormData(form));
            busy(btn, false);
            if (data.redirect) return; // login / sign up worked, page is moving on
            if (data.ok && form.dataset.kind === 'contact') {
                const done = $('#contactDone');
                $('.contact-email', done).textContent = (data.email || '').toUpperCase();
                const prev = form.previousElementSibling;
                if (prev && prev.classList.contains('alert')) prev.remove();
                form.hidden = true;
                done.hidden = false;
                done.classList.add('visible');
                return;
            }
            showErrors(form, data.errors);
            const pw = form.querySelector('input[type="password"]');
            if (pw && form.dataset.kind === 'login') { pw.value = ''; pw.focus(); }
        });
    });

    const again = $('#contactAgain');
    if (again) again.addEventListener('click', () => {
        const form = $('form.php-form[data-kind="contact"]');
        const done = $('#contactDone');
        form.querySelector('[name="subject"]').value = '';
        form.querySelector('[name="message"]').value = '';
        done.hidden = true;
        done.classList.remove('visible');
        form.hidden = false;
        form.querySelector('[name="message"]').focus();
    });

    /* ---------- sign up: live "is this email taken?" check ---------- */
    const regEmail = $('form[data-kind="register"] #email');
    if (regEmail) {
        const hint = document.createElement('p');
        hint.className = 'field-hint';
        hint.setAttribute('aria-live', 'polite');
        regEmail.insertAdjacentElement('afterend', hint);
        let t = null, last = '';
        regEmail.addEventListener('input', () => {
            clearTimeout(t);
            const v = regEmail.value.trim();
            if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v)) { hint.textContent = ''; hint.className = 'field-hint'; return; }
            hint.textContent = 'CHECKING...';
            hint.className = 'field-hint';
            t = setTimeout(async () => {
                if (v === last) return;
                last = v;
                const res = await fetch('register.php?check_email=' + encodeURIComponent(v), { headers: { 'X-Requested-With': 'fetch' } });
                const d = await res.json();
                if (regEmail.value.trim() !== v) return;
                hint.textContent = d.available ? '✓ EMAIL IS FREE' : '✕ ALREADY HAS AN ACCOUNT, TRY LOGGING IN';
                hint.className = 'field-hint ' + (d.available ? 'good' : 'bad');
            }, 400);
        });
    }

    /* ---------- show / hide password + strength bar ---------- */
    $$('.pw-toggle').forEach(btn => btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.pw);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'HIDE' : 'SHOW';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    }));
    const meter = $('.pw-meter span');
    const pw = document.getElementById('password');
    if (meter && pw) {
        pw.addEventListener('input', () => {
            const v = pw.value;
            let score = 0;
            if (v.length >= 8) score++;
            if (v.length >= 12) score++;
            if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
            if (/\d/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            meter.style.width = (score / 5 * 100) + '%';
            meter.dataset.level = score <= 1 ? 'weak' : score <= 3 ? 'ok' : 'strong';
        });
    }

    /* ---------- unread badge in the menu (admins, every page) ---------- */
    const feedUrl = document.body.dataset.feed;
    function setBadge(n) {
        const link = $('.nav-links a[href$="admin/messages.php"]');
        if (!link) return;
        let badge = $('.nav-badge', link);
        if (n > 0) {
            if (!badge) { badge = document.createElement('span'); badge.className = 'nav-badge'; link.appendChild(document.createTextNode(' ')); link.appendChild(badge); }
            badge.textContent = n;
        } else if (badge) badge.remove();
    }

    /* ---------- inbox widget ---------- */
    const inbox = $('#inbox');
    if (inbox) {
        const list = $('#inboxList');
        const empty = $('#inboxEmpty');
        const notice = $('#inboxNotice');
        const readAll = $('#readAllForm');
        const filter = inbox.dataset.filter;
        let latest = parseInt(inbox.dataset.latest, 10) || 0;

        const setCounts = c => {
            $$('[data-stat]').forEach(el => { if (c[el.dataset.stat] !== undefined) el.textContent = c[el.dataset.stat]; });
            readAll.hidden = !c.unread;
            setBadge(c.unread);
            document.title = (c.unread ? `(${c.unread}) ` : '') + 'Inbox | Lemuel D. Chua';
        };
        const say = text => {
            if (!text) return;
            notice.textContent = text;
            notice.hidden = false;
            clearTimeout(say.t);
            say.t = setTimeout(() => { notice.hidden = true; }, 3500);
        };
        const checkEmpty = () => { empty.hidden = !!list.querySelector('.msg'); };
        const markRow = (row, read) => {
            row.classList.toggle('unread', !read);
            const b = row.querySelector('button[name="action"][value="read"], button[name="action"][value="unread"]');
            if (b) { b.value = read ? 'unread' : 'read'; b.textContent = read ? 'MARK UNREAD' : 'MARK READ'; }
            row.querySelector('.msg-dot').setAttribute('aria-label', read ? 'Read' : 'Unread');
            if (read && filter === 'unread') { /* keep it visible until reload so it doesn't jump away */ }
        };

        async function act(form, action) {
            const fd = new FormData(form);
            fd.set('action', action);
            const d = await send(form.getAttribute('action'), fd);
            if (!d.ok) { say((d.errors || ['Could not do that.'])[0]); return; }
            const row = d.id ? list.querySelector(`.msg[data-id="${d.id}"]`) : null;
            if (action === 'delete' && row) {
                row.classList.add('msg-out');
                setTimeout(() => { row.remove(); checkEmpty(); }, 250);
            } else if (action === 'read' && row) markRow(row, true);
            else if (action === 'unread' && row) markRow(row, false);
            else if (action === 'read_all') $$('.msg', list).forEach(r => markRow(r, true));
            setCounts(d.counts);
            say(d.notice);
        }

        // buttons inside the list and the "mark all read" form
        inbox.addEventListener('submit', e => {
            const form = e.target.closest('form[data-ajax="inbox"]');
            if (!form) return;
            e.preventDefault();
            const btn = e.submitter || form.querySelector('[type="submit"]');
            if (btn.dataset.confirm && !window.confirm(btn.dataset.confirm)) return;
            act(form, btn.value || form.querySelector('[name="action"]').value);
        });

        // opening an unread message marks it read
        list.addEventListener('toggle', e => {
            const row = e.target;
            if (row.classList && row.classList.contains('msg') && row.open && row.classList.contains('unread')) {
                act(row.querySelector('form[data-ajax="inbox"]'), 'read');
            }
        }, true);

        // check for new messages every 8 seconds
        async function poll() {
            if (document.hidden) return;
            try {
                const res = await fetch(`messages.php?feed=1&filter=${filter}&since=${latest}`, { headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' });
                if (res.status === 401 || res.status === 403) { clearInterval(timer); return; }
                const d = await res.json();
                if (!d.ok) return;
                d.new.forEach(m => {
                    latest = Math.max(latest, m.id);
                    if (list.querySelector(`.msg[data-id="${m.id}"]`)) return;
                    const tpl = document.createElement('template');
                    tpl.innerHTML = m.html.trim();
                    const row = tpl.content.firstElementChild;
                    row.classList.add('msg-new');
                    list.prepend(row);
                });
                if (d.new.length) say(d.new.length === 1 ? 'New message!' : `${d.new.length} new messages!`);
                setCounts(d.counts);
                checkEmpty();
            } catch (_) { /* offline for a moment, try again next time */ }
        }
        const timer = setInterval(poll, 8000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
    } else if (feedUrl) {
        // other pages: just keep the badge fresh
        const tick = async () => {
            if (document.hidden) return;
            try {
                const res = await fetch(feedUrl + '?feed=1&since=2147483647', { headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' });
                const d = await res.json();
                if (d.ok) setBadge(d.counts.unread);
            } catch (_) {}
        };
        setInterval(tick, 20000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) tick(); });
    }
})();

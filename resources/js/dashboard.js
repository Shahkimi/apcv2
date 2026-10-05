import { createDashboardCharts } from './charts';

const POLL_MS = 15000;
const COUNT_UP_MS = 800;

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function readAutoRefresh(key) {
    try {
        const stored = localStorage.getItem(key);
        return stored === null ? true : stored === '1';
    } catch (e) {
        return true;
    }
}

function writeAutoRefresh(key, value) {
    try {
        localStorage.setItem(key, value ? '1' : '0');
    } catch (e) {
        /* ignore */
    }
}

/** Tween a number element with ease-out, formatting like the server (thousands separators, optional %). */
function animateNumber(el, from, to, stat, duration = COUNT_UP_MS) {
    const decimals = stat.suffix ? 1 : 0;
    const fmt = new Intl.NumberFormat('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    const text = (v) => fmt.format(v) + (stat.suffix || '');

    cancelAnimationFrame(el._raf);

    if (reducedMotion() || from === to) {
        el.textContent = text(to);
        return;
    }

    const start = performance.now();
    const step = (now) => {
        const t = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - t, 3);
        el.textContent = text(from + (to - from) * eased);
        if (t < 1) {
            el._raf = requestAnimationFrame(step);
        }
    };
    el._raf = requestAnimationFrame(step);
}

function flash(key, diff, stat) {
    const tile = document.querySelector(`[data-stat-tile="${key}"]`);
    if (tile && !reducedMotion()) {
        tile.classList.remove('bento-flash');
        void tile.offsetWidth; // restart the animation
        tile.classList.add('bento-flash');
        clearTimeout(tile._flashTimer);
        tile._flashTimer = setTimeout(() => tile.classList.remove('bento-flash'), 1500);
    }

    const badge = document.querySelector(`[data-stat-delta="${key}"]`);
    if (badge && !stat.suffix && diff) {
        badge.textContent = `${diff > 0 ? '+' : '−'}${Math.abs(diff).toLocaleString('en-US')}`;
        badge.classList.remove('is-up', 'is-down');
        badge.classList.add('is-show', diff > 0 ? 'is-up' : 'is-down');
        clearTimeout(badge._timer);
        badge._timer = setTimeout(() => badge.classList.remove('is-show'), 2200);
    }
}

function setRatio(key, stat) {
    if (stat.ratio === null || stat.ratio === undefined) return;

    document.querySelectorAll(`[data-stat-bar="${key}"]`).forEach((bar) => {
        bar.style.width = `${stat.ratio}%`;
    });

    document.querySelectorAll(`[data-ring="${key}"]`).forEach((ring) => {
        const circ = Number(ring.dataset.circ);
        ring.style.strokeDashoffset = String(circ * (1 - stat.ratio / 100));
    });

    document.querySelectorAll(`[data-ring-label="${key}"]`).forEach((label) => {
        label.textContent = `${Number(stat.ratio).toFixed(1)}%`;
    });
}

function renderStats(stats, known) {
    Object.entries(stats || {}).forEach(([key, stat]) => {
        const prev = known[key];

        document.querySelectorAll(`[data-stat="${key}"]`).forEach((el) => {
            animateNumber(el, prev ? prev.raw : stat.raw, stat.raw, stat);
        });
        document.querySelectorAll(`[data-stat-hint="${key}"]`).forEach((el) => {
            el.textContent = stat.hint;
        });
        setRatio(key, stat);

        if (prev && prev.raw !== stat.raw) {
            flash(key, stat.raw - prev.raw, stat);
        }

        known[key] = stat;
    });
}

function renderStatus(status) {
    Object.entries(status || {}).forEach(([key, value]) => {
        document.querySelectorAll(`[data-status="${key}"]`).forEach((el) => {
            el.textContent = value;
        });
    });
}

function renderTables(tables, seen) {
    Object.entries(tables || {}).forEach(([key, rows]) => {
        const body = document.querySelector(`[data-table-body="${key}"]`);
        if (!body) return;

        const before = seen[key] || new Set();
        seen[key] = new Set(rows.map((cells) => cells.join('|')));
        body.replaceChildren();

        if (!rows.length) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = Number(body.dataset.cols || 1);
            td.className = 'px-4 py-6 text-center text-muted-foreground';
            td.textContent = body.dataset.empty || '';
            tr.appendChild(td);
            body.appendChild(tr);
            return;
        }

        rows.forEach((cells) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-muted/30';
            if (!before.has(cells.join('|'))) {
                tr.classList.add('bento-row-in');
            }
            cells.forEach((cell) => {
                const td = document.createElement('td');
                td.className = 'px-4 py-3 text-card-foreground';
                td.textContent = cell;
                tr.appendChild(td);
            });
            body.appendChild(tr);
        });
    });
}

export function initDashboard(config) {
    const storageKey = `dashboardAutoRefresh:${config.role}`;
    const dot = document.getElementById('dash-live-dot');
    const ping = document.getElementById('dash-live-ping');
    const updatedAt = document.getElementById('dash-updated-at');
    const toggle = document.getElementById('dash-auto-refresh');
    const reloadBtn = document.getElementById('dash-reload');
    const reloadIcon = document.getElementById('dash-reload-icon');

    const charts = createDashboardCharts(config.payload.charts, config.labels.noData);
    const known = { ...config.payload.stats };
    const seenRows = {};
    Object.entries(config.payload.tables || {}).forEach(([key, rows]) => {
        seenRows[key] = new Set(rows.map((cells) => cells.join('|')));
    });
    let inFlight = false;

    // Count up from zero on first paint.
    Object.entries(known).forEach(([key, stat]) => {
        document.querySelectorAll(`[data-stat="${key}"]`).forEach((el) => {
            animateNumber(el, 0, stat.raw, stat, COUNT_UP_MS + 400);
        });
    });

    function stamp(ok) {
        if (updatedAt && ok) {
            updatedAt.textContent = `${config.labels.updated} ${new Date().toLocaleTimeString('ms-MY', { hour12: false })}`;
        }
        [dot, ping].forEach((el) => {
            if (!el) return;
            el.classList.toggle('bg-emerald-500', ok);
            el.classList.toggle('bg-amber-500', !ok);
        });
        if (dot) {
            dot.title = ok ? '' : config.labels.offline;
        }
    }

    async function refresh({ force = false } = {}) {
        if (inFlight || (!force && (!toggle.checked || document.hidden))) {
            return;
        }

        inFlight = true;
        reloadIcon?.classList.add('animate-spin');

        try {
            const query = new URLSearchParams({ sesi_id: config.sesiId ?? '' });
            const response = await fetch(`${config.pollUrl}?${query}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                stamp(false);
                return;
            }

            const data = await response.json();
            renderStats(data.stats, known);
            renderStatus(data.status);
            renderTables(data.tables, seenRows);
            charts.update(data.charts);
            stamp(true);
        } catch (e) {
            stamp(false);
        } finally {
            inFlight = false;
            reloadIcon?.classList.remove('animate-spin');
        }
    }

    toggle.checked = readAutoRefresh(storageKey);
    toggle.addEventListener('change', () => {
        writeAutoRefresh(storageKey, toggle.checked);
        if (toggle.checked) refresh();
    });
    reloadBtn?.addEventListener('click', () => refresh({ force: true }));
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refresh();
    });

    stamp(true);
    setInterval(refresh, POLL_MS);
}

import ApexCharts from 'apexcharts';

function cssVar(name, fallback) {
    const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return v || fallback;
}

function isDark() {
    return document.documentElement.classList.contains('dark');
}

function hasData(spec) {
    if (spec.type === 'donut') {
        return (spec.series || []).some((v) => Number(v) > 0);
    }
    return (spec.categories || []).length > 0;
}

function buildOptions(spec, noDataText) {
    const muted = cssVar('--muted-foreground', '#666');
    const border = cssVar('--border', '#e5e7eb');
    const palette = [1, 2, 3, 4, 5].map((n, i) =>
        cssVar(`--chart-${n}`, ['#3b82f6', '#22c55e', '#eab308', '#a855f7', '#f97316'][i]),
    );
    const theme = isDark() ? 'dark' : 'light';

    const base = {
        chart: {
            fontFamily: 'inherit',
            height: 280,
            background: 'transparent',
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: true, easing: 'easeinout', speed: 600, dynamicAnimationSpeed: 300 },
        },
        theme: { mode: theme },
        colors: palette,
        dataLabels: { enabled: false },
        legend: { position: 'bottom', labels: { colors: muted } },
        tooltip: { theme },
        noData: { text: noDataText, style: { color: muted } },
    };

    if (spec.type === 'donut') {
        const show = hasData(spec);
        return {
            ...base,
            chart: { ...base.chart, type: 'donut' },
            labels: show ? spec.labels : [],
            series: show ? spec.series.map(Number) : [],
            stroke: { colors: [cssVar('--card', '#fff')] },
            plotOptions: { pie: { donut: { labels: { show: true, total: { show: true, label: 'Total', color: muted } } } } },
        };
    }

    const axis = { labels: { style: { colors: muted } } };

    return {
        ...base,
        chart: { ...base.chart, type: spec.type, stacked: Boolean(spec.stacked) },
        series: hasData(spec) ? spec.series : [{ name: spec.series?.[0]?.name ?? '', data: [] }],
        xaxis: { categories: spec.categories || [], ...axis },
        yaxis: { ...axis, labels: { ...axis.labels, formatter: (v) => String(Math.round(v)) }, min: 0 },
        grid: { borderColor: border },
        stroke: spec.type === 'area' ? { curve: 'smooth', width: 2 } : { width: 0 },
        fill:
            spec.type === 'area'
                ? { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.02, stops: [0, 90, 100] } }
                : { opacity: 1 },
        plotOptions: { bar: { horizontal: Boolean(spec.horizontal), borderRadius: 4, columnWidth: '55%' } },
        legend: { ...base.legend, show: spec.type === 'bar' && (spec.series || []).length > 1 },
    };
}

/**
 * Renders every `[data-dashboard-chart=key]` element from its spec and keeps them in sync.
 *
 * @param {Record<string, object>} specs
 * @param {string} noDataText
 */
export function createDashboardCharts(specs, noDataText) {
    let current = specs;
    const charts = new Map();

    function render() {
        charts.forEach((chart) => chart.destroy());
        charts.clear();

        document.querySelectorAll('[data-dashboard-chart]').forEach((el) => {
            const spec = current[el.dataset.dashboardChart];
            if (!spec) {
                return;
            }
            const chart = new ApexCharts(el, buildOptions(spec, noDataText));
            chart.render().then(() => el.classList.add('is-ready'));
            charts.set(el.dataset.dashboardChart, chart);
        });
    }

    function update(next) {
        current = next;
        charts.forEach((chart, key) => {
            const spec = next[key];
            if (spec) {
                chart.updateOptions(buildOptions(spec, noDataText), false, true);
            }
        });
    }

    render();

    // Re-theme when the dark class toggles on <html>.
    new MutationObserver(render).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });

    return { update };
}

import ApexCharts from 'apexcharts';
import Chart from 'chart.js/auto';

function chartColor(varName, fallback) {
    const v = getComputedStyle(document.documentElement).getPropertyValue(varName).trim();
    return v || fallback;
}

/**
 * @param {Record<string, unknown>|null|undefined} data
 */
export function initDashboardCharts(data) {
    if (!data || typeof data !== 'object') {
        return;
    }

    const sales = /** @type {{ categories?: string[], series?: number[] }} */ (data.sales);
    const category = /** @type {{ labels?: string[], series?: number[] }} */ (data.category);
    const traffic = /** @type {{ labels?: string[], values?: number[] }} */ (data.traffic);
    const distribution = /** @type {{ labels?: string[], values?: number[] }} */ (data.distribution);

    const fg = chartColor('--foreground', '#111');
    const muted = chartColor('--muted-foreground', '#666');
    const border = chartColor('--border', '#e5e7eb');
    const c1 = chartColor('--chart-1', '#3b82f6');
    const c2 = chartColor('--chart-2', '#22c55e');
    const c3 = chartColor('--chart-3', '#eab308');
    const c4 = chartColor('--chart-4', '#a855f7');
    const c5 = chartColor('--chart-5', '#f97316');

    if (sales?.categories && sales?.series) {
        const el = document.querySelector('#dash-sales-chart');
        if (el) {
            const chart = new ApexCharts(el, {
                chart: {
                    type: 'area',
                    height: 280,
                    fontFamily: 'inherit',
                    toolbar: { show: false },
                    zoom: { enabled: false },
                },
                series: [{ name: 'Series', data: sales.series }],
                xaxis: {
                    categories: sales.categories,
                    labels: { style: { colors: muted } },
                },
                yaxis: { labels: { style: { colors: muted } } },
                grid: { borderColor: border },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2, colors: [c1] },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.35,
                        opacityTo: 0.05,
                        stops: [0, 90, 100],
                        colorStops: [
                            { offset: 0, color: c1, opacity: 0.4 },
                            { offset: 100, color: c1, opacity: 0 },
                        ],
                    },
                },
                colors: [c1],
                tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
            });
            chart.render();
        }
    }

    if (category?.labels && category?.series) {
        const el = document.querySelector('#dash-category-chart');
        if (el) {
            const chart = new ApexCharts(el, {
                chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
                labels: category.labels,
                series: category.series,
                colors: [c1, c2, c3],
                legend: { position: 'bottom', labels: { colors: muted } },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    color: muted,
                                },
                            },
                        },
                    },
                },
                dataLabels: { style: { colors: [fg] } },
                stroke: { colors: [chartColor('--card', '#fff')] },
                tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
            });
            chart.render();
        }
    }

    if (traffic?.labels && traffic?.values) {
        const canvas = document.createElement('canvas');
        const wrap = document.querySelector('#dash-traffic-chart');
        if (wrap) {
            wrap.innerHTML = '';
            wrap.appendChild(canvas);
            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: traffic.labels,
                    datasets: [
                        {
                            label: 'Traffic',
                            data: traffic.values,
                            backgroundColor: c1,
                            borderRadius: 6,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        x: {
                            ticks: { color: muted },
                            grid: { color: border },
                        },
                        y: {
                            ticks: { color: muted },
                            grid: { color: border },
                        },
                    },
                },
            });
        }
    }

    if (distribution?.labels && distribution?.values) {
        const el = document.querySelector('#dash-distribution-chart');
        if (el) {
            const canvas = document.createElement('canvas');
            el.innerHTML = '';
            el.appendChild(canvas);
            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: distribution.labels,
                    datasets: [
                        {
                            data: distribution.values,
                            backgroundColor: [c1, c2, c3, c4, c5],
                            borderWidth: 0,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: muted },
                        },
                    },
                },
            });
        }
    }
}

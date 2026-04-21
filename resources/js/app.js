import './bootstrap';
import './kawalan-swal';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { initModal } from './modal';
import { kawalanDataTableDefaults } from './kawalan-datatables';

Alpine.plugin(collapse);

window.kawalanDataTableDefaults = kawalanDataTableDefaults;

window.Alpine = Alpine;

Alpine.start();

initModal();

document.addEventListener('DOMContentLoaded', () => {
    if (window.__dashboardChartData) {
        import('./charts.js').then(({ initDashboardCharts }) => {
            initDashboardCharts(window.__dashboardChartData);
        });
    }
});

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        chunkSizeWarningLimit: 550,
        rolldownOptions: {
            checks: {
                pluginTimings: false,
            },
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/apexcharts')) {
                        return 'apexcharts';
                    }
                    if (id.includes('node_modules/chart.js')) {
                        return 'chartjs';
                    }
                },
            },
        },
    },
});

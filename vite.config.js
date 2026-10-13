import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ command }) => ({
    // Relative asset URLs in the build (fonts in CSS, lazy JS chunks) so the app also works
    // from a sub-path such as http://<LAN IP>/apcv2. Dev server keeps the plugin default.
    base: command === 'build' ? './' : undefined,
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
}));

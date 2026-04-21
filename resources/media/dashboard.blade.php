<x-dashboard-layout :title="$title" role="media">
    <div class="mx-auto max-w-[1600px] space-y-6">
        <x-page-header :title="$title" :subtitle="__('Library and publishing metrics')" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <x-stats-card :label="$stat['label']" :value="$stat['value']" :hint="$stat['hint']" />
            @endforeach
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 space-y-4 xl:col-span-8">
                <x-chart-panel :title="__('Uploads & usage')" id="dash-sales-chart" />

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-chart-panel :title="__('Asset types')" id="dash-category-chart" />
                    <x-recent-table
                        :title="__('Recent uploads')"
                        :columns="[__('ID'), __('File'), __('Date'), __('Status')]"
                        :rows="$recentRows"
                    />
                </div>
            </div>

            <div class="col-span-12 space-y-4 xl:col-span-4">
                <x-quick-actions :actions="$quickActions" />

                <x-chart-panel :title="__('Bandwidth')" id="dash-traffic-chart" />

                <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
                    <h3 class="mb-3 text-sm font-semibold text-card-foreground">{{ __('Storage split') }}</h3>
                    <div id="dash-distribution-chart" class="mx-auto h-56 max-w-xs"></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.__dashboardChartData = @json($chartData);
        </script>
    @endpush
</x-dashboard-layout>

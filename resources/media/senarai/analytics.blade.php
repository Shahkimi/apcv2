<x-dashboard-layout :title="__('Analitik Senarai Kehadiran')" role="media">
    <x-kawalan-shell>
        <x-crud-header :title="__('Analitik Senarai Kehadiran')" :description="__('Pantau kemajuan pengumuman kehadiran secara masa nyata.')" :show-create="false" />

        <x-senarai-analytics
            :progress="$progress"
            :all-sesis="$allSesis"
            :selected-sesi-id="$selectedSesiId"
            :form-action="route('media.senarai.analytics')"
            :poll-url="route('media.senarai.progress.analytics')"
            :datatable-url="route('media.senarai.progress.announced')"
        />
    </x-kawalan-shell>
</x-dashboard-layout>

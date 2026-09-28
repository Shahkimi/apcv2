<x-dashboard-layout :title="__('Analitik Senarai Kehadiran')" role="admin">
    <x-kawalan-shell>
        <x-crud-header :title="__('Analitik Senarai Kehadiran')" :description="__('Pantau kemajuan pengumuman kehadiran secara masa nyata.')" :show-create="false" />

        <x-senarai-analytics
            :progress="$progress"
            :all-sesis="$allSesis"
            :selected-sesi-id="$selectedSesiId"
            :form-action="route('admin.senarai.analytics')"
            :poll-url="route('admin.senarai.progress.analytics')"
            :datatable-url="route('admin.senarai.progress.announced')"
        />
    </x-kawalan-shell>
</x-dashboard-layout>

<x-dashboard-layout :title="$title" role="admin">
    <x-dashboard-live
        :title="$title"
        :subtitle="$subtitle"
        :role="$role"
        :layout="$layout"
        :payload="$payload"
        :all-sesis="$allSesis"
        :selected-sesi-id="$selectedSesiId"
        :poll-url="$pollUrl"
    />
</x-dashboard-layout>

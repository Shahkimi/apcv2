@php
    $profileTitle = __('Profile');
@endphp

<x-dashboard-layout :title="$profileTitle" :role="$dashboardRole">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header :title="$profileTitle" :subtitle="__('Manage your account settings')" />

        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-dashboard-layout>

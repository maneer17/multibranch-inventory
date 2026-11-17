@php
    $current = app()->getLocale();
    $next = $current === 'ar' ? 'en' : 'ar';
    $label = $current === 'ar' ? 'EN' : 'AR';
@endphp

<a
    href="{{ route('locale.switch', $next) }}"
    class="flex items-center justify-center rounded-lg p-2 text-sm font-medium text-gray-500 outline-none transition hover:bg-gray-500/5 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-500/10 dark:hover:text-gray-200"
    title="{{ $label }}"
>
    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
    </svg>
    <span class="ms-1 text-xs font-bold">{{ $label }}</span>
</a>

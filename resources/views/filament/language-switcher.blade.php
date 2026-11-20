@php
    $toArabic = app()->getLocale() !== 'ar';
@endphp

<a
    href="{{ route('locale.switch', $toArabic ? 'ar' : 'en') }}"
    class="stoq-lang"
    lang="{{ $toArabic ? 'ar' : 'en' }}"
    hreflang="{{ $toArabic ? 'ar' : 'en' }}"
>{{ $toArabic ? 'العربية' : 'English' }}</a>

<style>
    .stoq-lang {
        display: inline-flex;
        align-items: center;
        padding: 0.375rem 0.875rem;
        border: 1px solid color-mix(in srgb, currentColor 22%, transparent);
        border-radius: 9999px;
        color: inherit;
        font-size: 0.8125rem;
        font-weight: 600;
        line-height: 1;
        text-decoration: none;
        white-space: nowrap;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }

    .stoq-lang:hover {
        background-color: color-mix(in srgb, currentColor 8%, transparent);
        border-color: color-mix(in srgb, currentColor 35%, transparent);
    }

    .stoq-lang:focus-visible {
        outline: 2px solid currentColor;
        outline-offset: 2px;
    }
</style>

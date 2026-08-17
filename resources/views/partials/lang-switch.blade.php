@php
    $current = app()->getLocale();
    $other = $current === 'ar' ? 'en' : 'ar';
    // Preserve whatever the visitor arrived with (table number, utm) on switch.
    $target = request()->fullUrlWithQuery(['lang' => $other]);
    $visible = $other === 'ar' ? 'عربي' : 'EN';
    $description = $other === 'ar' ? __('menu.switch_to_arabic') : __('menu.switch_to_english');
@endphp

<a href="{{ $target }}"
   rel="alternate" hreflang="{{ $other }}"
   aria-label="{{ $visible }} — {{ $description }}"
   class="u-focus inline-flex items-center gap-2 rounded-[2px] border border-[color:var(--rule)]
          bg-[color:color-mix(in_srgb,var(--ground)_70%,transparent)] h-12 px-4 text-[0.75rem] font-semibold
          tracking-[0.12em] text-accent backdrop-blur transition
          hover:bg-accent hover:text-[color:var(--ground)]">
    {{ $visible }}
</a>

@props(['name'])

<svg {{ $attributes->merge(['class' => 'size-4 shrink-0', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('house')
            <path d="m3 10 9-7 9 7" />
            <path d="M5 9v12h14V9M9 21v-7h6v7" />
            @break
        @case('map')
            <path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3z" />
            <path d="M9 3v15m6-12v15" />
            @break
        @case('shield')
            <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11" />
            <path d="m9 12 2 2 4-4" />
            @break
        @case('users')
            <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
            <circle cx="10" cy="7" r="4" />
            <path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
            @break
        @case('clipboard-list')
            <rect x="5" y="4" width="14" height="17" rx="2" />
            <path d="M9 4.5h6a1.5 1.5 0 0 0-3-1.5 1.5 1.5 0 0 0-3 1.5ZM9 11h6m-6 4h6" />
            @break
        @case('settings')
            <path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" />
            <path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.5.9l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.5-.9l-1.7.7-1.4-2.4 1.4-1.1a7 7 0 0 1 0-1.8l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.5-.9l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.5.9l1.7-.7 1.4 2.4-1.4 1.1a7 7 0 0 1-.1 1.7Z" transform="translate(-1 -1) scale(.95)" />
            @break
        @case('database')
            <ellipse cx="12" cy="5" rx="8" ry="3" />
            <path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3" />
            @break
        @case('package')
            <path d="m12 3 9 5-9 5-9-5zM3 8v9l9 5 9-5V8M12 13v9m-4-17 9 5" />
            @break
        @case('truck')
            <path d="M3 6h11v12H3zM14 10h4l3 3v5h-7z" />
            <circle cx="7.5" cy="18" r="2" />
            <circle cx="18" cy="18" r="2" />
            @break
        @case('user-round')
            <circle cx="12" cy="8" r="4" />
            <path d="M5 21a7 7 0 0 1 14 0" />
            @break
        @case('stethoscope')
            <path d="M6 3v5a4 4 0 0 0 8 0V3M4 3h4m4 0h4m-6 9v2a5 5 0 0 0 10 0v-1" />
            <circle cx="18" cy="11" r="2" />
            @break
        @case('badge-check')
            <path d="M12 3 15 5l4-.2.2 4L21 12l-2 3 .2 4-4 .2L12 21l-3-2-4 .2L4.8 15 3 12l2-3-.2-4 4-.2z" />
            <path d="m9 12 2 2 4-4" />
            @break
        @case('heart-pulse')
            <path d="M20.8 8.6c0 5.4-8.8 11.4-8.8 11.4S3.2 14 3.2 8.6A4.6 4.6 0 0 1 12 6.3a4.6 4.6 0 0 1 8.8 2.3Z" />
            <path d="M4 12h4l2-3 3 6 2-3h5" />
            @break
        @case('tags')
            <path d="m20 13-7 7-10-10V3h7z" />
            <circle cx="7.5" cy="7.5" r="1" />
            <path d="m14 3 7 7" />
            @break
        @case('badge-dollar')
            <circle cx="12" cy="12" r="9" />
            <path d="M16 8.5c-.7-.8-1.8-1.2-3.2-1.2-1.7 0-2.8.8-2.8 2s1 1.8 2.8 2.2 3.2 1 3.2 2.4-1.2 2.3-3 2.3c-1.5 0-2.8-.5-3.7-1.5M12.5 5v14" />
            @break
        @case('receipt')
            <path d="M5 3 7 4.5 9 3l3 1.5L15 3l2 1.5L19 3v18l-2-1.5-2 1.5-3-1.5L9 21l-2-1.5L5 21z" />
            <path d="M8 9h8m-8 4h8m-8 4h5" />
            @break
        @case('percent')
            <circle cx="7" cy="7" r="2.5" />
            <circle cx="17" cy="17" r="2.5" />
            <path d="m19 5-14 14" />
            @break
        @case('boxes')
            <path d="m3 7 6-3 6 3-6 3zM3 7v7l6 3 6-3V7m-6 3v7" />
            <path d="m15 10 4-2 3 1.5v6L16 19l-4-2" />
            @break
        @case('calendar-clock')
            <rect x="3" y="4" width="13" height="16" rx="2" />
            <path d="M7 2v4m5-4v4M3 9h13m3 4v3l2 1" />
            <circle cx="18" cy="16" r="4" />
            @break
        @case('chart')
            <path d="M4 19V5m0 14h17" />
            <path d="m7 15 4-4 3 2 6-7" />
            @break
        @case('clipboard-check')
            <rect x="5" y="4" width="14" height="17" rx="2" />
            <path d="M9 4.5h6a1.5 1.5 0 0 0-3-1.5 1.5 1.5 0 0 0-3 1.5Zm0 9 2 2 4-4" />
            @break
        @case('rotate-ccw')
            <path d="M3 7v5h5M4.5 12a8 8 0 1 0 2-5.5L3 12" />
            @break
        @case('package-check')
            <path d="m12 3 9 5-9 5-9-5zM3 8v9l9 5 4-2" />
            <path d="m16 16 2 2 4-4M12 13v4" />
            @break
        @case('wallet')
            <path d="M4 6.5h15a2 2 0 0 1 2 2V19H5a2 2 0 0 1-2-2V6a3 3 0 0 1 3-3h12v3" />
            <path d="M21 12h-5a2 2 0 0 0 0 4h5" />
            <path d="M17 14h.01" />
            @break
        @case('shopping-cart')
            <path d="M3 3h2l2.5 12.5a2 2 0 0 0 2 1.5h8a2 2 0 0 0 2-1.6L21 8H6" />
            <circle cx="10" cy="21" r="1" />
            <circle cx="18" cy="21" r="1" />
            @break
        @case('scan-barcode')
            <path d="M4 7V5a1 1 0 0 1 1-1h2m10 0h2a1 1 0 0 1 1 1v2M4 17v2a1 1 0 0 0 1 1h2m10 0h2a1 1 0 0 0 1-1v-2M7 9v6m3-6v6m3-6v6m2-6v6m3-6v6" />
            @break
        @case('pill')
            <path d="m10.5 20.5 10-10a5.7 5.7 0 0 0-8-8l-10 10a5.7 5.7 0 0 0 8 8Z" />
            <path d="m8 8 8 8" />
            @break
        @case('activity')
            <path d="M3 12h4l3-8 4 16 3-8h4" />
            @break
        @case('landmark')
            <path d="m3 10 9-7 9 7M4 10h16M5 10v9m5-9v9m4-9v9m5-9v9M3 21h18" />
            @break
        @case('hand-coins')
            <path d="M12 8V5a2 2 0 1 1 4 0v3m-2-5v2m-8 9 3-3a2 2 0 0 1 3 0l1 1h4a2 2 0 0 1 2 2v1l-7 4H6l-3-3v-3h3Z" />
            <circle cx="18" cy="6" r="2" />
            @break
        @case('file-chart')
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <path d="M14 2v6h6M8 17v-3m4 3v-6m4 6v-5" />
            @break
        @default
            <circle cx="12" cy="12" r="9" />
    @endswitch
</svg>

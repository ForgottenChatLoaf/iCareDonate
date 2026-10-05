@props(['name', 'size' => 18, 'class' => '', 'style' => ''])

@php
/**
 * Lucide icon name mapper.
 * Maps our internal icon names → official Lucide kebab-case names.
 * Full reference: https://lucide.dev/icons/
 */
$lucideMap = [
    // Navigation & structure
    'home'         => 'house',
    'dashboard'    => 'layout-dashboard',
    'list'         => 'list',
    'search'       => 'search',
    'menu'         => 'menu',
    'arrow-right'  => 'arrow-right',
    'arrow-left'   => 'arrow-left',
    'eye'          => 'eye',
    'repeat'       => 'repeat',

    // Donation / giving
    'heart'        => 'heart-handshake',
    'gift'         => 'hand-heart',
    'package'      => 'package',
    'tag'          => 'tag',
    'ticket'       => 'ticket',
    'donate'       => 'hand-heart',

    // People
    'user'         => 'circle-user-round',
    'users'        => 'users',
    'shield'       => 'shield-check',

    // Status / feedback
    'check'        => 'check',
    'check-circle' => 'circle-check',
    'alert'        => 'triangle-alert',
    'bell'         => 'bell',
    'info'         => 'info',

    // Content / files
    'file-text'    => 'file-text',
    'message'      => 'message-circle',
    'printer'      => 'printer',
    'edit'         => 'pencil',
    'trash'        => 'trash-2',
    'mic'          => 'mic',

    // Admin / reporting
    'bar-chart'    => 'chart-no-axes-combined',
    'activity'     => 'activity',
    'settings'     => 'settings',
    'laptop'       => 'laptop',
    'log-in'       => 'log-in',
    'logout'       => 'log-out',

    // Info / verification
    'info'         => 'info',
    'badge-check'  => 'badge-check',
    'building'     => 'building-2',
    'credit-card'  => 'credit-card',
    'peso'         => 'philippine-peso',
    'megaphone'    => 'megaphone',

    // Time / location
    'calendar'     => 'calendar-days',
    'clock'        => 'clock',
    'map-pin'      => 'map-pin',

    // Contact
    'phone'        => 'phone',
    'mail'         => 'mail',

    // Theme
    'sun'          => 'sun',
    'moon'         => 'moon',

    // Categories
    'utensils'     => 'utensils',
    'book-open'    => 'book-open',
    'shirt'        => 'shirt',
    'help-circle'  => 'circle-help',
    'paperclip'    => 'paperclip',
];

$lucideIcon = $lucideMap[$name] ?? $name;
@endphp

<i data-lucide="{{ $lucideIcon }}"
   class="lucide-icon {{ $class }}"
   style="width:{{ $size }}px;height:{{ $size }}px;display:inline-block;vertical-align:middle;flex-shrink:0;{{ $style }}"></i>

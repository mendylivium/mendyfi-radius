@props(['name', 'uptimeLimit' => 0, 'dataLimit' => 0, 'validity' => 0])

@php
    $seconds = $validity > 0 ? $validity : $uptimeLimit;

    if ($seconds == 0 && $dataLimit == 0) {
        $badgeClass = 'badge-neutral';
    } elseif ($seconds > 0) {
        $hours = $seconds / 3600;
        $badgeClass = match(true) {
            $hours <= 12  => 'badge-info',
            $hours <= 24  => 'badge-primary',
            $hours <= 168 => 'badge-secondary',
            $hours <= 336 => 'badge-accent',
            $hours <= 720 => 'badge-warning',
            default       => 'badge-neutral',
        };
    } elseif ($dataLimit > 0) {
        $badgeClass = 'badge-secondary';
    } else {
        $badgeClass = 'badge-ghost';
    }
@endphp

<span class="badge {{ $badgeClass }} badge-sm">{{ $name }}</span>

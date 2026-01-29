@props(['name', 'uptimeLimit' => 0, 'dataLimit' => 0, 'validity' => 0])

@php
    // Determine badge class based on duration
    $badgeClass = 'badge-profile';
    
    // Use validity first, then uptime_limit
    $seconds = $validity > 0 ? $validity : $uptimeLimit;
    
    if ($seconds == 0 && $dataLimit == 0) {
        $badgeClass = 'badge-profile-unlimited';
    } elseif ($seconds > 0) {
        $hours = $seconds / 3600;
        if ($hours <= 12) {
            $badgeClass = 'badge-profile-hours';
        } elseif ($hours <= 24) {
            $badgeClass = 'badge-profile-day';
        } elseif ($hours <= 168) { // 7 days
            $badgeClass = 'badge-profile-days';
        } elseif ($hours <= 336) { // 14 days / 2 weeks
            $badgeClass = 'badge-profile-weekly';
        } elseif ($hours <= 720) { // 30 days
            $badgeClass = 'badge-profile-monthly';
        } else {
            $badgeClass = 'badge-profile-unlimited';
        }
    } elseif ($dataLimit > 0) {
        // Data-only profiles
        $badgeClass = 'badge-profile-days';
    }
@endphp

<span class="badge {{ $badgeClass }}">{{ $name }}</span>

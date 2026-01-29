@props(['status', 'icon' => null])

@php
    $statusMap = [
        'available' => ['class' => 'status-available', 'icon' => 'fa-check-circle', 'text' => 'Available'],
        'active' => ['class' => 'status-active', 'icon' => 'fa-wifi', 'text' => 'Active'],
        'used' => ['class' => 'status-used', 'icon' => 'fa-clock', 'text' => 'Used'],
        'expired' => ['class' => 'status-used', 'icon' => 'fa-clock', 'text' => 'Expired'],
        'suspended' => ['class' => 'status-suspended', 'icon' => 'fa-ban', 'text' => 'Suspended'],
        'enabled' => ['class' => 'status-enabled', 'icon' => 'fa-check', 'text' => 'Enabled'],
        'disabled' => ['class' => 'status-suspended', 'icon' => 'fa-times', 'text' => 'Disabled'],
        'low' => ['class' => 'status-low', 'icon' => 'fa-exclamation-circle', 'text' => 'Low'],
        'out' => ['class' => 'status-out', 'icon' => 'fa-times-circle', 'text' => 'Out'],
        'ok' => ['class' => 'status-ok', 'icon' => 'fa-check-circle', 'text' => 'OK'],
    ];
    
    $statusKey = strtolower($status);
    $config = $statusMap[$statusKey] ?? ['class' => 'badge-secondary', 'icon' => 'fa-info-circle', 'text' => ucfirst($status)];
@endphp

<span class="badge {{ $config['class'] }}">
    @if($icon !== false)
        <i class="fas {{ $icon ?? $config['icon'] }} mr-1"></i>
    @endif
    {{ $config['text'] }}
</span>

@props(['status', 'icon' => null])

@php
    $statusMap = [
        'available' => ['class' => 'badge-success', 'text' => 'Available'],
        'active'    => ['class' => 'badge-primary', 'text' => 'Active'],
        'used'      => ['class' => 'badge-ghost',   'text' => 'Used'],
        'expired'   => ['class' => 'badge-ghost',   'text' => 'Expired'],
        'suspended' => ['class' => 'badge-error',   'text' => 'Suspended'],
        'enabled'   => ['class' => 'badge-success', 'text' => 'Enabled'],
        'disabled'  => ['class' => 'badge-error',   'text' => 'Disabled'],
        'low'       => ['class' => 'badge-warning', 'text' => 'Low'],
        'out'       => ['class' => 'badge-error',   'text' => 'Out'],
        'ok'        => ['class' => 'badge-success', 'text' => 'OK'],
    ];

    $statusKey = strtolower($status);
    $config = $statusMap[$statusKey] ?? ['class' => 'badge-ghost', 'text' => ucfirst($status)];
@endphp

<span class="badge {{ $config['class'] }} badge-sm">{{ $config['text'] }}</span>

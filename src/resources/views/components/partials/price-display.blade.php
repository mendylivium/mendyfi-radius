@props(['price', 'showCurrency' => false, 'currency' => ''])

@if($price == 0)
    <span class="text-success text-xs font-semibold">FREE</span>
@else
    <span class="font-mono font-semibold text-sm">{{ $showCurrency ? $currency : '' }}{{ number_format($price, 2) }}</span>
@endif

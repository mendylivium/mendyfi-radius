@props(['price', 'showCurrency' => false, 'currency' => ''])

@if($price == 0)
    <span class="price-free">FREE</span>
@else
    <span class="price-value">{{ $showCurrency ? $currency : '' }}{{ number_format($price, 2) }}</span>
@endif

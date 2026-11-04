@props(['label','value','hint'=>'','icon'=>'fa-chart-simple','tone'=>'primary'])
<div {{ $attributes->merge(['class'=>'stat-card h-100']) }}>
    <div class="stat-top"><span class="stat-label">{{ $label }}</span><span class="stat-icon bg-{{ $tone }}-subtle text-{{ $tone }}"><i class="fa-solid {{ $icon }}"></i></span></div>
    <div class="stat-value">{{ $value }}</div>
    <div class="stat-hint">{{ $hint }}</div>
</div>

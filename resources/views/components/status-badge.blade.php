@props(['label','type'=>'gray'])
<span {{ $attributes->merge(['class'=>'status-badge '.$type]) }}>{{ $label }}</span>

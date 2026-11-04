@props(['title','tone'=>'primary'])
<div {{ $attributes->merge(['class'=>'data-panel']) }}>
    <div class="panel-head"><div class="panel-title"><span class="dot" style="background:var(--{{ $tone }})"></span>{{ $title }}</div><div>{{ $actions ?? '' }}</div></div>
    <div class="panel-body">{{ $slot }}</div>
</div>

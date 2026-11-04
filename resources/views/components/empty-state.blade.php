@props(['icon'=>'fa-inbox','title'=>'Belum ada data','description'=>''])
<div class="empty-state"><div class="empty-icon"><i class="fa-solid {{ $icon }}"></i></div><div class="empty-title">{{ $title }}</div>@if($description)<div class="empty-desc">{{ $description }}</div>@endif</div>

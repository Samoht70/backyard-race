@props(['url', 'label' => null])
<a href="{{ $url }}" class="break-all">{{ $label ?? $url }}</a>

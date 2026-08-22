@use(App\Listeners\EmbedBrandMarkListener)
@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}"><img src="cid:{{ EmbedBrandMarkListener::CID }}" alt="" width="28" height="28"><span class="brand-name">{!! $slot !!}</span></a>
</td>
</tr>

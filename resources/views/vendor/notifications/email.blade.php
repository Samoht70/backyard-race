<x-mail::message>
@if (! empty($greeting))
<x-mail::heading>{{ $greeting }}</x-mail::heading>
@else
<x-mail::heading>@lang($level === 'error' ? 'Whoops!' : 'Hello!')</x-mail::heading>
@endif

@foreach ($introLines as $line)
{{ $line }}

@endforeach
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

@foreach ($outroLines as $line)
{{ $line }}

@endforeach
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('Regards,')<br>
{{ config('app.name') }}
@endif

@isset($actionText)
<x-slot:subcopy>
@lang(
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
    'into your web browser:',
    [
        'actionText' => $actionText,
    ]
) <x-mail::link :url="$actionUrl" :label="$displayableActionUrl" />
</x-slot:subcopy>
@endisset
</x-mail::message>

{{-- <x-fc.action> that submits a small POST/PUT/DELETE form (activate, delete…). --}}
@props(['action', 'method' => 'POST', 'icon', 'label', 'tone' => null, 'confirm' => null])
<form action="{{ $action }}" method="POST">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif
    <x-fc.action type="submit" :icon="$icon" :label="$label" :tone="$tone" :data-confirm="$confirm" {{ $attributes }} />
</form>

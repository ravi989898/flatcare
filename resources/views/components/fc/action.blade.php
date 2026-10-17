{{--
    Square icon button for a table row, with a hover tooltip (label). A link
    when href is given, otherwise a <button> (pass type="submit",
    data-toggle="modal" data-target="#…", data-confirm="…" as needed).
    tone="danger|warning" colours the hover state.
--}}
@props(['href' => null, 'icon', 'label', 'tone' => null, 'disabled' => false])
@if ($href && !$disabled)
    <a href="{{ $href }}" {{ $attributes->class(['fc-act', $tone]) }} data-tip="{{ $label }}" aria-label="{{ $label }}"><i class="bi bi-{{ $icon }}" aria-hidden="true"></i></a>
@else
    <button type="{{ $attributes->get('type', 'button') }}" {{ $attributes->except('type')->class(['fc-act', $tone]) }} data-tip="{{ $label }}" aria-label="{{ $label }}" @disabled($disabled)><i class="bi bi-{{ $icon }}" aria-hidden="true"></i></button>
@endif

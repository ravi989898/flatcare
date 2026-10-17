{{--
    Coloured status pill. The colour comes from the status value (paid,
    overdue, active…) unless tone="success|warning|danger|info|purple|
    neutral|brand" is given; the label defaults to the status, humanised.
--}}
@props(['status' => null, 'label' => null, 'tone' => null, 'dot' => true])
@php
    $key = strtolower((string) $status);
    $tones = [
        'success' => ['active', 'paid', 'resolved', 'approved', 'completed', 'published', 'verified', 'converted', 'done', 'on_duty', 'present', 'yes', 'success', 'owner'],
        'warning' => ['pending', 'partial', 'partially_paid', 'in_progress', 'under_construction', 'open', 'medium', 'awaiting', 'contacted', 'unpaid'],
        'danger' => ['overdue', 'rejected', 'suspended', 'blocked', 'failed', 'cancelled', 'canceled', 'expired', 'under_maintenance', 'urgent', 'high', 'denied', 'deleted', 'error'],
        'info' => ['new', 'upcoming', 'scheduled', 'checked_in', 'ongoing', 'running', 'tenant', 'low', 'info', 'pinned'],
        'neutral' => ['inactive', 'closed', 'draft', 'archived', 'checked_out', 'past', 'ended', 'occupant', 'no', 'off_duty', 'lost'],
    ];
    $tone ??= collect($tones)->search(fn ($values) => in_array($key, $values, true)) ?: 'neutral';
    $label ??= \Illuminate\Support\Str::of((string) $status)->replace(['_', '-'], ' ')->ucfirst();
@endphp
<span {{ $attributes->class(['fc-pill', $tone, 'dot' => $dot]) }}>{{ $label }}</span>

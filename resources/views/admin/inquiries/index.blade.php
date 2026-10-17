@extends('adminlte::page')

@section('title', 'Inquiries')

@php
    $statusTone = ['new' => 'danger', 'contacted' => 'success', 'dismissed' => 'neutral'];
    $statusTabs = [
        '' => ['All', $statusCounts->sum()],
        'new' => ['New', $statusCounts->get('new', 0)],
        'contacted' => ['Contacted', $statusCounts->get('contacted', 0)],
        'dismissed' => ['Dismissed', $statusCounts->get('dismissed', 0)],
    ];
@endphp

@section('content_header')
    <h1>Trial Inquiries</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="fc-card">
        <x-fc.tabs name="status" label="Inquiry status" :tabs="$statusTabs" />

        <x-fc.toolbar placeholder="Search society, name, email, phone…" :keep="['status']" />

        @if ($inquiries->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="society">Society</x-fc.th>
                            <x-fc.th sort="contact">Contact</x-fc.th>
                            <th>Address</th>
                            <th>Status</th>
                            <x-fc.th sort="received" :default="true" first="desc">Received</x-fc.th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inquiries as $inquiry)
                            <tr>
                                <td>
                                    <div class="fc-person">
                                        <x-fc.avatar :name="$inquiry->society_name" />
                                        <span class="fc-strong">{{ $inquiry->society_name }}</span>
                                    </div>
                                </td>
                                <td>
                                    {{ $inquiry->contact_name }}
                                    <span class="fc-sub">
                                        <a href="mailto:{{ $inquiry->email }}">{{ $inquiry->email }}</a>
                                        &middot;
                                        <a href="tel:{{ $inquiry->phone }}">{{ $inquiry->phone }}</a>
                                    </span>
                                </td>
                                <td class="text-muted small" title="{{ $inquiry->address }}">{{ Str::limit($inquiry->address, 60) }}</td>
                                <td><x-fc.pill :status="$inquiry->status" :tone="$statusTone[$inquiry->status] ?? 'neutral'" /></td>
                                <td class="fc-nowrap" title="{{ $inquiry->created_at->format('d M Y, h:i A') }}">
                                    {{ $inquiry->created_at->format('d M Y') }}
                                    <span class="fc-sub">{{ $inquiry->created_at->diffForHumans() }}</span>
                                </td>
                                <td>
                                    <div class="fc-actions">
                                        @if ($inquiry->status === 'new')
                                            <x-fc.post-action :action="route('admin.inquiries.mark_contacted', $inquiry)" icon="check2-circle" label="Mark contacted" />
                                            <x-fc.post-action :action="route('admin.inquiries.dismiss', $inquiry)" icon="x-circle" label="Dismiss" tone="warning" />
                                        @endif
                                        <x-fc.post-action :action="route('admin.inquiries.destroy', $inquiry)" method="DELETE"
                                            icon="trash" label="Delete" tone="danger" confirm="Delete this inquiry?" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="envelope-open" title="No trial inquiries yet" text="Requests sent from the website's free-trial form appear here." :keep="['status']" />
        @endif

        <x-fc.footer :paginator="$inquiries" />
    </div>
@stop

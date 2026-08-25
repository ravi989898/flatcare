@extends('society.layout')

@section('title', 'New Poll')

@section('content')
    <div class="mb-4">
        <a href="{{ route('society.polls.index') }}" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left"></i> Back to Polls
        </a>
        <h1 class="h3 mb-0 mt-2">New Poll</h1>
    </div>

    <div class="card stat-card">
        <div class="card-body">
            <form action="{{ route('society.polls.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label small text-muted">Question</label>
                    <input type="text" name="question" class="form-control @error('question') is-invalid @enderror" value="{{ old('question') }}" required>
                    @error('question')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label small text-muted">Description (optional)</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small text-muted">Closes At (optional)</label>
                    <input type="datetime-local" name="closes_at" class="form-control @error('closes_at') is-invalid @enderror" value="{{ old('closes_at') }}">
                    @error('closes_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <label class="form-label small text-muted">Options</label>
                @php $oldOptions = old('options', ['', '']); @endphp
                <div id="options-wrap">
                    @foreach ($oldOptions as $option)
                        <div class="input-group mb-2">
                            <input type="text" name="options[]" class="form-control" value="{{ $option }}" placeholder="Option label" required>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="
                    const wrap = document.getElementById('options-wrap');
                    const row = document.createElement('div');
                    row.className = 'input-group mb-2';
                    row.innerHTML = '<input type=&quot;text&quot; name=&quot;options[]&quot; class=&quot;form-control&quot; placeholder=&quot;Option label&quot; required>';
                    wrap.appendChild(row);
                ">
                    <i class="bi bi-plus-lg"></i> Add Option
                </button>

                <div>
                    <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Publish Poll</button>
                </div>
            </form>
        </div>
    </div>
@stop

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — {{ config('app.name', 'FlatCare') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root { --brand: #2f6f4f; --brand-dark: #234f38; --brand-light: #eaf5ee; --ink: #17241d; }
        body {
            font-family: 'Inter', system-ui, sans-serif; color: var(--ink); min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: radial-gradient(circle at top right, var(--brand-light), #fff 60%);
        }
        .brand { font-weight: 800; letter-spacing: -.02em; color: var(--brand); }
        .auth-card { max-width: 420px; width: 100%; border: 1px solid #eceff1; border-radius: 1rem; }
        .btn-brand { background: var(--brand); border-color: var(--brand); color: #fff; }
        .btn-brand:hover { background: var(--brand-dark); border-color: var(--brand-dark); color: #fff; }
        a { color: var(--brand); }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="card auth-card mx-auto shadow-sm">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <a href="{{ route('home') }}" class="brand text-decoration-none fs-4">
                        @include('partials.brand', ['height' => '32px'])
                    </a>
                    <p class="text-muted mt-2 mb-0">Sign in to your society portal</p>
                </div>

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form action="{{ route('society.login') }}" method="POST" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                            id="email" name="email" value="{{ old('email') }}"
                            autocomplete="username" autofocus required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                            id="password" name="password" autocomplete="current-password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="btn btn-brand w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>
                </form>
            </div>
        </div>
        <p class="text-center text-muted mt-3">
            <a href="{{ route('home') }}"><i class="bi bi-arrow-left"></i> Back to homepage</a>
        </p>
    </div>
</body>
</html>

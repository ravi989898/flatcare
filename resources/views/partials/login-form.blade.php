{{--
    The email + password + remember-me form shared by both sign-in pages.
    Same field names / method / CSRF / error handling as before; only the
    presentation changed. Param: $action (the route the form posts to).
--}}
<form action="{{ $action }}" method="post" novalidate>
    @csrf

    <div class="field">
        <i class="bi bi-envelope lead-icon" aria-hidden="true"></i>
        <input type="email" name="email" id="login-email" class="@error('email') is-invalid @enderror"
            value="{{ old('email') }}" placeholder="Enter your email" aria-label="Email"
            autofocus autocomplete="username" required>
        @error('email')
            <span class="err" role="alert">{{ $message }}</span>
        @enderror
    </div>

    <div class="field">
        <i class="bi bi-lock lead-icon" aria-hidden="true"></i>
        <input type="password" name="password" id="login-password" class="@error('password') is-invalid @enderror"
            placeholder="Enter your password" aria-label="Password" autocomplete="current-password" required>
        <button type="button" class="toggle-pass" data-toggle-password="login-password" aria-label="Show password">
            <i class="bi bi-eye-slash" aria-hidden="true"></i>
        </button>
        @error('password')
            <span class="err" role="alert">{{ $message }}</span>
        @enderror
    </div>

    <div class="row-opts">
        <label class="remember" for="remember">
            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            Remember me
        </label>
    </div>

    <button type="submit" class="btn-signin">
        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Sign in
    </button>
</form>

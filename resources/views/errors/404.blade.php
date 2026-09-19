<!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found – FlatCare</title>
    <meta name="description" content="The page you are looking for could not be found. Go back to the FlatCare home page.">
    {{-- A 404 must never be indexed. --}}
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: 'Inter', system-ui, sans-serif; color: #142019; background: linear-gradient(135deg, #fbfefc, #eef8f2); padding: 1.5rem; }
        .box { max-width: 34rem; text-align: center; }
        .code { font-size: 5rem; font-weight: 800; color: #157a3e; line-height: 1; }
        h1 { font-size: 1.7rem; margin: .5rem 0 .75rem; }
        p { color: #5d6b63; line-height: 1.6; }
        .links { display: flex; flex-wrap: wrap; gap: .75rem; justify-content: center; margin-top: 1.5rem; }
        a { color: #157a3e; font-weight: 600; }
        a.btn { background: linear-gradient(135deg, #1a8f47, #0f6631); color: #fff; text-decoration: none; padding: .75rem 1.5rem; border-radius: 999px; }
    </style>
</head>
<body>
    <main class="box">
        <div class="code" aria-hidden="true">404</div>
        <h1>We couldn’t find that page</h1>
        <p>The link may be old or mistyped. You can head back to the FlatCare home page, or explore our apartment and society management software.</p>
        <div class="links">
            <a class="btn" href="{{ url('/') }}">Go to FlatCare home</a>
            <a href="{{ url('/features') }}">Features</a>
            <a href="{{ url('/pricing') }}">Pricing</a>
            <a href="{{ url('/contact') }}">Contact</a>
        </div>
    </main>
</body>
</html>

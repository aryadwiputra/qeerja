<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Inter, sans-serif; font-size: 14px; line-height: 1.6; color: #1a1a1a; max-width: 700px; margin: 0 auto; padding: 40px; }
        h1 { font-size: 24px; margin-bottom: 8px; }
        h2 { font-size: 18px; margin-top: 24px; margin-bottom: 8px; }
        h3 { font-size: 16px; margin-top: 20px; }
        p { margin: 8px 0; }
        ul, ol { margin: 8px 0; padding-left: 24px; }
        li { margin: 4px 0; }
        hr { border: none; border-top: 1px solid #ccc; margin: 24px 0; }
        .meta { color: #666; font-size: 12px; margin-bottom: 24px; }
        strong { font-weight: 600; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">Project: {{ $project }} &middot; Updated: {{ $updated_at }}</div>
    <hr>
    {!! $content !!}
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="0;url={{ $url }}">
    <title>{{ __('login.verifying_title') }}</title>
    <script>window.location.replace(@json($url));</script>
</head>
<body></body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  @vite(['resources/js/app.js'])
  @inertiaHead
</head>
<body class="antialiased">
  @inertia
</body>
</html>

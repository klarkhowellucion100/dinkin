<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ config('app.aclsappname') }}</title>

    <link rel="icon" type="image/png" href="{{ url('frontend/img/logo.png') }}" />

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="{{ url('frontend/css/welcome.css') }}">


    <link rel="manifest" href="{{ url('manifest.json') }}" />
    <link rel="manifest" href="{{ url('frontend/js/pwa.js') }}" />

</head>

<body>

    <main class="landing-page">
        {{ $slot }}
    </main>

</body>

</html>

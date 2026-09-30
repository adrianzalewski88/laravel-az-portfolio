<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'Laravel - AZ Portfolio')
    </title>

    <meta
        name="description"
        content="@yield('description', 'Laravel - AZ Portfolio by Adrian Zalewski.')"
    >

    @vite([
        'resources/scss/app.scss',
        'resources/js/app.js',
    ])

    @livewireStyles

</head>

<body class="public-site">

    <div class="public-site__background">

        <div class="public-orb public-orb--one"></div>
        <div class="public-orb public-orb--two"></div>
        <div class="public-orb public-orb--three"></div>

    </div>

    <main class="public-site__main">

        {{ $slot ?? '' }}

        @yield('content')

    </main>

    @livewireScripts

</body>

</html>
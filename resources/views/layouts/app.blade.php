<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        @yield('title', 'AZ Portfolio')
    </title>

    @vite([
        'resources/scss/app.scss',
        'resources/js/app.js',
    ])

    @livewireStyles
</head>

<body>

    <div class="admin-shell">

        <aside class="admin-sidebar">

            <div class="admin-sidebar__brand">
                <a href="{{ route('home') }}">
                    <span class="gradient-text">
                        AZ Portfolio
                    </span>
                </a>
            </div>


            <nav class="admin-nav">

                <a
                    href="{{ route('dashboard') }}"
                    class="admin-nav__link"
                >
                    Dashboard
                </a>

                <a
                    href="{{ route('projects.index') }}"
                    class="admin-nav__link"
                >
                    Projects
                </a>

                <a
                    href="{{ route('categories.index') }}"
                    class="admin-nav__link"
                >
                    Categories
                </a>

                <a
                    href="{{ route('users.index') }}"
                    class="admin-nav__link"
                >
                    Users
                </a>

            </nav>


            <div class="admin-sidebar__footer">

                <div class="admin-user">

                    <strong>
                        {{ auth()->user()->name }}
                    </strong>

                    <span>
                        {{ auth()->user()->email }}
                    </span>

                </div>


                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="admin-logout"
                    >
                        Sign Out
                    </button>
                </form>

            </div>

        </aside>


        <main class="admin-main">

            @isset($slot)

                {{ $slot }}

            @else

                @yield('content')

            @endisset

        </main>

    </div>


    @livewireScripts

</body>

</html>
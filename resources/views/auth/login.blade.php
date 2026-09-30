@extends('layouts.guest')

@section('title', 'Sign In | AZ Portfolio')

@section('content')

<div class="auth-page">

    <div class="auth-card">

        <div class="auth-logo">
            <h1 class="gradient-text">
                AZ Portfolio
            </h1>
        </div>

        <h2 class="auth-title">
            Welcome back
        </h2>

        <p class="auth-subtitle">
            Sign in to manage your portfolio.
        </p>

        @if ($errors->any())
            <div class="auth-errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('login') }}"
            class="auth-form"
        >
            @csrf

            <div class="auth-field">

                <label
                    for="email"
                    class="auth-label"
                >
                    Email
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    class="auth-input"
                    autocomplete="email"
                    autofocus
                    required
                >

            </div>

            <div class="auth-field">

                <label
                    for="password"
                    class="auth-label"
                >
                    Password
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    class="auth-input"
                    autocomplete="current-password"
                    required
                >

            </div>

            <label class="auth-remember">

                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                >

                <span>
                    Remember me
                </span>

            </label>

            <button
                type="submit"
                class="btn-primary auth-submit"
            >
                Sign In
            </button>

        </form>

    </div>

</div>

@endsection
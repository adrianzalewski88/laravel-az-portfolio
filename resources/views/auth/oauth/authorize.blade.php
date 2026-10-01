<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>Authorize AZ Portfolio</title>

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>

<body>
    <main>
        <section>
            <h1>Authorize AZ Portfolio</h1>

            <p>
                <strong>{{ $client->name }}</strong>
                is requesting access to your account.
            </p>

            @if (count($scopes) > 0)
                <h2>This application is requesting:</h2>

                <ul>
                    @foreach ($scopes as $scope)
                        <li>
                            {{ $scope->description }}
                        </li>
                    @endforeach
                </ul>
            @endif

            <form
                method="POST"
                action="{{ route('passport.authorizations.approve') }}"
            >
                @csrf

                <input
                    type="hidden"
                    name="state"
                    value="{{ $request->state }}"
                >

                <input
                    type="hidden"
                    name="client_id"
                    value="{{ $client->getKey() }}"
                >

                <input
                    type="hidden"
                    name="auth_token"
                    value="{{ $authToken }}"
                >

                <button type="submit">
                    Authorize
                </button>
            </form>

            <form
                method="POST"
                action="{{ route('passport.authorizations.deny') }}"
            >
                @csrf

                @method('DELETE')

                <input
                    type="hidden"
                    name="state"
                    value="{{ $request->state }}"
                >

                <input
                    type="hidden"
                    name="client_id"
                    value="{{ $client->getKey() }}"
                >

                <input
                    type="hidden"
                    name="auth_token"
                    value="{{ $authToken }}"
                >

                <button type="submit">
                    Deny
                </button>
            </form>
        </section>
    </main>
</body>
</html>
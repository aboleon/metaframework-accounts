<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }} - Account Dashboard</title>
        <style>
            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #f5f5f5;
                color: #222;
            }

            .account-dashboard {
                max-width: 860px;
                margin: 48px auto;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 24px;
            }

            .account-dashboard h1 {
                margin-top: 0;
            }

            .account-dashboard form {
                margin-top: 24px;
            }

            .account-dashboard button {
                border: 0;
                border-radius: 6px;
                background: #111;
                color: #fff;
                padding: 10px 12px;
                cursor: pointer;
            }
        </style>
    </head>
    <body>
        <main class="account-dashboard">
            <h1>Account Dashboard</h1>
            <p>Skeleton dashboard ready. Add your front account widgets here.</p>
            @if (!empty($account))
                <p>Logged in as: {{ $account->email ?? $account->id }}</p>
            @endif

            <form method="POST" action="{{ route('account.logout') }}">
                @csrf
                <button type="submit">{{ __('mfw-auth.logout') }}</button>
            </form>
        </main>
    </body>
</html>

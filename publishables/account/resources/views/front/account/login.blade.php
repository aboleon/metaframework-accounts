<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }} - {{ __('mfw-auth.loginBtn') }}</title>
        <style>
            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #f5f5f5;
                color: #222;
            }

            .account-login {
                max-width: 420px;
                margin: 48px auto;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 24px;
            }

            .account-login h1 {
                margin: 0 0 20px;
                font-size: 24px;
            }

            .account-login .field {
                margin-bottom: 16px;
            }

            .account-login label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
            }

            .account-login input[type='email'],
            .account-login input[type='password'] {
                width: 100%;
                box-sizing: border-box;
                padding: 10px 12px;
                border: 1px solid #bbb;
                border-radius: 6px;
            }

            .account-login .remember {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 20px;
            }

            .account-login button {
                width: 100%;
                border: 0;
                border-radius: 6px;
                background: #111;
                color: #fff;
                padding: 10px 12px;
                cursor: pointer;
            }

            .account-login .errors {
                margin-bottom: 16px;
                padding: 12px;
                border-radius: 6px;
                background: #fce8e8;
                border: 1px solid #f1bcbc;
                color: #8a1f1f;
            }
        </style>
    </head>
    <body>
        <main class="account-login">
            <h1>{{ __('mfw-auth.loginBtn') }}</h1>

            @if ($errors->any())
                <div class="errors">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('account.login.store') }}">
                @csrf
                <div class="field">
                    <label for="email">{{ __('mfw-auth.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="field">
                    <label for="password">{{ __('mfw-auth.password.label') }}</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                </div>

                <label class="remember" for="remember">
                    <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span>{{ __('mfw-auth.keepMe') }}</span>
                </label>

                <button type="submit">{{ __('mfw-auth.loginBtn') }}</button>
            </form>
        </main>
    </body>
</html>

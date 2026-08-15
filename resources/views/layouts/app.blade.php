<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Рецепты и КБЖУ')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900 antialiased">
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center gap-6">
            <a href="{{ route('recipes.index') }}" class="font-semibold text-gray-800 hover:text-gray-600">КБЖУ</a>
            <a href="{{ route('recipes.index') }}" class="text-gray-600 hover:text-gray-900">Рецепты</a>
            <a href="{{ route('products.index') }}" class="text-gray-600 hover:text-gray-900">Продукты</a>

            <div class="ml-auto flex items-center gap-4">
                @auth
                    <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">Выйти</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900">Войти</a>
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-sm text-white hover:bg-blue-700">
                        Регистрация
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 py-8">
        @if (session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>

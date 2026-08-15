@extends('layouts.app')

@section('title', 'Регистрация')

@section('content')
    <div class="max-w-md mx-auto bg-white rounded-lg shadow p-8 mt-10">
        <h1 class="text-2xl font-bold mb-6">Регистрация</h1>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Имя</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
                <input type="password" name="password" id="password" required
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Повторите пароль</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                Зарегистрироваться
            </button>
        </form>

        <p class="mt-6 text-sm text-gray-600">
            Уже есть аккаунт?
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Войти</a>
        </p>
    </div>
@endsection
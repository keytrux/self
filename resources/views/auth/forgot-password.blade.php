@extends('layouts.app')

@section('title', 'Восстановление пароля')

@section('content')
    <div class="max-w-md mx-auto bg-white rounded-lg shadow p-8 mt-10">
        <h1 class="text-2xl font-bold mb-2">Восстановление пароля</h1>
        <p class="text-sm text-gray-600 mb-6">Укажите email — мы отправим ссылку для сброса пароля.</p>

        @if (session('status'))
            <p class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                Отправить ссылку
            </button>
        </form>

        <p class="mt-6 text-sm text-gray-600">
            Вспомнили пароль?
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Войти</a>
        </p>
    </div>
@endsection
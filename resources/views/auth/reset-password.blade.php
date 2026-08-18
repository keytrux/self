@extends('layouts.app')

@section('title', 'Новый пароль')

@section('content')
    <div class="max-w-md mx-auto bg-white rounded-lg shadow p-8 mt-10">
        <h1 class="text-2xl font-bold mb-6">Новый пароль</h1>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Электронная почта</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Новый пароль</label>
                <input type="password" name="password" id="password" required minlength="8"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Подтвердите пароль</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                Сохранить пароль
            </button>
        </form>
    </div>
@endsection
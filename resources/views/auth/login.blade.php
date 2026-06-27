@extends('layouts.guest')

@section('title', 'Connexion administration')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 mb-1">Connexion</h1>
    <p class="text-sm text-slate-500 mb-6">Accès réservé aux administrateurs.</p>

    <form method="POST" action="{{ route('login') }}" class="space-y-4" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input
                type="email"
                name="email"
                id="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('email') border-rose-500 @enderror"
            >
            @error('email')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Mot de passe</label>
            <input
                type="password"
                name="password"
                id="password"
                required
                autocomplete="current-password"
                class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('password') border-rose-500 @enderror"
            >
            @error('password')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center">
            <input
                type="checkbox"
                name="remember"
                id="remember"
                value="1"
                {{ old('remember') ? 'checked' : '' }}
                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
            >
            <label for="remember" class="ml-2 text-sm text-slate-600">Se souvenir de moi</label>
        </div>

        <button
            type="submit"
            :disabled="submitting"
            class="w-full inline-flex items-center justify-center gap-2 bg-blue-600 text-white font-medium py-2.5 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition disabled:opacity-60"
        >
            <svg x-show="submitting" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span x-text="submitting ? 'Connexion...' : 'Se connecter'">Se connecter</span>
        </button>
    </form>
@endsection

@section('footer')
    Rate-limit : 6 tentatives par minute.
@endsection
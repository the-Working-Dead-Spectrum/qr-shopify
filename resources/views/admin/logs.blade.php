@extends('layouts.admin_new')

@section('title', 'Logs système')

@section('content')
    <div class="max-w-7xl mx-auto space-y-xl">
        <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
            <div class="flex justify-between items-center mb-lg">
                <h2 class="font-headline-md text-headline-md text-on-surface">Logs système</h2>
                
                <div class="flex gap-md">
                    <button class="bg-primary text-white px-md py-sm rounded-lg font-bold hover:bg-opacity-90 transition-all">
                        Rafraîchir
                    </button>
                    
                    <button class="bg-surface-container text-secondary px-md py-sm rounded-lg font-bold hover:bg-surface-container-high transition-all">
                        Exporter
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase text-secondary bg-surface-container">
                        <tr>
                            <th class="px-md py-sm">Date</th>
                            <th class="px-md py-sm">Utilisateur</th>
                            <th class="px-md py-sm">Action</th>
                            <th class="px-md py-sm">Détails</th>
                            <th class="px-md py-sm">IP</th>
                        </tr>
                    </thead>
                    
                    <tbody class="divide-y divide-outline-variant">
                        @forelse($logs as $log)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-md py-sm text-on-surface">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                <td class="px-md py-sm text-on-surface">{{ $log->user?->name ?? 'Système' }}</td>
                                <td class="px-md py-sm">
                                    <span class="inline-flex items-center gap-xs px-xs py-1 rounded-full text-xs font-bold 
                                        @if($log->level === 'error') bg-error-container text-on-error
                                        @elseif($log->level === 'warning') bg-amber-100 text-amber-800
                                        @else bg-primary-container text-primary @endif">
                                        <span class="material-symbols-outlined text-xs">{{ $log->level === 'error' ? 'error' : ($log->level === 'warning' ? 'warning' : 'info') }}</span>
                                        {{ ucfirst($log->level) }}
                                    </span>
                                </td>
                                <td class="px-md py-sm text-on-surface">{{ $log->message }}</td>
                                <td class="px-md py-sm text-secondary">{{ $log->ip_address }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-md py-lg text-center text-secondary">
                                    Aucun log trouvé
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-lg">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
@endsection
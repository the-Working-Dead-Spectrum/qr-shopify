@extends('layouts.admin_new')

@section('title', 'Logs système')

@section('content')
<div class="max-w-7xl mx-auto space-y-lg">
    <!-- Page Header & Hero Stats -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-md">
        <div>
            <h3 class="font-headline-lg text-headline-lg text-on-surface">Audit Trail & Sécurité</h3>
            <p class="text-body-lg text-on-surface-variant max-w-2xl">Surveillez l'intégrité de votre système en temps réel. Historique complet des transactions, accès et modifications administratives.</p>
        </div>
        <div class="flex gap-sm">
            <a href="{{ route('admin.reports.export') }}" class="flex items-center gap-xs px-md py-2 bg-white border border-outline-variant rounded-lg text-button-text font-button-text text-secondary hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[20px]">file_download</span>
                Exporter CSV
            </a>
            <button onclick="refreshLogs()" class="flex items-center gap-xs px-md py-2 bg-primary text-on-primary rounded-lg text-button-text font-button-text hover:opacity-90 transition-opacity">
                <span class="material-symbols-outlined text-[20px]" id="refresh-icon">refresh</span>
                Actualiser
            </button>
        </div>
    </div>
    
    <!-- Filters Bento -->
    <section class="grid grid-cols-1 md:grid-cols-4 gap-md">
        <!-- Event Type Filter -->
        <div class="md:col-span-1 bg-white p-md rounded-xl border border-outline-variant shadow-sm space-y-xs">
            <label class="text-label-md font-label-md text-secondary uppercase tracking-wider">Type d'Événement</label>
            <select id="event-type-filter" class="w-full bg-surface-container-low border-none rounded-lg text-body-md focus:ring-2 focus:ring-primary" onchange="filterLogs()">
                <option value="">Tous les événements</option>
                <option value="scan">Scans de Commandes</option>
                <option value="partner">Gestion Partenaires</option>
                <option value="settings">Paramètres Système</option>
                <option value="login">Logins & Sécurité</option>
            </select>
        </div>
        
        <!-- User Filter -->
        <div class="md:col-span-1 bg-white p-md rounded-xl border border-outline-variant shadow-sm space-y-xs">
            <label class="text-label-md font-label-md text-secondary uppercase tracking-wider">Utilisateur</label>
            <select id="user-filter" class="w-full bg-surface-container-low border-none rounded-lg text-body-md focus:ring-2 focus:ring-primary" onchange="filterLogs()">
                <option value="">Tous les utilisateurs</option>
                @foreach($logs->unique('user_id') as $log)
                    @if($log->user)
                        <option value="{{ $log->user->id }}">{{ $log->user->name }}</option>
                    @endif
                @endforeach
                <option value="system">Système</option>
            </select>
        </div>
        
        <!-- Date Filter -->
        <div class="md:col-span-1 bg-white p-md rounded-xl border border-outline-variant shadow-sm space-y-xs">
            <label class="text-label-md font-label-md text-secondary uppercase tracking-wider">Période</label>
            <input type="date" id="date-filter" class="w-full bg-surface-container-low border-none rounded-lg text-body-md focus:ring-2 focus:ring-primary" onchange="filterLogs()" value="{{ now()->format('Y-m-d') }}">
        </div>
        
        <!-- Critical Alerts -->
        <div class="md:col-span-1 bg-primary-container p-md rounded-xl border border-primary text-on-primary-container flex items-center justify-between">
            <div>
                <p class="text-label-md font-label-md opacity-80">Alertes Critiques (24h)</p>
                <h4 class="text-headline-md font-bold">{{ $logs->where('level', 'error')->count() }}</h4>
            </div>
            <span class="material-symbols-outlined text-4xl opacity-50">warning</span>
        </div>
    </section>
    
    <!-- Audit Log Table -->
    <div class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse" id="logs-table">
                <thead class="bg-surface-container-low border-b border-outline-variant">
                    <tr>
                        <th class="px-lg py-md text-label-md font-label-md text-secondary uppercase">Horodatage</th>
                        <th class="px-lg py-md text-label-md font-label-md text-secondary uppercase">Événement</th>
                        <th class="px-lg py-md text-label-md font-label-md text-secondary uppercase">Utilisateur</th>
                        <th class="px-lg py-md text-label-md font-label-md text-secondary uppercase">Détails / Cible</th>
                        <th class="px-lg py-md text-label-md font-label-md text-secondary uppercase text-right">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-container">
                    @forelse($logs as $log)
                        <tr class="hover:bg-surface-container-lowest transition-colors log-row" 
                            data-event-type="{{ strtolower($log->action ?? 'system') }}"
                            data-user-id="{{ $log->user_id }}"
                            data-date="{{ $log->created_at->format('Y-m-d') }}">
                            <td class="px-lg py-md">
                                <div class="text-body-md font-bold">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                <div class="text-label-md text-secondary opacity-70">IP: {{ $log->ip_address }}</div>
                            </td>
                            <td class="px-lg py-md">
                                <div class="flex items-center gap-xs">
                                    @php
                                        $icon = 'info';
                                        $color = 'text-secondary';
                                        switch(strtolower($log->action ?? '')) {
                                            case 'scan':
                                                $icon = 'qr_code_scanner';
                                                $color = 'text-primary';
                                                break;
                                            case 'partner':
                                                $icon = 'handshake';
                                                $color = 'text-tertiary';
                                                break;
                                            case 'settings':
                                                $icon = 'settings';
                                                $color = 'text-secondary';
                                                break;
                                            case 'login':
                                                $icon = $log->level === 'error' ? 'lock_reset' : 'login';
                                                $color = $log->level === 'error' ? 'text-error' : 'text-secondary';
                                                break;
                                            default:
                                                $icon = 'info';
                                                $color = 'text-secondary';
                                        }
                                    @endphp
                                    <span class="material-symbols-outlined {{ $color }} text-[20px]">{{ $icon }}</span>
                                    <span class="text-body-md font-medium">{{ ucfirst($log->action ?? 'Système') }}</span>
                                </div>
                            </td>
                            <td class="px-lg py-md">
                                <div class="flex items-center gap-xs">
                                    @if($log->user)
                                        <div class="w-6 h-6 rounded-full bg-primary-container text-on-primary-container text-[10px] flex items-center justify-center font-bold">
                                            {{ strtoupper(substr($log->user->name, 0, 2)) }}
                                        </div>
                                        <span class="text-body-md">{{ $log->user->name }}</span>
                                    @else
                                        <span class="material-symbols-outlined text-error text-[16px]">no_accounts</span>
                                        <span class="text-body-md font-bold text-error">Système</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-lg py-md">
                                @if($log->level === 'error')
                                    <span class="text-body-md text-error italic">{{ $log->message }}</span>
                                @else
                                    <span class="text-body-md text-on-surface-variant italic">{{ $log->message }}</span>
                                @endif
                            </td>
                            <td class="px-lg py-md text-right">
                                @php
                                    $statusColor = 'bg-primary-container text-primary';
                                    $statusText = ucfirst($log->level ?? 'info');
                                    
                                    switch($log->level) {
                                        case 'error':
                                            $statusColor = 'bg-error-container text-on-error-container';
                                            $statusText = $log->message === 'Brute force detected' ? 'BLOQUÉ' : 'ERREUR';
                                            break;
                                        case 'warning':
                                            $statusColor = 'bg-amber-100 text-amber-800';
                                            break;
                                        case 'update':
                                            $statusColor = 'bg-secondary-container text-on-secondary-fixed-variant';
                                            $statusText = 'MAJ';
                                            break;
                                        case 'success':
                                            $statusColor = 'bg-[#0080601a] text-[#008060]';
                                            $statusText = 'SUCCÈS';
                                            break;
                                    }
                                @endphp
                                <span class="px-xs py-1 rounded {{ $statusColor }} text-label-md font-bold">{{ $statusText }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-lg py-lg text-center text-secondary">
                                Aucun log trouvé
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-lg py-md bg-surface-container-low flex justify-between items-center border-t border-outline-variant">
            <span class="text-body-md text-secondary">Affichage de {{ $logs->firstItem() }} à {{ $logs->lastItem() }} sur {{ $logs->total() }} logs</span>
            <div class="flex gap-xs">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e0e3e5; border-radius: 10px; }
    
    /* Style pour la pagination */
    .pagination-container {
        display: flex;
        gap: 4px;
    }
    
    .pagination-container span {
        padding: 8px 12px;
        border-radius: 8px;
    }
    
    .pagination-container a {
        padding: 8px 12px;
        border-radius: 8px;
        transition: all 0.2s;
    }
    
    .pagination-container a:hover {
        background-color: #f2f4f6;
    }
</style>

<script>
    // Micro-interaction for table rows
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('click', () => {
            row.classList.add('opacity-80');
            setTimeout(() => row.classList.remove('opacity-80'), 150);
        });
    });
    
    // Refresh logs function
    function refreshLogs() {
        const refreshIcon = document.getElementById('refresh-icon');
        refreshIcon.style.transform = 'rotate(360deg)';
        refreshIcon.style.transition = 'transform 0.5s ease-in-out';
        
        setTimeout(() => {
            refreshIcon.style.transform = 'rotate(0deg)';
        }, 500);
        
        // In a real application, you would fetch new logs via AJAX here
        // For now, we just simulate the refresh
        console.log('Logs refreshed');
    }
    
    // Filter logs function
    function filterLogs() {
        const eventType = document.getElementById('event-type-filter').value;
        const userId = document.getElementById('user-filter').value;
        const date = document.getElementById('date-filter').value;
        
        const rows = document.querySelectorAll('#logs-table tbody tr.log-row');
        
        rows.forEach(row => {
            const rowEventType = row.getAttribute('data-event-type');
            const rowUserId = row.getAttribute('data-user-id');
            const rowDate = row.getAttribute('data-date');
            
            let shouldShow = true;
            
            if (eventType && rowEventType !== eventType) {
                shouldShow = false;
            }
            
            if (userId) {
                if (userId === 'system' && rowUserId) {
                    shouldShow = false;
                } else if (userId !== 'system' && rowUserId !== userId) {
                    shouldShow = false;
                }
            }
            
            if (date && rowDate !== date) {
                shouldShow = false;
            }
            
            row.style.display = shouldShow ? '' : 'none';
        });
    }
    
    // Simple real-time update simulation (visual only)
    setInterval(() => {
        const refreshBtn = document.querySelector('button .material-symbols-outlined:contains("refresh")');
        if(refreshBtn) {
            refreshBtn.style.transform = 'rotate(360deg)';
            refreshBtn.style.transition = 'transform 0.5s ease-in-out';
            setTimeout(() => refreshBtn.style.transform = 'rotate(0deg)', 500);
        }
    }, 30000);
</script>
@endsection
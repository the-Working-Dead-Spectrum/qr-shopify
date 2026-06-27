<div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm hover:shadow-md transition-shadow">
    <div class="flex justify-between items-start mb-sm">
        <p class="font-label-md text-label-md text-secondary uppercase tracking-wider">{{ $title }}</p>
        <span class="material-symbols-outlined text-primary" data-icon="{{ $icon }}">{{ $icon }}</span>
    </div>
    
    <h2 class="font-headline-lg text-headline-lg text-on-surface" @if(isset($dataKpi)) data-kpi="{{ $dataKpi }}" @endif>{{ $value }}</h2>
    
    <div class="mt-md flex items-center gap-xs text-{{ $trendColor }} font-bold text-xs">
        <span class="material-symbols-outlined text-sm" data-icon="{{ $trendIcon }}">{{ $trendIcon }}</span>
        <span>{{ $trendText }}</span>
    </div>
</div>
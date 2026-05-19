<x-filament-widgets::widget>
    <x-filament::section>

        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-semibold text-white">Active Maintenance</h2>
                <p class="text-xs text-gray-400 mt-0.5">Maintenance yang sedang berjalan</p>
            </div>
            <a href="{{ $this->getViewAllUrl() }}"
                class="text-xs px-2.5 py-1.5 rounded-md bg-gray-500/20 text-gray-400 hover:bg-gray-500/30 transition-colors flex items-center gap-1">
                Lihat Semua
                <x-heroicon-o-arrow-right class="w-3.5 h-3.5" />
            </a>
        </div>

        {{-- Content --}}
        @php $items = $this->getUpcomingMaintenances(); @endphp

        @if ($items->isEmpty())
            <div class="flex flex-col items-center justify-center py-10">
                <x-heroicon-o-check-circle class="w-10 h-10 mb-2 opacity-30 text-green-500" />
                <p class="text-sm font-medium text-gray-400">Tidak ada maintenance berjalan</p>
                <p class="text-xs text-gray-500 mt-1">Semua sistem dalam kondisi normal</p>
            </div>
        @else
            <div class="space-y-2.5">
                @foreach ($items as $maintenance)
                    @php
                        $end = $maintenance->scheduled_at->addMinutes($maintenance->duration_minutes);
                    @endphp

                    <div class="p-3 rounded-lg bg-white/5 hover:bg-white/8 transition-colors flex items-start gap-3">

                        {{-- Pulse indicator --}}
                        <div class="flex-shrink-0 mt-1.5">
                            <div class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></div>
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white truncate">
                                {{ $maintenance->title }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $maintenance->scheduled_at->format('d M Y, H:i') }}
                                –
                                {{ $end->format('H:i') }}
                            </p>

                            {{-- OPD chips --}}
                            @if ($maintenance->opds->isNotEmpty())
                                <div class="flex flex-wrap gap-1 mt-1.5">
                                    @foreach ($maintenance->opds->take(3) as $opd)
                                        <span
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-white/10 text-gray-300">
                                            {{ $opd->nama_opd }}
                                        </span>
                                    @endforeach
                                    @if ($maintenance->opds->count() > 3)
                                        <span
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-white/10 text-gray-400">
                                            +{{ $maintenance->opds->count() - 3 }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Duration --}}
                        <div class="flex-shrink-0">
                            <span
                                class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 uppercase whitespace-nowrap">
                                {{ $maintenance->formatted_duration }}
                            </span>
                        </div>

                    </div>
                @endforeach
            </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>

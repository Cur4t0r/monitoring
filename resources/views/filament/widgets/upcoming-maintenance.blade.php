<x-filament-widgets::widget>
    <x-filament::section>

        {{-- Header --}}
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-base font-semibold text-white">Maintenance Overview</h2>
                <p class="text-xs text-gray-400 mt-0.5">Status</p>
            </div>
            <a href="{{ $this->getViewAllUrl() }}"
                class="text-xs px-2.5 py-1.5 rounded-md bg-white/10 text-gray-300 hover:bg-white/15 transition-colors flex items-center gap-1">
                Lihat Semua
                <x-heroicon-o-arrow-right class="w-3.5 h-3.5" />
            </a>
        </div>

        @php
            $inProgress = $this->getInProgressMaintenances();
            $planned = $this->getPlannedMaintenances();
        @endphp

        {{-- SECTION: IN PROGRESS --}}
        <div class="mb-5">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse flex-shrink-0"></span>
                <p class="text-xs font-semibold uppercase tracking-widest text-amber-400">
                    Sedang Berjalan
                </p>
                <span class="ml-auto text-xs font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full">
                    {{ $inProgress->count() }}
                </span>
            </div>

            @if ($inProgress->isEmpty())
                <div class="flex items-center gap-2 py-3 px-3 rounded-lg bg-white/5 text-gray-500">
                    <x-heroicon-o-check-circle class="w-4 h-4 text-green-500 opacity-50" />
                    <p class="text-xs">Tidak ada maintenance yang sedang berjalan</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($inProgress as $item)
                        @php $end = $item->scheduled_at->addMinutes($item->duration_minutes); @endphp
                        <div class="flex items-start gap-3 p-3 rounded-lg bg-amber-500/10 border border-amber-500/20">

                            {{-- Tanggal --}}
                            <div class="flex-shrink-0 text-center w-9">
                                <p class="text-[10px] font-bold uppercase text-amber-400 leading-none">
                                    {{ $item->scheduled_at->translatedFormat('M') }}
                                </p>
                                <p class="text-lg font-bold text-white leading-tight">
                                    {{ $item->scheduled_at->format('d') }}
                                </p>
                            </div>

                            {{-- Garis --}}
                            <div class="w-0.5 self-stretch rounded-full bg-amber-500 flex-shrink-0"></div>

                            {{-- Detail --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-white truncate">{{ $item->title }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $item->scheduled_at->format('H:i') }} – {{ $end->format('H:i') }}
                                    &middot; {{ $item->formatted_duration }}
                                </p>
                                @if ($item->opds->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach ($item->opds->take(3) as $opd)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] bg-white/10 text-gray-300">
                                                {{ $opd->nama_opd }}
                                            </span>
                                        @endforeach
                                        @if ($item->opds->count() > 3)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] bg-white/10 text-gray-400">
                                                +{{ $item->opds->count() - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Badge --}}
                            <span
                                class="flex-shrink-0 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 uppercase">
                                Berjalan
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Divider --}}
        <div class="border-t border-white/10 mb-5"></div>

        {{-- SECTION: PLANNED --}}
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                <p class="text-xs font-semibold uppercase tracking-widest text-blue-400">
                    Direncanakan
                </p>
                <span class="ml-auto text-xs font-bold text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded-full">
                    {{ $planned->count() }}
                </span>
            </div>

            @if ($planned->isEmpty())
                <div class="flex items-center gap-2 py-3 px-3 rounded-lg bg-white/5 text-gray-500">
                    <x-heroicon-o-calendar-days class="w-4 h-4 opacity-40" />
                    <p class="text-xs">Tidak ada maintenance yang direncanakan</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($planned as $item)
                        @php $end = $item->scheduled_at->addMinutes($item->duration_minutes); @endphp
                        <div class="flex items-start gap-3 p-3 rounded-lg bg-blue-500/10 border border-blue-500/20">

                            {{-- Tanggal --}}
                            <div class="flex-shrink-0 text-center w-9">
                                <p class="text-[10px] font-bold uppercase text-blue-400 leading-none">
                                    {{ $item->scheduled_at->translatedFormat('M') }}
                                </p>
                                <p class="text-lg font-bold text-white leading-tight">
                                    {{ $item->scheduled_at->format('d') }}
                                </p>
                            </div>

                            {{-- Garis --}}
                            <div class="w-0.5 self-stretch rounded-full bg-blue-500 flex-shrink-0"></div>

                            {{-- Detail --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-white truncate">{{ $item->title }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $item->scheduled_at->format('H:i') }} – {{ $end->format('H:i') }}
                                    &middot; {{ $item->formatted_duration }}
                                </p>
                                @if ($item->opds->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach ($item->opds->take(3) as $opd)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] bg-white/10 text-gray-300">
                                                {{ $opd->nama_opd }}
                                            </span>
                                        @endforeach
                                        @if ($item->opds->count() > 3)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] bg-white/10 text-gray-400">
                                                +{{ $item->opds->count() - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Badge --}}
                            <span
                                class="flex-shrink-0 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-400 uppercase">
                                Direncanakan
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </x-filament::section>
</x-filament-widgets::widget>

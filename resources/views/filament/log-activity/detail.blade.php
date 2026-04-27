<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">
            {{ $opd->nama_opd }}
        </h2>
        <p class="text-sm text-gray-400">
            Detail pemakaian bandwidth berdasarkan data log dummy.
        </p>
    </div>

    {{-- Inbound --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-5 bg-gray-900 border border-gray-700 rounded-xl shadow-sm">
            <p class="text-sm text-gray-400">Max In</p>
            <p class="text-2xl font-bold text-blue-400">
                {{ number_format($stats['max_in'] / 1_000_000, 2) }} Mbps
            </p>
        </div>

        <div class="p-5 bg-gray-900 border border-gray-700 rounded-xl shadow-sm">
            <p class="text-sm text-gray-400">Average In</p>
            <p class="text-2xl font-bold text-amber-400">
                {{ number_format($stats['avg_in'] / 1_000_000, 2) }} Mbps
            </p>
        </div>

        <div class="p-5 bg-gray-900 border border-gray-700 rounded-xl shadow-sm">
            <p class="text-sm text-gray-400">Current In</p>
            <p class="text-2xl font-bold text-green-400">
                {{ number_format($stats['current_in'] / 1_000_000, 2) }} Mbps
            </p>
        </div>
    </div>

    {{-- Outbound --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-5 bg-gray-900 border border-gray-700 rounded-xl shadow-sm">
            <p class="text-sm text-gray-400">Max Out</p>
            <p class="text-2xl font-bold text-blue-400">
                {{ number_format($stats['max_out'] / 1_000_000, 2) }} Mbps
            </p>
        </div>

        <div class="p-5 bg-gray-900 border border-gray-700 rounded-xl shadow-sm">
            <p class="text-sm text-gray-400">Average Out</p>
            <p class="text-2xl font-bold text-amber-400">
                {{ number_format($stats['avg_out'] / 1_000_000, 2) }} Mbps
            </p>
        </div>

        <div class="p-5 bg-gray-900 border border-gray-700 rounded-xl shadow-sm">
            <p class="text-sm text-gray-400">Current Out</p>
            <p class="text-2xl font-bold text-green-400">
                {{ number_format($stats['current_out'] / 1_000_000, 2) }} Mbps
            </p>
        </div>
    </div>

    <div class="p-5 bg-gray-900 border border-gray-700 rounded-xl">
        <h3 class="text-lg font-semibold text-white mb-3">Data Log Dipilih</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-gray-400">Waktu</p>
                <p class="font-semibold text-white">
                    {{ $record->timestamp?->format('d M Y H:i:s') }}
                </p>
            </div>

            <div>
                <p class="text-gray-400">Inbound</p>
                <p class="font-semibold text-white">
                    {{ $record->in_mbps }}
                </p>
            </div>

            <div>
                <p class="text-gray-400">Outbound</p>
                <p class="font-semibold text-white">
                    {{ $record->out_mbps }}
                </p>
            </div>
        </div>
    </div>
</div>

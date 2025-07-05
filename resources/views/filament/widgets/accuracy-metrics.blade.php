<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            <!-- Header dengan Status Evaluasi -->
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Metrics Akurasi Real-Time
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Evaluasi performa algoritma berdasarkan {{ $training_data_count }} data training
                    </p>
                </div>
                
                <div class="flex items-center gap-3">
                    <!-- Status Badge -->
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                        @if($evaluation_status['color'] === 'success') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                        @elseif($evaluation_status['color'] === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                        @else bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                        @endif">
                        {{ $evaluation_status['message'] }}
                    </span>
                    
                    <!-- Tombol Evaluasi Ulang -->
                    <x-filament::button
                        wire:click="evaluateAllAlgorithms"
                        size="sm"
                        color="primary"
                        icon="heroicon-o-arrow-path"
                    >
                        Evaluasi Ulang
                    </x-filament::button>
                </div>
            </div>

            <!-- Best Algorithm Highlight -->
            @if($best_algorithm)
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                        <x-filament::icon icon="heroicon-o-trophy" class="w-5 h-5 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <h4 class="font-semibold text-green-900 dark:text-green-100">
                            Algoritma Terbaik: {{ $best_algorithm['name'] }}
                        </h4>
                        <p class="text-sm text-green-700 dark:text-green-300">
                            Akurasi: {{ number_format($best_algorithm['accuracy'], 1) }}%
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Grid Metrics per Algoritma -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($metrics as $algorithm => $data)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <!-- Header Algoritma -->
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ $data['name'] }}</h4>
                        <div class="flex items-center gap-2">
                            <!-- Trend Indicator -->
                            @if($data['trend']['direction'] === 'up')
                                <span class="inline-flex items-center text-green-600 dark:text-green-400">
                                    <x-filament::icon icon="heroicon-o-arrow-trending-up" class="w-4 h-4 mr-1" />
                                    +{{ $data['trend']['change'] }}%
                                </span>
                            @elseif($data['trend']['direction'] === 'down')
                                <span class="inline-flex items-center text-red-600 dark:text-red-400">
                                    <x-filament::icon icon="heroicon-o-arrow-trending-down" class="w-4 h-4 mr-1" />
                                    {{ $data['trend']['change'] }}%
                                </span>
                            @elseif($data['trend']['direction'] === 'stable')
                                <span class="inline-flex items-center text-gray-600 dark:text-gray-400">
                                    <x-filament::icon icon="heroicon-o-minus" class="w-4 h-4 mr-1" />
                                    Stabil
                                </span>
                            @else
                                <span class="inline-flex items-center text-blue-600 dark:text-blue-400">
                                    <x-filament::icon icon="heroicon-o-sparkles" class="w-4 h-4 mr-1" />
                                    Baru
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Akurasi Utama -->
                    <div class="text-center mb-6">
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                            {{ number_format($data['accuracy'], 1) }}%
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Akurasi Keseluruhan
                        </div>
                    </div>

                    <!-- Detailed Metrics per Kelas -->
                    @if($data['detailed_metrics'])
                    <div class="space-y-3">
                        <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Metrics per Kelas:</h5>
                        @foreach($data['detailed_metrics'] as $class => $metrics)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-white capitalize">
                                    {{ str_replace('_', ' ', $class) }}
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div class="text-center">
                                    <div class="font-semibold text-blue-600 dark:text-blue-400">
                                        {{ $metrics['precision'] }}%
                                    </div>
                                    <div class="text-gray-500 dark:text-gray-400">Precision</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-semibold text-green-600 dark:text-green-400">
                                        {{ $metrics['recall'] }}%
                                    </div>
                                    <div class="text-gray-500 dark:text-gray-400">Recall</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-semibold text-purple-600 dark:text-purple-400">
                                        {{ $metrics['f1_score'] }}%
                                    </div>
                                    <div class="text-gray-500 dark:text-gray-400">F1-Score</div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <!-- Info Tambahan -->
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>Data: {{ $data['data_count'] }} samples</span>
                            <span>Update: {{ $data['last_updated'] }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Confusion Matrix Section -->
            @if(collect($metrics)->where('confusion_matrix', '!=', null)->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-4">Confusion Matrix</h4>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($metrics as $algorithm => $data)
                        @if($data['confusion_matrix'])
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ $data['name'] }}</h5>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-xs">
                                    <thead>
                                        <tr>
                                            <th class="px-2 py-1 text-left text-gray-500 dark:text-gray-400">Aktual \ Prediksi</th>
                                            @foreach(['mentah', 'setengah_matang', 'matang', 'busuk'] as $class)
                                            <th class="px-2 py-1 text-center text-gray-500 dark:text-gray-400 capitalize">
                                                {{ str_replace('_', ' ', $class) }}
                                            </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(['mentah', 'setengah_matang', 'matang', 'busuk'] as $actualClass)
                                        <tr>
                                            <td class="px-2 py-1 font-medium text-gray-700 dark:text-gray-300 capitalize">
                                                {{ str_replace('_', ' ', $actualClass) }}
                                            </td>
                                            @foreach(['mentah', 'setengah_matang', 'matang', 'busuk'] as $predictedClass)
                                            <td class="px-2 py-1 text-center
                                                @if($actualClass === $predictedClass) bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400
                                                @else bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400
                                                @endif">
                                                {{ $data['confusion_matrix'][$actualClass][$predictedClass] ?? 0 }}
                                            </td>
                                            @endforeach
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Accuracy History Chart -->
            @if(collect($accuracy_history)->flatten(1)->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-4">Riwayat Akurasi</h4>
                <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                    <div class="text-center">
                        <x-filament::icon icon="heroicon-o-chart-bar" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                        <p>Chart akan ditampilkan di sini</p>
                        <p class="text-xs mt-1">Implementasi chart dengan Chart.js atau ApexCharts</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Footer Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Evaluasi terakhir: {{ $metrics ? collect($metrics)->max('last_updated') : 'Belum pernah' }}
                </div>
                
                <div class="flex items-center gap-2">
                    <x-filament::button
                        wire:click="exportMetrics"
                        size="sm"
                        color="gray"
                        icon="heroicon-o-arrow-down-tray"
                    >
                        Export Metrics
                    </x-filament::button>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
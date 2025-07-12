@php
    $viewData = $this->getCachedData();
    $evaluationSummary = $viewData['evaluation_summary'];
    $algorithmPerformance = $viewData['algorithm_performance'];
    $distributionData = $viewData['distribution_data'];
    $confusionMatrices = $viewData['confusion_matrices'];
    $rgbAnalysis = $viewData['rgb_analysis'];
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Summary -->
        <div class="grid grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                        {{ $evaluationSummary['total_training_data'] ?? 0 }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Data Training</div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-success-600 dark:text-success-400">
                        {{ $evaluationSummary['total_classifications'] ?? 0 }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Klasifikasi</div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-warning-600 dark:text-warning-400">
                        {{ $evaluationSummary['total_data_points'] ?? 0 }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Data</div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-center">
                    @php
                        $knnPerformance = $algorithmPerformance['knn'] ?? null;
                    @endphp
                    @if($knnPerformance)
                        <div class="text-2xl font-bold text-info-600 dark:text-info-400">
                            {{ number_format($knnPerformance['current_accuracy'], 1) }}%
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Akurasi KNN</div>
                    @else
                        <div class="text-sm text-gray-500">KNN belum dievaluasi</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Distribusi Data -->
        <div class="grid grid-cols-2 gap-6">
            <x-filament::section>
                <x-slot name="heading">
                    Distribusi Data Training & Klasifikasi
                </x-slot>
                <div class="h-80">
                    <canvas id="combinedDistributionChart"></canvas>
                </div>
            </x-filament::section>
            
            <x-filament::section>
                <x-slot name="heading">
                    Perbandingan Performa Algoritma
                </x-slot>
                <div class="h-80">
                    <canvas id="algorithmComparisonChart"></canvas>
                </div>
            </x-filament::section>
        </div>

        <!-- Analisis RGB & Tren Historis -->
        <div class="grid grid-cols-2 gap-6">
            <x-filament::section>
                <x-slot name="heading">
                    Analisis Nilai RGB per Kelas Kematangan
                </x-slot>
                <div class="h-80">
                    <canvas id="rgbAnalysisChart"></canvas>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    Tren Historis Performa Algoritma
                </x-slot>
                <div class="h-80">
                    <canvas id="historicalTrendChart"></canvas>
                </div>
            </x-filament::section>
        </div>

        <!-- Confusion Matrices -->
        <x-filament::section>
            <x-slot name="heading">
                Confusion Matrix per Algoritma (Berdasarkan Data)
            </x-slot>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($confusionMatrices as $algorithm => $data)
                    <div class="border rounded-lg p-4 dark:border-gray-700">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="text-lg font-semibold">{{ $data['name'] }}</h4>
                            @if($data['has_data'])
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full">
                                    Data Tersedia
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full">
                                    Belum Ada Data
                                </span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Akurasi: {{ number_format($data['accuracy'], 2) }}% | 
                            Total Prediksi: {{ $data['total_predictions'] ?? 0 }}
                        </div>
                        <div class="overflow-x-auto">
                            @if(isset($data['matrix']) && is_array($data['matrix']))
                                <table class="w-full text-xs border-collapse">
                                    <thead>
                                        <tr>
                                            <th class="border p-1 bg-gray-100 dark:bg-gray-800">Actual \ Predicted</th>
                                            @foreach(array_keys($data['matrix']) as $class)
                                                <th class="border p-1 bg-gray-100 dark:bg-gray-800">{{ ucfirst(str_replace('_', ' ', $class)) }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data['matrix'] as $actualClass => $predictions)
                                            <tr>
                                                <td class="border p-1 font-medium bg-gray-50 dark:bg-gray-900">{{ ucfirst(str_replace('_', ' ', $actualClass)) }}</td>
                                                @foreach($predictions as $predictedClass => $count)
                                                    <td class="border p-1 text-center {{ $actualClass === $predictedClass ? 'bg-green-100 dark:bg-green-900 font-bold' : ($count > 0 ? 'bg-red-50 dark:bg-red-900' : '') }}">
                                                        {{ $count }}
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if($data['has_data'])
                                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="inline-block w-3 h-3 bg-green-100 dark:bg-green-900 border mr-1"></span>Prediksi Benar
                                        <span class="inline-block w-3 h-3 bg-red-50 dark:bg-red-900 border mr-1 ml-3"></span>Prediksi Salah
                                    </div>
                                @else
                                    <div class="mt-2 text-xs text-center text-gray-500 dark:text-gray-400">
                                        Belum ada data training atau klasifikasi yang terverifikasi untuk algoritma ini.
                                        <br>Tambahkan data training atau lakukan klasifikasi untuk melihat confusion matrix.
                                    </div>
                                @endif
                            @else
                                <div class="text-center text-gray-500 py-8">
                                    <div class="text-lg mb-2">📊</div>
                                    <div>Confusion matrix belum tersedia</div>
                                    <div class="text-xs mt-1">Tambahkan data training untuk melihat matrix</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <!-- Detail Statistik RGB -->
        <x-filament::section>
            <x-slot name="heading">
                Statistik Detail Nilai RGB
            </x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="text-left p-2">Kelas</th>
                            <th class="text-center p-2">Jumlah</th>
                            <th class="text-center p-2">Red (Avg±Std)</th>
                            <th class="text-center p-2">Green (Avg±Std)</th>
                            <th class="text-center p-2">Blue (Avg±Std)</th>
                            <th class="text-center p-2">Range RGB</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rgbAnalysis as $class => $stats)
                            <tr class="border-b dark:border-gray-700">
                                <td class="p-2 font-medium">{{ ucfirst($class) }}</td>
                                <td class="text-center p-2">{{ $stats['count'] }}</td>
                                <td class="text-center p-2">
                                    {{ $stats['red']['avg'] }} ± {{ $stats['red']['std'] }}
                                </td>
                                <td class="text-center p-2">
                                    {{ $stats['green']['avg'] }} ± {{ $stats['green']['std'] }}
                                </td>
                                <td class="text-center p-2">
                                    {{ $stats['blue']['avg'] }} ± {{ $stats['blue']['std'] }}
                                </td>
                                <td class="text-center p-2">
                                    <span class="text-xs">
                                        R: {{ $stats['red']['min'] }}-{{ $stats['red']['max'] }}<br>
                                        G: {{ $stats['green']['min'] }}-{{ $stats['green']['max'] }}<br>
                                        B: {{ $stats['blue']['min'] }}-{{ $stats['blue']['max'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#e5e7eb' : '#374151';
            const gridColor = isDark ? '#374151' : '#e5e7eb';
            
            const chartDefaults = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: textColor
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            color: gridColor
                        }
                    },
                    y: {
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            color: gridColor
                        }
                    }
                }
            };

            // Data dari PHP
            const distributionData = @json($distributionData);
            const rgbAnalysisJS = @json($rgbAnalysis);
            const algorithmPerformanceJS = @json($algorithmPerformance);
            const confusionMatricesJS = @json($confusionMatrices);

            // Warna untuk kelas kematangan
            const maturityColors = {
                'matang': '#10b981',
                'setengah_matang': '#f59e0b', 
                'mentah': '#ef4444',
                'busuk': '#6b7280',
                'Matang': '#10b981',
                'Setengah Matang': '#f59e0b', 
                'Mentah': '#ef4444',
                'Busuk': '#6b7280'
            };

            // Combined Distribution Chart (Pie Chart)
            const allClasses = [...new Set([
                ...Object.keys(distributionData.training),
                ...Object.keys(distributionData.classification)
            ])];
            
            new Chart(document.getElementById('combinedDistributionChart'), {
                type: 'pie',
                data: {
                    labels: allClasses.map(key => key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())),
                    datasets: [{
                        data: allClasses.map(key => {
                            const training = distributionData.training[key] || 0;
                            const classification = distributionData.classification[key] || 
                                                 distributionData.classification[key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())] || 
                                                 distributionData.classification[key.charAt(0).toUpperCase() + key.slice(1)] || 0;
                            return training + classification;
                        }),
                        backgroundColor: [
                            '#10b981', // matang - green
                            '#f59e0b', // setengah_matang - yellow
                            '#ef4444', // mentah - red
                            '#6b7280'  // busuk - gray
                        ],
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                color: textColor
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: `Training: ${distributionData.total_training} | Klasifikasi: ${distributionData.total_classification}`,
                            color: textColor
                        }
                    }
                }
            });

            // RGB Analysis Chart
            const rgbLabels = Object.keys(rgbAnalysisJS);
            new Chart(document.getElementById('rgbAnalysisChart'), {
                type: 'bar',
                data: {
                    labels: rgbLabels.map(label => label.replace('_', ' ').toUpperCase()),
                    datasets: [
                        {
                            label: 'Red Average',
                            data: rgbLabels.map(label => rgbAnalysisJS[label].red.avg),
                            backgroundColor: 'rgba(239, 68, 68, 0.7)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Green Average',
                            data: rgbLabels.map(label => rgbAnalysisJS[label].green.avg),
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Blue Average',
                            data: rgbLabels.map(label => rgbAnalysisJS[label].blue.avg),
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    ...chartDefaults,
                    scales: {
                        ...chartDefaults.scales,
                        y: {
                            ...chartDefaults.scales.y,
                            beginAtZero: true,
                            max: 255
                        }
                    }
                }
            });

            // Algorithm Comparison Chart (Line Chart)
            const algorithms = Object.keys(algorithmPerformanceJS);
            new Chart(document.getElementById('algorithmComparisonChart'), {
                type: 'line',
                data: {
                    labels: algorithms.map(alg => algorithmPerformanceJS[alg].name),
                    datasets: [{
                        label: 'Akurasi Saat Ini (%)',
                        data: algorithms.map(alg => algorithmPerformanceJS[alg].current_accuracy),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
                        pointBorderColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
                        pointRadius: 8,
                        pointHoverRadius: 10,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    ...chartDefaults,
                    scales: {
                        ...chartDefaults.scales,
                        y: {
                            ...chartDefaults.scales.y,
                            beginAtZero: true,
                            max: 100
                        }
                    },
                    plugins: {
                        ...chartDefaults.plugins,
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Historical Trend Chart
            const hasHistoricalData = algorithms.some(alg => 
                algorithmPerformanceJS[alg].historical_data && 
                algorithmPerformanceJS[alg].historical_data.length > 0
            );
            
            if (hasHistoricalData) {
                new Chart(document.getElementById('historicalTrendChart'), {
                    type: 'line',
                    data: {
                        datasets: algorithms.filter(alg => 
                            algorithmPerformanceJS[alg].historical_data && 
                            algorithmPerformanceJS[alg].historical_data.length > 0
                        ).map((alg, index) => ({
                            label: algorithmPerformanceJS[alg].name,
                            data: algorithmPerformanceJS[alg].historical_data.map(item => ({
                                x: item.date,
                                y: item.accuracy
                            })),
                            borderColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'][index],
                            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'][index] + '20',
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }))
                    },
                    options: {
                        ...chartDefaults,
                        scales: {
                            ...chartDefaults.scales,
                            x: {
                                ...chartDefaults.scales.x,
                                type: 'category'
                            },
                            y: {
                                ...chartDefaults.scales.y,
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            } else {
                // Show message when no historical data
                const canvas = document.getElementById('historicalTrendChart');
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = textColor;
                ctx.font = '16px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('Belum ada data historis tersedia', canvas.width/2, canvas.height/2);
            }

            // Confusion matrices are now displayed as HTML tables above
        });
    </script>
    @endpush
</x-filament-panels::page>
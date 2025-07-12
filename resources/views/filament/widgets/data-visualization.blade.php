<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            📊 Visualisasi Data & Analisis Algoritma
        </x-slot>
        
        <x-slot name="description">
            Grafik distribusi data, confusion matrix, dan perbandingan performa algoritma AI
        </x-slot>
        
        <div class="space-y-6">
            <!-- Distribusi Data -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">📈 Distribusi Data Training</h3>
                    <canvas id="trainingDistributionChart" width="400" height="300"></canvas>
                    <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        Total Data: {{ $distribution_data['total_training'] }} sampel
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">🔍 Distribusi Klasifikasi</h3>
                    <canvas id="classificationDistributionChart" width="400" height="300"></canvas>
                    <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        Total Klasifikasi: {{ $distribution_data['total_classification'] }} hasil
                    </div>
                </div>
            </div>
            
            <!-- RGB Analysis -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">🎨 Analisis Nilai RGB per Kelas</h3>
                <canvas id="rgbAnalysisChart" width="800" height="400"></canvas>
                <div class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    @foreach($rgb_analysis as $class => $data)
                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                        <div class="font-semibold text-gray-900 dark:text-white">{{ ucfirst($class) }}</div>
                        <div class="text-gray-600 dark:text-gray-400">{{ $data['count'] }} sampel</div>
                        <div class="text-xs mt-1">
                            <div>R: {{ $data['red']['avg'] }} ({{ $data['red']['min'] }}-{{ $data['red']['max'] }})</div>
                            <div>G: {{ $data['green']['avg'] }} ({{ $data['green']['min'] }}-{{ $data['green']['max'] }})</div>
                            <div>B: {{ $data['blue']['avg'] }} ({{ $data['blue']['min'] }}-{{ $data['blue']['max'] }})</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Algorithm Performance -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">⚡ Perbandingan Performa Algoritma</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-md font-medium mb-2 text-gray-800 dark:text-gray-200">Akurasi Saat Ini</h4>
                        <canvas id="currentAccuracyChart" width="400" height="300"></canvas>
                    </div>
                    <div>
                        <h4 class="text-md font-medium mb-2 text-gray-800 dark:text-gray-200">Tren Akurasi Historis</h4>
                        <canvas id="historicalAccuracyChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Confusion Matrices -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">🎯 Confusion Matrix per Algoritma</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($confusion_matrices as $algorithm => $data)
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <h4 class="text-md font-medium mb-3 text-gray-800 dark:text-gray-200">
                            {{ $data['name'] }} ({{ number_format($data['accuracy'], 2) }}%)
                        </h4>
                        <canvas id="confusionMatrix{{ ucfirst($algorithm) }}" width="300" height="300"></canvas>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-filament::section>
    
    <!-- Chart.js Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Color schemes
            const maturityColors = {
                'matang': '#10B981',
                'setengah_matang': '#F59E0B', 
                'mentah': '#EF4444',
                'busuk': '#6B7280'
            };
            
            const algorithmColors = {
                'decision_tree': '#3B82F6',
                'knn': '#10B981',
                'random_forest': '#F59E0B',
                'ensemble': '#8B5CF6'
            };
            
            // Training Distribution Chart
            const trainingData = @json($distribution_data['training']);
            new Chart(document.getElementById('trainingDistributionChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(trainingData).map(key => key.replace('_', ' ').toUpperCase()),
                    datasets: [{
                        data: Object.values(trainingData),
                        backgroundColor: Object.keys(trainingData).map(key => maturityColors[key] || '#6B7280'),
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Classification Distribution Chart
            const classificationData = @json($distribution_data['classification']);
            new Chart(document.getElementById('classificationDistributionChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(classificationData).map(key => key.replace('_', ' ').toUpperCase()),
                    datasets: [{
                        data: Object.values(classificationData),
                        backgroundColor: Object.keys(classificationData).map(key => maturityColors[key] || '#6B7280'),
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // RGB Analysis Chart
            const rgbData = @json($rgb_analysis);
            const rgbDatasets = [];
            const rgbLabels = Object.keys(rgbData);
            
            ['red', 'green', 'blue'].forEach((color, index) => {
                rgbDatasets.push({
                    label: color.toUpperCase() + ' Average',
                    data: rgbLabels.map(label => rgbData[label][color].avg),
                    backgroundColor: color === 'red' ? '#EF4444' : color === 'green' ? '#10B981' : '#3B82F6',
                    borderColor: color === 'red' ? '#DC2626' : color === 'green' ? '#059669' : '#2563EB',
                    borderWidth: 2
                });
            });
            
            new Chart(document.getElementById('rgbAnalysisChart'), {
                type: 'bar',
                data: {
                    labels: rgbLabels.map(label => label.replace('_', ' ').toUpperCase()),
                    datasets: rgbDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 255
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
            
            // Current Accuracy Chart
            const performanceData = @json($algorithm_performance);
            const algorithmNames = Object.keys(performanceData);
            const currentAccuracies = algorithmNames.map(alg => performanceData[alg].current_accuracy);
            
            new Chart(document.getElementById('currentAccuracyChart'), {
                type: 'bar',
                data: {
                    labels: algorithmNames.map(alg => performanceData[alg].name),
                    datasets: [{
                        label: 'Akurasi (%)',
                        data: currentAccuracies,
                        backgroundColor: algorithmNames.map(alg => algorithmColors[alg] || '#6B7280'),
                        borderColor: algorithmNames.map(alg => algorithmColors[alg] || '#6B7280'),
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            
            // Historical Accuracy Chart
            const historicalDatasets = algorithmNames.map(alg => {
                const historical = performanceData[alg].historical_data;
                return {
                    label: performanceData[alg].name,
                    data: historical.map(item => item.accuracy),
                    borderColor: algorithmColors[alg] || '#6B7280',
                    backgroundColor: (algorithmColors[alg] || '#6B7280') + '20',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.1
                };
            });
            
            // Get all unique dates for labels
            const allDates = [];
            algorithmNames.forEach(alg => {
                performanceData[alg].historical_data.forEach(item => {
                    if (!allDates.includes(item.date)) {
                        allDates.push(item.date);
                    }
                });
            });
            allDates.sort();
            
            new Chart(document.getElementById('historicalAccuracyChart'), {
                type: 'line',
                data: {
                    labels: allDates,
                    datasets: historicalDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
            
            // Confusion Matrices
            const confusionData = @json($confusion_matrices);
            Object.keys(confusionData).forEach(algorithm => {
                const matrix = confusionData[algorithm].matrix;
                if (matrix && typeof matrix === 'object') {
                    const canvasId = 'confusionMatrix' + algorithm.split('_').map(word => 
                        word.charAt(0).toUpperCase() + word.slice(1)
                    ).join('');
                    
                    // Convert matrix to heatmap data
                    const classes = Object.keys(matrix);
                    const heatmapData = [];
                    
                    classes.forEach((actualClass, i) => {
                        classes.forEach((predictedClass, j) => {
                            const value = matrix[actualClass] && matrix[actualClass][predictedClass] ? 
                                         matrix[actualClass][predictedClass] : 0;
                            heatmapData.push({
                                x: j,
                                y: i,
                                v: value
                            });
                        });
                    });
                    
                    new Chart(document.getElementById(canvasId), {
                        type: 'scatter',
                        data: {
                            datasets: [{
                                label: 'Confusion Matrix',
                                data: heatmapData,
                                backgroundColor: function(context) {
                                    const value = context.parsed.v;
                                    const maxValue = Math.max(...heatmapData.map(d => d.v));
                                    const intensity = value / maxValue;
                                    return `rgba(59, 130, 246, ${intensity})`;
                                },
                                pointRadius: function(context) {
                                    const value = context.parsed.v;
                                    const maxValue = Math.max(...heatmapData.map(d => d.v));
                                    return 5 + (value / maxValue) * 15;
                                }
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    type: 'linear',
                                    position: 'bottom',
                                    min: -0.5,
                                    max: classes.length - 0.5,
                                    ticks: {
                                        stepSize: 1,
                                        callback: function(value) {
                                            return classes[value] ? classes[value].replace('_', ' ').toUpperCase() : '';
                                        }
                                    }
                                },
                                y: {
                                    min: -0.5,
                                    max: classes.length - 0.5,
                                    ticks: {
                                        stepSize: 1,
                                        callback: function(value) {
                                            return classes[value] ? classes[value].replace('_', ' ').toUpperCase() : '';
                                        }
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        title: function() {
                                            return 'Confusion Matrix';
                                        },
                                        label: function(context) {
                                            const actual = classes[context.parsed.y];
                                            const predicted = classes[context.parsed.x];
                                            return `Actual: ${actual}, Predicted: ${predicted}, Count: ${context.parsed.v}`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            });
        });
    </script>
</x-filament-widgets::widget>
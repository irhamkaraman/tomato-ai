<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sistem Pakar Kematangan Tomat - AI Sensor Testing</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-shadow {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .input-focus:focus {
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
        .btn-hover:hover {
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="min-h-screen gradient-bg">
    <!-- Header Section -->
    <div class="bg-white/10 backdrop-blur-md border-b border-white/20">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-center space-x-4">
                <div class="bg-white/20 p-3 rounded-full">
                    <i class="fas fa-seedling text-white text-2xl"></i>
                </div>
                <div class="text-center">
                    <h1 class="text-3xl font-bold text-white mb-2">🍅 Sistem Pakar Kematangan Tomat</h1>
                    <p class="text-white/80 text-lg">Analisis AI dengan Teknologi Sensor RGB</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">

        <!-- Input Form Card -->
        <div class="bg-white/95 backdrop-blur-md rounded-2xl card-shadow p-8 mb-8 border border-white/20">
            <div class="flex items-center space-x-3 mb-6">
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-3 rounded-xl">
                    <i class="fas fa-microscope text-white text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Input Data Sensor RGB</h2>
                    <p class="text-gray-600">Masukkan nilai sensor untuk analisis kematangan tomat</p>
                </div>
            </div>

            <form id="sensorForm" class="space-y-6">
                <!-- RGB Values Section -->
                <div class="bg-gradient-to-r from-red-50 to-blue-50 p-6 rounded-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-palette text-purple-600 mr-2"></i>
                        Nilai RGB (Red, Green, Blue)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-red-700 mb-2">
                                <i class="fas fa-circle text-red-500 mr-1"></i>
                                Red Value (0-255)
                            </label>
                            <input type="number" name="red_value" min="0" max="255"
                                   class="input-focus w-full px-4 py-3 rounded-xl border-2 border-red-200 focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-all duration-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-green-700 mb-2">
                                <i class="fas fa-circle text-green-500 mr-1"></i>
                                Green Value (0-255)
                            </label>
                            <input type="number" name="green_value" min="0" max="255"
                                   class="input-focus w-full px-4 py-3 rounded-xl border-2 border-green-200 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-blue-700 mb-2">
                                <i class="fas fa-circle text-blue-500 mr-1"></i>
                                Blue Value (0-255)
                            </label>
                            <input type="number" name="blue_value" min="0" max="255"
                                   class="input-focus w-full px-4 py-3 rounded-xl border-2 border-blue-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-300">
                        </div>
                    </div>
                </div>

                <!-- Environmental Data Section -->
                <div class="bg-gradient-to-r from-yellow-50 to-green-50 p-6 rounded-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-thermometer-half text-orange-600 mr-2"></i>
                        Data Lingkungan
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-eye text-gray-500 mr-1"></i>
                                Clear Value
                            </label>
                            <input type="number" name="clear_value" min="0"
                                   class="input-focus w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all duration-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-orange-700 mb-2">
                                <i class="fas fa-thermometer-half text-orange-500 mr-1"></i>
                                Temperature (°C)
                            </label>
                            <input type="number" name="temperature" step="0.1"
                                   class="input-focus w-full px-4 py-3 rounded-xl border-2 border-orange-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-blue-700 mb-2">
                                <i class="fas fa-tint text-blue-500 mr-1"></i>
                                Humidity (%)
                            </label>
                            <input type="number" name="humidity" step="0.1"
                                   class="input-focus w-full px-4 py-3 rounded-xl border-2 border-blue-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-300">
                        </div>
                    </div>
                </div>

                <!-- Generate Data Buttons -->
                <div class="bg-gray-50 p-6 rounded-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-magic text-purple-600 mr-2"></i>
                        Generate Data Realistis
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
                        <button type="button" onclick="generateTomatoData('mentah')"
                                class="btn-hover bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-xl font-medium shadow-lg flex items-center justify-center space-x-2">
                            <i class="fas fa-seedling"></i>
                            <span>Mentah</span>
                        </button>
                        <button type="button" onclick="generateTomatoData('setengah_matang')"
                                class="btn-hover bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-3 rounded-xl font-medium shadow-lg flex items-center justify-center space-x-2">
                            <i class="fas fa-adjust"></i>
                            <span>Setengah</span>
                        </button>
                        <button type="button" onclick="generateTomatoData('matang')"
                                class="btn-hover bg-red-500 hover:bg-red-600 text-white px-4 py-3 rounded-xl font-medium shadow-lg flex items-center justify-center space-x-2">
                            <i class="fas fa-heart"></i>
                            <span>Matang</span>
                        </button>
                        <button type="button" onclick="generateTomatoData('busuk')"
                                class="btn-hover bg-gray-600 hover:bg-gray-700 text-white px-4 py-3 rounded-xl font-medium shadow-lg flex items-center justify-center space-x-2">
                            <i class="fas fa-skull"></i>
                            <span>Busuk</span>
                        </button>
                        <button type="button" onclick="generateRandomData()"
                                class="btn-hover bg-purple-500 hover:bg-purple-600 text-white px-4 py-3 rounded-xl font-medium shadow-lg flex items-center justify-center space-x-2">
                            <i class="fas fa-random"></i>
                            <span>Random</span>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-center mt-8">
                    <button type="submit" id="analyzeBtn"
                            class="btn-hover bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white px-12 py-4 rounded-2xl font-bold text-lg shadow-2xl flex items-center space-x-3 transition-all duration-300">
                        <i id="analyzeIcon" class="fas fa-brain text-xl"></i>
                        <span id="analyzeText">Analisis dengan AI</span>
                        <i id="analyzeArrow" class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Section -->
        <div id="results" class="bg-white/95 backdrop-blur-md rounded-2xl card-shadow p-8 border border-white/20 hidden">
            <div class="flex items-center space-x-3 mb-6">
                <div class="bg-gradient-to-r from-green-500 to-blue-600 p-3 rounded-xl">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Hasil Analisis AI</h2>
                    <p class="text-gray-600">Analisis kematangan tomat menggunakan algoritma ensemble learning</p>
                </div>
            </div>
            <div class="space-y-6">
                <div class="border-b pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Data Sensor</h3>
                    <div id="sensorData" class="mt-2 grid grid-cols-2 gap-4 text-sm"></div>
                </div>

                <div class="border-b pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Hasil Prediksi</h3>
                    <div id="predictions" class="mt-2 space-y-2"></div>
                </div>

                <div class="border-b pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Rekomendasi</h3>
                    <div id="recommendations" class="mt-2 space-y-2"></div>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900">Detail Analisis</h3>
                    <div id="analysis" class="mt-2 space-y-4"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Generate realistic tomato data based on ripeness level
        function generateTomatoData(type) {
            let rgbRanges = {
                'mentah': {
                    red: [80, 120],     // Hijau kekuningan
                    green: [140, 180],  // Dominan hijau
                    blue: [60, 100],    // Sedikit biru
                    temp: [20, 25],     // Suhu ruang
                    humidity: [60, 75]  // Kelembaban sedang
                },
                'setengah_matang': {
                    red: [150, 200],    // Mulai kemerahan
                    green: [120, 160],  // Hijau berkurang
                    blue: [70, 110],    // Biru sedikit
                    temp: [22, 27],     // Suhu hangat
                    humidity: [65, 80]  // Kelembaban tinggi
                },
                'matang': {
                    red: [200, 255],    // Merah dominan
                    green: [80, 120],   // Hijau minimal
                    blue: [60, 100],    // Biru minimal
                    temp: [25, 30],     // Suhu hangat
                    humidity: [70, 85]  // Kelembaban tinggi
                },
                'busuk': {
                    red: [100, 140],    // Merah gelap
                    green: [60, 100],   // Hijau gelap
                    blue: [40, 80],     // Biru gelap
                    temp: [28, 35],     // Suhu tinggi
                    humidity: [80, 95]  // Kelembaban sangat tinggi
                }
            };

            if (rgbRanges[type]) {
                const ranges = rgbRanges[type];

                // Generate RGB values within realistic ranges
                const red = Math.floor(Math.random() * (ranges.red[1] - ranges.red[0] + 1)) + ranges.red[0];
                const green = Math.floor(Math.random() * (ranges.green[1] - ranges.green[0] + 1)) + ranges.green[0];
                const blue = Math.floor(Math.random() * (ranges.blue[1] - ranges.blue[0] + 1)) + ranges.blue[0];

                // Calculate clear value based on RGB intensity
                const clear = Math.floor((red + green + blue) * 2.5 + Math.random() * 200);

                // Generate environmental data
                const temp = (Math.random() * (ranges.temp[1] - ranges.temp[0]) + ranges.temp[0]).toFixed(1);
                const humidity = (Math.random() * (ranges.humidity[1] - ranges.humidity[0]) + ranges.humidity[0]).toFixed(1);

                // Set values to form inputs
                document.querySelector('input[name="red_value"]').value = red;
                document.querySelector('input[name="green_value"]').value = green;
                document.querySelector('input[name="blue_value"]').value = blue;
                document.querySelector('input[name="clear_value"]').value = clear;
                document.querySelector('input[name="temperature"]').value = temp;
                document.querySelector('input[name="humidity"]').value = humidity;

                // Add visual feedback
                showToast(`Data ${type.replace('_', ' ')} berhasil di-generate!`, 'success');
            }
        }

        // Original random data generator (completely random)
        function generateRandomData() {
            // Generate random RGB values (0-255)
            document.querySelector('input[name="red_value"]').value = Math.floor(Math.random() * 256);
            document.querySelector('input[name="green_value"]').value = Math.floor(Math.random() * 256);
            document.querySelector('input[name="blue_value"]').value = Math.floor(Math.random() * 256);

            // Generate random clear value (300-700)
            document.querySelector('input[name="clear_value"]').value = Math.floor(Math.random() * 401) + 300;

            // Generate random temperature (20-35°C)
            document.querySelector('input[name="temperature"]').value = (Math.random() * 15 + 20).toFixed(1);

            // Generate random humidity (50-90%)
            document.querySelector('input[name="humidity"]').value = (Math.random() * 40 + 50).toFixed(1);

            showToast('Data random berhasil di-generate!', 'info');
        }

        // Toast notification function
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium transform transition-all duration-300 translate-x-full`;

            switch(type) {
                case 'success':
                    toast.classList.add('bg-green-500');
                    break;
                case 'error':
                    toast.classList.add('bg-red-500');
                    break;
                case 'info':
                default:
                    toast.classList.add('bg-blue-500');
                    break;
            }

            toast.innerHTML = `
                <div class="flex items-center space-x-2">
                    <i class="fas fa-check-circle"></i>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(toast);

            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);

            // Animate out and remove
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }

        document.getElementById('sensorForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            // Show loading state
            showLoadingState(true);

            const formData = new FormData(e.target);
            
            // Add device_id for database storage
            formData.append('device_id', 'WEB_SENSOR_TEST');

            try {
                // First, save to database using store endpoint
                const storeResponse = await fetch('/api/tomat-readings', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const storeResult = await storeResponse.json();
                if (storeResult.success) {
                    // Display results from database response
                    displayResults(storeResult.data, storeResult.recommendations, storeResult.analysis);
                    showToast('Data berhasil disimpan dan dianalisis!', 'success');
                } else {
                    showToast('Error: ' + (storeResult.message || 'Terjadi kesalahan'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan saat melakukan analisis', 'error');
            } finally {
                // Hide loading state
                showLoadingState(false);
            }
        });

        // Function to show/hide loading state
        function showLoadingState(isLoading) {
            const btn = document.getElementById('analyzeBtn');
            const icon = document.getElementById('analyzeIcon');
            const text = document.getElementById('analyzeText');
            const arrow = document.getElementById('analyzeArrow');

            if (isLoading) {
                btn.disabled = true;
                btn.classList.add('opacity-75', 'cursor-not-allowed');
                icon.className = 'fas fa-spinner fa-spin text-xl';
                text.textContent = 'Sedang Menganalisis...';
                arrow.style.display = 'none';
            } else {
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
                icon.className = 'fas fa-brain text-xl';
                text.textContent = 'Analisis dengan AI';
                arrow.style.display = 'inline';
            }
        }

        function displayResults(data, recommendations = null, analysis = null) {
            const resultsDiv = document.getElementById('results');
            const resultsContainer = resultsDiv.querySelector('.space-y-6');

            // Get ripeness color and icon
            const ripenessInfo = getRipenessInfo(data.maturity_level);
            
            // Use recommendations and analysis from parameters if provided
            const displayRecommendations = recommendations || data.recommendations;
            const displayAnalysis = analysis || data.ml_analysis;

            // Replace entire results content with new structure
            resultsContainer.innerHTML = `
                <!-- Main Prediction Card -->
                <div class="bg-gradient-to-r ${ripenessInfo.gradient} p-6 rounded-2xl text-white shadow-xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="bg-white/20 p-4 rounded-xl">
                                <i class="${ripenessInfo.icon} text-3xl"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold">${formatMaturityLevel(data.maturity_level)}</h3>
                                <p class="text-white/80">Tingkat Kematangan Tomat</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold">${(data.confidence_score * 100).toFixed(1)}%</div>
                            <div class="text-white/80">Confidence</div>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-white/20">
                        <div class="text-lg font-medium">Status: ${data.status}</div>
                    </div>
                </div>

                <!-- Analysis Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- RGB Analysis -->
                    <div class="bg-gradient-to-br from-red-50 to-blue-50 p-6 rounded-xl border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-palette text-purple-600 mr-2"></i>
                            Analisis RGB
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center text-red-700">
                                    <i class="fas fa-circle text-red-500 mr-2"></i>
                                    Red
                                </span>
                                <span class="font-bold text-red-600">${data.red_value || data.rgb_input?.red || 'N/A'}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="flex items-center text-green-700">
                                    <i class="fas fa-circle text-green-500 mr-2"></i>
                                    Green
                                </span>
                                <span class="font-bold text-green-600">${data.green_value || data.rgb_input?.green || 'N/A'}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="flex items-center text-blue-700">
                                    <i class="fas fa-circle text-blue-500 mr-2"></i>
                                    Blue
                                </span>
                                <span class="font-bold text-blue-600">${data.blue_value || data.rgb_input?.blue || 'N/A'}</span>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t">
                                <span class="flex items-center text-gray-700">
                                    <i class="fas fa-eye text-gray-500 mr-2"></i>
                                    Clear
                                </span>
                                <span class="font-bold text-gray-600">${data.clear_value || 'N/A'}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Environmental Data -->
                    <div class="bg-gradient-to-br from-yellow-50 to-green-50 p-6 rounded-xl border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-thermometer-half text-orange-600 mr-2"></i>
                            Data Lingkungan
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center text-orange-700">
                                    <i class="fas fa-thermometer-half text-orange-500 mr-2"></i>
                                    Temperature
                                </span>
                                <span class="font-bold text-orange-600">${data.temperature}°C</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="flex items-center text-blue-700">
                                    <i class="fas fa-tint text-blue-500 mr-2"></i>
                                    Humidity
                                </span>
                                <span class="font-bold text-blue-600">${data.humidity}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Machine Learning Analysis -->
                <div class="bg-gradient-to-br from-purple-50 to-indigo-50 p-6 rounded-xl border border-purple-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-brain text-purple-600 mr-2"></i>
                        Machine Learning Analysis
                    </h3>
                    <div class="space-y-4">
                        ${displayAnalysis ? `
                            <div class="bg-white p-4 rounded-lg border border-purple-100">
                                <div class="text-sm text-gray-600 mb-2">Analisis AI</div>
                                <div class="text-gray-800">${typeof displayAnalysis === 'object' ? JSON.stringify(displayAnalysis, null, 2) : displayAnalysis}</div>
                            </div>
                        ` : ''}

                        ${displayRecommendations ? `
                            <div class="bg-white p-4 rounded-lg border border-purple-100">
                                <div class="text-sm text-gray-600 mb-3">Rekomendasi Sistem</div>
                                <div class="grid grid-cols-1 gap-3">
                                    ${Object.entries(displayRecommendations).map(([key, value]) => `
                                        <div class="flex items-start space-x-2">
                                            <i class="fas fa-check-circle text-green-500 mt-1 text-sm"></i>
                                            <div>
                                                <span class="font-medium text-gray-800">${formatRecommendationKey(key)}:</span>
                                                <span class="text-gray-700 ml-1">${value}</span>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                        
                        <!-- Database Information -->
                        <div class="bg-white p-4 rounded-lg border border-purple-100">
                            <div class="text-sm text-gray-600 mb-3">Informasi Database</div>
                            <div class="grid grid-cols-1 gap-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">ID Reading:</span>
                                    <span class="font-medium">${data.id || 'N/A'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Device ID:</span>
                                    <span class="font-medium">${data.device_id || 'N/A'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Waktu Analisis:</span>
                                    <span class="font-medium">${data.created_at ? new Date(data.created_at).toLocaleString('id-ID') : 'N/A'}</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-4 rounded-lg border border-purple-100">
                            <div class="text-sm text-gray-600 mb-2">Algoritma yang Digunakan</div>
                            <div class="flex flex-wrap gap-2">
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">Decision Tree</span>
                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">K-Nearest Neighbors</span>
                                <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium">Random Forest</span>
                                <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-medium">Ensemble Voting</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            resultsDiv.classList.remove('hidden');
             resultsDiv.scrollIntoView({ behavior: 'smooth' });
        }

        // Helper function to get ripeness info
        function getRipenessInfo(maturityLevel) {
            const info = {
                'mentah': {
                    gradient: 'from-green-500 to-green-600',
                    icon: 'fas fa-seedling'
                },
                'setengah_matang': {
                    gradient: 'from-yellow-500 to-orange-500',
                    icon: 'fas fa-adjust'
                },
                'matang': {
                    gradient: 'from-red-500 to-red-600',
                    icon: 'fas fa-heart'
                },
                'busuk': {
                    gradient: 'from-gray-600 to-gray-700',
                    icon: 'fas fa-skull'
                }
            };

            return info[maturityLevel] || info['matang'];
        }

        function formatMaturityLevel(level) {
            const mapping = {
                'mentah': 'Mentah',
                'setengah_matang': 'Setengah Matang',
                'matang': 'Matang',
                'busuk': 'Busuk'
            };
            return mapping[level] || level;
        }

        function getMaturityClass(level) {
            const classes = {
                'mentah': 'text-green-600',
                'setengah_matang': 'text-yellow-600',
                'matang': 'text-red-600',
                'busuk': 'text-gray-600'
            };
            return classes[level] || '';
        }

        function formatRecommendationKey(key) {
            const mapping = {
                'storage': 'Penyimpanan',
                'handling': 'Penanganan',
                'use': 'Penggunaan',
                'timeframe': 'Jangka Waktu'
            };
            return mapping[key] || key;
        }

        function formatDecisionPath(path) {
            if (!path || !path.length) return '';
            return `
                <div class="mt-2 text-sm space-y-1">
                    ${Object.entries(path).map(([key, value]) => `
                        <div class="${key === 'conclusion' ? 'mt-2 font-medium' : ''}">${value}</div>
                    `).join('')}
                </div>
            `;
        }
    </script>
</body>
</html>

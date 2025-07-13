<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Sistem Pakar Kematangan Tomat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status-online {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .card-hover:hover {
            transform: translateY(-2px);
            transition: transform 0.2s ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-seedling text-green-600 mr-2"></i>
                        Sistem Pakar Kematangan Tomat
                    </h1>
                    <p class="text-gray-600 mt-1">Dashboard Real-time Monitoring ESP32</p>
                </div>
                
                <!-- Status ESP32 -->
                <div class="flex items-center space-x-4">
                    <div id="esp32-status" class="flex items-center px-4 py-2 rounded-full {{ $esp32Status === 'online' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        <div class="w-3 h-3 rounded-full mr-2 {{ $esp32Status === 'online' ? 'bg-green-500 status-online' : 'bg-red-500' }}"></div>
                        <span class="font-semibold">ESP32 <span id="status-text">{{ ucfirst($esp32Status) }}</span></span>
                    </div>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-clock mr-1"></i>
                        <span id="last-update">{{ now()->format('H:i:s') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <!-- Statistik Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-database text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Readings</p>
                        <p class="text-2xl font-bold text-gray-900" id="total-readings">{{ $totalReadings }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-brain text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Training Data</p>
                        <p class="text-2xl font-bold text-gray-900" id="training-data">{{ $totalTrainingData }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-percentage text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Avg Confidence</p>
                        <p class="text-2xl font-bold text-gray-900" id="avg-confidence">{{ number_format($averageConfidence ?? 0, 1) }}%</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-calendar-day text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Hari Ini</p>
                        <p class="text-2xl font-bold text-gray-900" id="today-readings">0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Terbaru ESP32 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Latest Reading -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-thermometer-half text-red-500 mr-2"></i>
                    Data Sensor Terbaru
                </h2>
                
                <div id="latest-reading-card">
                    @if($latestReading)
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Device ID:</span>
                                <span class="font-semibold">{{ $latestReading->device_id }}</span>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-center p-3 bg-red-50 rounded-lg">
                                    <div class="text-red-600 font-bold text-lg">{{ $latestReading->red_value }}</div>
                                    <div class="text-sm text-gray-600">Red</div>
                                </div>
                                <div class="text-center p-3 bg-green-50 rounded-lg">
                                    <div class="text-green-600 font-bold text-lg">{{ $latestReading->green_value }}</div>
                                    <div class="text-sm text-gray-600">Green</div>
                                </div>
                                <div class="text-center p-3 bg-blue-50 rounded-lg">
                                    <div class="text-blue-600 font-bold text-lg">{{ $latestReading->blue_value }}</div>
                                    <div class="text-sm text-gray-600">Blue</div>
                                </div>
                            </div>
                            
                            @if($latestReading->temperature || $latestReading->humidity)
                            <div class="grid grid-cols-2 gap-4">
                                @if($latestReading->temperature)
                                <div class="text-center p-3 bg-orange-50 rounded-lg">
                                    <div class="text-orange-600 font-bold text-lg">{{ number_format($latestReading->temperature, 1) }}°C</div>
                                    <div class="text-sm text-gray-600">Temperature</div>
                                </div>
                                @endif
                                @if($latestReading->humidity)
                                <div class="text-center p-3 bg-cyan-50 rounded-lg">
                                    <div class="text-cyan-600 font-bold text-lg">{{ number_format($latestReading->humidity, 1) }}%</div>
                                    <div class="text-sm text-gray-600">Humidity</div>
                                </div>
                                @endif
                            </div>
                            @endif
                            
                            @if($latestReading->maturity_level)
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600">Tingkat Kematangan:</span>
                                    <span class="px-3 py-1 rounded-full text-sm font-semibold
                                        @if($latestReading->maturity_level === 'mentah') bg-red-100 text-red-800
                                        @elseif($latestReading->maturity_level === 'setengah_matang') bg-yellow-100 text-yellow-800
                                        @elseif($latestReading->maturity_level === 'matang') bg-green-100 text-green-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $latestReading->maturity_level)) }}
                                    </span>
                                </div>
                                @if($latestReading->confidence_score)
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Confidence:</span>
                                    <span class="font-semibold">{{ number_format($latestReading->confidence_score, 1) }}%</span>
                                </div>
                                @endif
                            </div>
                            @endif
                            
                            <div class="text-sm text-gray-500 text-center">
                                <i class="fas fa-clock mr-1"></i>
                                {{ $latestReading->created_at->format('d/m/Y H:i:s') }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-exclamation-circle text-4xl mb-4"></i>
                            <p>Belum ada data dari ESP32</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Distribusi Kematangan -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-chart-pie text-blue-500 mr-2"></i>
                    Distribusi Kematangan
                </h2>
                
                <div class="space-y-3">
                    @foreach($maturityDistribution as $item)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3
                                @if($item->maturity_level === 'mentah') bg-red-500
                                @elseif($item->maturity_level === 'setengah_matang') bg-yellow-500
                                @elseif($item->maturity_level === 'matang') bg-green-500
                                @else bg-gray-500
                                @endif"></div>
                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $item->maturity_level)) }}</span>
                        </div>
                        <span class="font-bold text-gray-800">{{ $item->count }}</span>
                    </div>
                    @endforeach
                    
                    @if($maturityDistribution->isEmpty())
                    <div class="text-center py-4 text-gray-500">
                        <p>Belum ada data klasifikasi</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tabel Data Readings -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-list text-indigo-500 mr-2"></i>
                    Data Readings Terbaru
                </h2>
                <button id="refresh-data" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RGB Values</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Temp/Humidity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kematangan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="readings-table-body" class="bg-white divide-y divide-gray-200">
                        @foreach($recentReadings as $reading)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $reading->device_id }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex space-x-2">
                                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">R:{{ $reading->red_value }}</span>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">G:{{ $reading->green_value }}</span>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">B:{{ $reading->blue_value }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($reading->temperature || $reading->humidity)
                                    <div class="text-xs">
                                        @if($reading->temperature)<div>{{ number_format($reading->temperature, 1) }}°C</div>@endif
                                        @if($reading->humidity)<div>{{ number_format($reading->humidity, 1) }}%</div>@endif
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($reading->maturity_level)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        @if($reading->maturity_level === 'mentah') bg-red-100 text-red-800
                                        @elseif($reading->maturity_level === 'setengah_matang') bg-yellow-100 text-yellow-800
                                        @elseif($reading->maturity_level === 'matang') bg-green-100 text-green-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $reading->maturity_level)) }}
                                    </span>
                                    @if($reading->confidence_score)
                                        <div class="text-xs text-gray-500 mt-1">{{ number_format($reading->confidence_score, 1) }}%</div>
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $reading->created_at->format('d/m H:i') }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="openTrainingModal({{ $reading->id }})" 
                                            class="text-green-600 hover:text-green-900 transition-colors"
                                            title="Simpan sebagai Training Data">
                                        <i class="fas fa-plus-circle"></i>
                                    </button>
                                    <button onclick="deleteReading({{ $reading->id }})" 
                                            class="text-red-600 hover:text-red-900 transition-colors"
                                            title="Hapus Data">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                
                @if($recentReadings->isEmpty())
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p>Belum ada data readings</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Training Data -->
    <div id="training-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Simpan sebagai Training Data</h3>
                    
                    <form id="training-form">
                        <input type="hidden" id="reading-id" name="reading_id">
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Kelas Kematangan</label>
                            <select name="maturity_class" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Pilih Kelas</option>
                                <option value="mentah">Mentah</option>
                                <option value="setengah_matang">Setengah Matang</option>
                                <option value="matang">Matang</option>
                                <option value="busuk">Busuk</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi (Opsional)</label>
                            <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Tambahkan deskripsi..."></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeTrainingModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Setup CSRF token untuk AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Update data setiap 5 detik
        setInterval(updateDashboard, 5000);

        function updateDashboard() {
            // Update status ESP32 dan data terbaru
            $.get('/api/dashboard/latest', function(response) {
                if (response.status === 'success') {
                    updateESP32Status(response.esp32_status);
                    updateLatestReading(response.latest_reading);
                    $('#last-update').text(new Date().toLocaleTimeString());
                }
            }).fail(function() {
                console.error('Failed to fetch latest data');
            });

            // Update statistik
            $.get('/api/dashboard/stats', function(response) {
                if (response.status === 'success') {
                    updateStats(response.stats);
                }
            }).fail(function() {
                console.error('Failed to fetch stats');
            });

            // Update tabel readings
            $.get('/api/dashboard/readings', function(response) {
                if (response.status === 'success') {
                    updateReadingsTable(response.readings);
                }
            }).fail(function() {
                console.error('Failed to fetch readings');
            });
        }

        function updateESP32Status(status) {
            const statusElement = $('#esp32-status');
            const statusText = $('#status-text');
            const statusDot = statusElement.find('div:first');

            if (status === 'online') {
                statusElement.removeClass('bg-red-100 text-red-800').addClass('bg-green-100 text-green-800');
                statusDot.removeClass('bg-red-500').addClass('bg-green-500 status-online');
                statusText.text('Online');
            } else {
                statusElement.removeClass('bg-green-100 text-green-800').addClass('bg-red-100 text-red-800');
                statusDot.removeClass('bg-green-500 status-online').addClass('bg-red-500');
                statusText.text('Offline');
            }
        }

        function updateLatestReading(reading) {
            if (!reading) return;

            const card = $('#latest-reading-card');
            // Update konten card dengan data terbaru
            // Implementasi update konten sesuai struktur HTML yang ada
        }

        function updateStats(stats) {
            $('#total-readings').text(stats.total_readings || 0);
            $('#training-data').text(stats.total_training_data || 0);
            $('#avg-confidence').text((stats.average_confidence || 0) + '%');
            $('#today-readings').text(stats.readings_today || 0);
        }

        function updateReadingsTable(readings) {
            // Update tabel dengan data terbaru
            // Implementasi update tabel sesuai struktur HTML yang ada
        }

        // Modal functions
        function openTrainingModal(readingId) {
            $('#reading-id').val(readingId);
            $('#training-modal').removeClass('hidden');
        }

        function closeTrainingModal() {
            $('#training-modal').addClass('hidden');
            $('#training-form')[0].reset();
        }

        // Form submission
        $('#training-form').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize();
            
            $.post('/api/dashboard/save-training', formData, function(response) {
                if (response.status === 'success') {
                    alert('Data berhasil disimpan sebagai training data!');
                    closeTrainingModal();
                    updateDashboard();
                } else {
                    alert('Error: ' + response.message);
                }
            }).fail(function() {
                alert('Terjadi kesalahan saat menyimpan data');
            });
        });

        // Delete reading
        function deleteReading(readingId) {
            if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                $.post('/api/dashboard/delete-reading', {reading_id: readingId}, function(response) {
                    if (response.status === 'success') {
                        alert('Data berhasil dihapus!');
                        updateDashboard();
                    } else {
                        alert('Error: ' + response.message);
                    }
                }).fail(function() {
                    alert('Terjadi kesalahan saat menghapus data');
                });
            }
        }

        // Refresh button
        $('#refresh-data').on('click', function() {
            updateDashboard();
            $(this).find('i').addClass('fa-spin');
            setTimeout(() => {
                $(this).find('i').removeClass('fa-spin');
            }, 1000);
        });

        // Close modal when clicking outside
        $('#training-modal').on('click', function(e) {
            if (e.target === this) {
                closeTrainingModal();
            }
        });
    </script>
</body>
</html>
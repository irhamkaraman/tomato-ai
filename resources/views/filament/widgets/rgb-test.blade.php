<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            🧪 Testing Lab - Uji Coba Sistem AI
        </x-slot>

        <x-slot name="description">
            Masukkan nilai RGB untuk menguji langsung kemampuan algoritma AI dalam mengklasifikasikan kematangan tomat
        </x-slot>

        <div class="space-y-6">
            <!-- Form Section -->
            <form wire:submit="analyzeRGB">
                {{ $this->form }}

                <div class="mt-6 flex justify-center">
                    <x-filament::button
                        type="submit"
                        size="lg"
                        icon="{{ $isLoading ? 'heroicon-o-arrow-path' : 'heroicon-o-cpu-chip' }}"
                        class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 {{ $isLoading ? 'opacity-75 cursor-not-allowed' : '' }}"
                        :disabled="$isLoading"
                        wire:loading.attr="disabled"
                        wire:target="analyzeRGB"
                    >
                        <span wire:loading.remove wire:target="analyzeRGB">
                            🚀 Analisis RGB dengan AI
                        </span>
                        <span wire:loading wire:target="analyzeRGB" class="flex items-center">
                            Menganalisis...
                        </span>
                    </x-filament::button>
                </div>
            </form>

            <!-- Quick Test Presets -->
            <div class="mt-8 p-6 bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-800 dark:to-slate-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <x-filament::icon icon="heroicon-o-beaker" class="w-5 h-5 mr-2 text-indigo-500" />
                    Preset Nilai RGB untuk Testing Cepat
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Preset Mentah -->
                    <button
                        type="button"
                        wire:click="$set('data.red', 80); $set('data.green', 150); $set('data.blue', 60)"
                        class="p-4 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:shadow-md transition-all duration-200 hover:scale-105 group"
                    >
                        <div class="w-full h-8 rounded mb-2" style="background-color: rgb(80, 150, 60);"></div>
                        <h4 class="font-medium text-sm text-gray-900 dark:text-white">Tomat Mentah</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">RGB(80, 150, 60)</p>
                        <div class="mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-xs text-green-600 dark:text-green-400">Klik untuk test</span>
                        </div>
                    </button>

                    <!-- Preset Setengah Matang -->
                    <button
                        type="button"
                        wire:click="$set('data.red', 180); $set('data.green', 140); $set('data.blue', 70)"
                        class="p-4 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:shadow-md transition-all duration-200 hover:scale-105 group"
                    >
                        <div class="w-full h-8 rounded mb-2" style="background-color: rgb(180, 140, 70);"></div>
                        <h4 class="font-medium text-sm text-gray-900 dark:text-white">Setengah Matang</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">RGB(180, 140, 70)</p>
                        <div class="mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-xs text-yellow-600 dark:text-yellow-400">Klik untuk test</span>
                        </div>
                    </button>

                    <!-- Preset Matang -->
                    <button
                        type="button"
                        wire:click="$set('data.red', 220); $set('data.green', 80); $set('data.blue', 60)"
                        class="p-4 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:shadow-md transition-all duration-200 hover:scale-105 group"
                    >
                        <div class="w-full h-8 rounded mb-2" style="background-color: rgb(220, 80, 60);"></div>
                        <h4 class="font-medium text-sm text-gray-900 dark:text-white">Tomat Matang</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">RGB(220, 80, 60)</p>
                        <div class="mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-xs text-red-600 dark:text-red-400">Klik untuk test</span>
                        </div>
                    </button>

                    <!-- Preset Busuk -->
                    <button
                        type="button"
                        wire:click="$set('data.red', 70); $set('data.green', 90); $set('data.blue', 100)"
                        class="p-4 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:shadow-md transition-all duration-200 hover:scale-105 group"
                    >
                        <div class="w-full h-8 rounded mb-2" style="background-color: rgb(70, 90, 100);"></div>
                        <h4 class="font-medium text-sm text-gray-900 dark:text-white">Tomat Busuk</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">RGB(70, 90, 100)</p>
                        <div class="mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Klik untuk test</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- AI Processing Visualization -->
            @if($result)
                <div class="mt-8 p-6 bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl border border-emerald-200 dark:border-emerald-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <x-filament::icon icon="heroicon-o-sparkles" class="w-5 h-5 mr-2 text-emerald-500" />
                        Proses AI dalam Aksi
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-2">
                                <x-filament::icon icon="heroicon-o-squares-2x2" class="w-6 h-6 text-blue-600" />
                            </div>
                            <h4 class="font-medium text-sm text-gray-900 dark:text-white">Decision Tree</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Aturan berbasis database</p>
                        </div>

                        <div class="text-center p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-2">
                                <x-filament::icon icon="heroicon-o-map-pin" class="w-6 h-6 text-green-600" />
                            </div>
                            <h4 class="font-medium text-sm text-gray-900 dark:text-white">K-NN</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Tetangga terdekat</p>
                        </div>

                        <div class="text-center p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-2">
                                <x-filament::icon icon="heroicon-o-squares-plus" class="w-6 h-6 text-purple-600" />
                            </div>
                            <h4 class="font-medium text-sm text-gray-900 dark:text-white">Random Forest</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Ensemble trees</p>
                        </div>

                        <div class="text-center p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                            <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-2">
                                <x-filament::icon icon="heroicon-o-cpu-chip" class="w-6 h-6 text-red-600" />
                            </div>
                            <h4 class="font-medium text-sm text-gray-900 dark:text-white">Ensemble</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Voting final</p>
                        </div>
                    </div>

                    <!-- Processing Steps Animation -->
                    <div class="relative">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 h-2 bg-gradient-to-r from-blue-500 via-green-500 to-red-500 rounded-full"></div>
                        </div>
                        <div class="flex justify-between mt-2 text-xs text-gray-600 dark:text-gray-400">
                            <span>Input RGB</span>
                            <span>Analisis Paralel</span>
                            <span>Voting</span>
                            <span>Hasil Final</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Tips Section -->
            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2 flex items-center">
                    <x-filament::icon icon="heroicon-o-light-bulb" class="w-4 h-4 mr-2" />
                    Tips untuk Testing Optimal
                </h4>
                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                    <li>• Gunakan nilai RGB yang realistis (0-255) sesuai dengan warna tomat asli</li>
                    <li>• Tomat mentah cenderung memiliki nilai Green tinggi, Red rendah</li>
                    <li>• Tomat matang memiliki nilai Red tinggi, Green dan Blue rendah</li>
                    <li>• Sistem AI akan memberikan confidence score untuk setiap prediksi</li>
                </ul>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

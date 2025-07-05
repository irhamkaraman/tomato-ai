<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            🤖 Algoritma Kecerdasan Buatan
        </x-slot>

        <x-slot name="description">
            Sistem pakar kematangan tomat menggunakan ensemble learning dengan 4 algoritma AI untuk akurasi maksimal
        </x-slot>

        <div class="space-y-6">
            <!-- Algoritma Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($this->getViewData()['algorithms'] as $algorithm)
                    <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm transition-all duration-200 hover:shadow-md hover:scale-[1.02]">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0">
                                    <x-filament::icon
                                        :icon="$algorithm['icon']"
                                        class="w-8 h-8 text-{{ $algorithm['color'] }}-500"
                                    />
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $algorithm['name'] }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $algorithm['color'] }}-100 text-{{ $algorithm['color'] }}-800 dark:bg-{{ $algorithm['color'] }}-900 dark:text-{{ $algorithm['color'] }}-200">
                                            Akurasi: {{ $algorithm['accuracy'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 leading-relaxed">
                            {{ $algorithm['description'] }}
                        </p>

                        <!-- Features -->
                        <div class="space-y-2">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Fitur Utama:</h4>
                            <ul class="space-y-1">
                                @foreach($algorithm['features'] as $feature)
                                    <li class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        <x-filament::icon icon="heroicon-m-check-circle" class="w-4 h-4 text-{{ $algorithm['color'] }}-500 mt-0.5 flex-shrink-0" />
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <!-- Decorative gradient -->
                        <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-{{ $algorithm['color'] }}-500/10 to-transparent rounded-bl-3xl"></div>
                    </div>
                @endforeach
            </div>

            <!-- Maturity Levels Visualization -->
            <div class="mt-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <x-filament::icon icon="heroicon-o-squares-2x2" class="w-5 h-5 mr-2 text-blue-500" />
                    Tingkat Kematangan yang Dapat Dideteksi
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($this->getViewData()['maturity_levels'] as $level)
                        <div class="text-center p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="w-12 h-12 {{ $level['color'] }} rounded-full mx-auto mb-3 shadow-md"></div>
                            <h4 class="font-medium text-gray-900 dark:text-white text-sm">{{ $level['name'] }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $level['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- System Architecture Info -->
            <div class="mt-6 p-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border border-green-200 dark:border-green-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <x-filament::icon icon="heroicon-o-cpu-chip" class="w-5 h-5 mr-2 text-green-500" />
                    Arsitektur Sistem Ensemble Learning
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-filament::icon icon="heroicon-o-arrow-down-tray" class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h4 class="font-medium text-gray-900 dark:text-white">Input RGB</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Nilai warna merah, hijau, biru dari gambar tomat</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-filament::icon icon="heroicon-o-cog-6-tooth" class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                        </div>
                        <h4 class="font-medium text-gray-900 dark:text-white">Ensemble Processing</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">4 algoritma AI bekerja secara paralel</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-filament::icon icon="heroicon-o-check-circle" class="w-8 h-8 text-green-600 dark:text-green-400" />
                        </div>
                        <h4 class="font-medium text-gray-900 dark:text-white">Output & Rekomendasi</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Klasifikasi kematangan + panduan tindakan</p>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

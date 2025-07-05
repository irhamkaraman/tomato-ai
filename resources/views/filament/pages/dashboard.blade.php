<x-filament-panels::page>
    <!-- Hero Section -->
    <div class="mb-8 relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-700 dark:from-blue-800 dark:via-purple-800 dark:to-indigo-900 p-8 text-gray-800 shadow-2xl">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-black/10 dark:bg-black/20">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Ccircle cx="30" cy="30" r="2"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-20 dark:opacity-30"></div>
        </div>

        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="w-16 h-16 bg-white/20 dark:bg-white/30 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                            <x-filament::icon icon="heroicon-o-cpu-chip" class="w-8 h-8 text-gray-800" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold mb-2 text-gray-800 dark:text-gray-100">Sistem Pakar Kematangan Tomat</h1>
                            <p class="text-blue-100 dark:text-blue-200 text-lg">Powered by Artificial Intelligence & Ensemble Learning</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                        <div class="bg-white/10 dark:bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/20 dark:border-white/30">
                            <div class="flex items-center gap-3">
                                <x-filament::icon icon="heroicon-o-squares-2x2" class="w-6 h-6 text-blue-200 dark:text-blue-300" />
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">4 Algoritma AI</h3>
                                    <p class="text-sm text-blue-200 dark:text-blue-300">Decision Tree, KNN, Random Forest, Ensemble</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white/10 dark:bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/20 dark:border-white/30">
                            <div class="flex items-center gap-3">
                                <x-filament::icon icon="heroicon-o-chart-bar" class="w-6 h-6 text-green-200 dark:text-green-300" />
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ $ensembleAccuracy }}% Akurasi</h3>
                                    <p class="text-sm text-green-200 dark:text-green-300">Ensemble Learning untuk hasil optimal</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white/10 dark:bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/20 dark:border-white/30">
                            <div class="flex items-center gap-3">
                                <x-filament::icon icon="heroicon-o-beaker" class="w-6 h-6 text-yellow-200 dark:text-yellow-300" />
                                <div>
                                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">Real-time Testing</h3>
                                    <p class="text-sm text-yellow-200 dark:text-yellow-300">Uji coba langsung dengan input RGB</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Decorative Elements -->
                <div class="hidden lg:block">
                    <div class="relative">
                        <div class="w-32 h-32 bg-white/10 rounded-full flex items-center justify-center backdrop-blur-sm">
                            <x-filament::icon icon="heroicon-o-sparkles" class="w-16 h-16 text-gray-800" />
                        </div>
                        <div class="absolute -top-2 -right-2 w-8 h-8 bg-yellow-400 rounded-full animate-pulse"></div>
                        <div class="absolute -bottom-2 -left-2 w-6 h-6 bg-green-400 rounded-full animate-pulse delay-300"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <a href="{{ route('filament.admin.resources.training-datas.index') }}"
           class="group p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 hover:scale-105">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50 transition-colors">
                    <x-filament::icon icon="heroicon-o-academic-cap" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white">Training Data</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Kelola dataset AI</p>
                </div>
            </div>
        </a>

        <a href="{{ route('filament.admin.resources.decision-tree-rules.index') }}"
           class="group p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 hover:scale-105">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition-colors">
                    <x-filament::icon icon="heroicon-o-squares-2x2" class="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white">Decision Rules</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aturan klasifikasi</p>
                </div>
            </div>
        </a>

        <a href="{{ route('filament.admin.resources.recommendations.index') }}"
           class="group p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 hover:scale-105">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center group-hover:bg-yellow-200 dark:group-hover:bg-yellow-900/50 transition-colors">
                    <x-filament::icon icon="heroicon-o-light-bulb" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white">Rekomendasi</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Panduan tindakan</p>
                </div>
            </div>
        </a>

    </div>

    <!-- Widgets -->
    <div class="space-y-12">
        @foreach ($this->getWidgets() as $widget)
            <div style="margin-top: 2rem;">
                @livewire($widget)
            </div>
        @endforeach
    </div>

    <!-- Footer Info -->
    <div class="mt-12 p-6 bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-800 dark:to-slate-800 rounded-xl border border-gray-200 dark:border-gray-700">
        <div class="text-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Tentang Sistem</h3>
            <p class="text-gray-600 dark:text-gray-300 max-w-3xl mx-auto leading-relaxed">
                Sistem Pakar Kematangan Tomat ini menggunakan teknologi Artificial Intelligence dengan pendekatan Ensemble Learning
                yang menggabungkan 4 algoritma machine learning: Decision Tree, K-Nearest Neighbors (KNN), Random Forest, dan
                Ensemble Voting. Sistem ini mampu mengklasifikasikan tingkat kematangan tomat berdasarkan nilai RGB dengan
                akurasi hingga 92% dan memberikan rekomendasi tindakan yang sesuai.
            </p>

            <div class="flex justify-center items-center space-x-6 mt-6">
                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-code-bracket" class="w-4 h-4" />
                    <span>Laravel + Filament</span>
                </div>
                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-cpu-chip" class="w-4 h-4" />
                    <span>AI & Machine Learning</span>
                </div>
                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-chart-bar" class="w-4 h-4" />
                    <span>Real-time Analytics</span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

<?php

namespace App\Filament\Widgets;

use App\Http\Controllers\TomatReadingController;
use App\Services\ModelEvaluationService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Http\Request;

class RGBTestWidget extends Widget implements HasForms
{
    use InteractsWithForms;
    
    protected static string $view = 'filament.widgets.rgb-test';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 4;
    
    public ?array $data = [];
    
    public ?array $result = null;
    
    public bool $isLoading = false;
    
    public function mount(): void
    {
        $this->form->fill([
            'red' => 150,
            'green' => 100,
            'blue' => 80,
        ]);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('🧪 Test Sistem AI Kematangan Tomat')
                    ->description('Masukkan nilai RGB untuk menguji algoritma klasifikasi kematangan tomat')
                    ->schema([
                        TextInput::make('red')
                            ->label('Nilai Merah (R)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(255)
                            ->default(150)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->result = null),
                            
                        TextInput::make('green')
                            ->label('Nilai Hijau (G)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(255)
                            ->default(100)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->result = null),
                            
                        TextInput::make('blue')
                            ->label('Nilai Biru (B)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(255)
                            ->default(80)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->result = null),
                            
                        Placeholder::make('color_preview')
                            ->label('Preview Warna')
                            ->content(function () {
                                $red = $this->data['red'] ?? 150;
                                $green = $this->data['green'] ?? 100;
                                $blue = $this->data['blue'] ?? 80;
                                
                                return new HtmlString(
                                    '<div class="w-full h-16 rounded-lg border-2 border-gray-300 dark:border-gray-600" ' .
                                    'style="background-color: rgb(' . $red . ', ' . $green . ', ' . $blue . ');"></div>' .
                                    '<p class="text-sm text-gray-600 dark:text-gray-400 mt-2">RGB(' . $red . ', ' . $green . ', ' . $blue . ')</p>'
                                );
                            }),
                    ])
                    ->columns(3)
                    ->collapsible(),
                    
                Section::make('🤖 Hasil Analisis AI')
                    ->schema([
                        Placeholder::make('analysis_result')
                            ->label('')
                            ->content(function () {
                                if (!$this->result) {
                                    return new HtmlString('<p class="text-gray-500 dark:text-gray-400 italic">Klik "Analisis RGB" untuk melihat hasil klasifikasi AI</p>');
                                }
                                
                                $result = $this->result;
                                $maturityLevel = $result['maturity_level'] ?? 'unknown';
                                $confidence = $result['confidence_score'] ?? 0;
                                
                                $maturityColors = [
                                    'mentah' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'setengah_matang' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'matang' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'busuk' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                ];
                                
                                $colorClass = $maturityColors[$maturityLevel] ?? 'bg-gray-100 text-gray-800';
                                
                                $html = '<div class="space-y-4">';
                                
                                // Hasil Utama
                                $html .= '<div class="flex items-center space-x-4">';
                                $html .= '<span class="px-3 py-1 rounded-full text-sm font-medium ' . $colorClass . '">' . ucfirst(str_replace('_', ' ', $maturityLevel)) . '</span>';
                                $html .= '<span class="text-lg font-semibold">Confidence: ' . number_format($confidence, 1) . '%</span>';
                                $html .= '</div>';
                                
                                // Detail Algoritma
                                if (isset($result['algorithm_details'])) {
                                    $html .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">';
                                    
                                    foreach ($result['algorithm_details'] as $algo => $detail) {
                                        $algoName = ucfirst(str_replace('_', ' ', $algo));
                                        $prediction = $detail['prediction'] ?? 'unknown';
                                        $conf = $detail['confidence'] ?? 0;
                                        
                                        $html .= '<div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">';
                                        $html .= '<h4 class="font-medium text-sm">' . $algoName . '</h4>';
                                        $html .= '<p class="text-xs text-gray-600 dark:text-gray-400">' . ucfirst(str_replace('_', ' ', $prediction)) . '</p>';
                                        $html .= '<p class="text-xs font-medium">' . number_format($conf, 1) . '%</p>';
                                        $html .= '</div>';
                                    }
                                    
                                    $html .= '</div>';
                                }
                                
                                // Rekomendasi
                                if (isset($result['recommendations'])) {
                                    $html .= '<div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">';
                                    $html .= '<h4 class="font-medium text-sm mb-2">📋 Rekomendasi:</h4>';
                                    $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">';
                                    
                                    foreach ($result['recommendations'] as $category => $recommendation) {
                                        $categoryName = ucfirst(str_replace('_', ' ', $category));
                                        $html .= '<div><strong>' . $categoryName . ':</strong> ' . $recommendation . '</div>';
                                    }
                                    
                                    $html .= '</div></div>';
                                }
                                
                                $html .= '</div>';
                                
                                return new HtmlString($html);
                            })
                    ])
                    ->visible(fn () => $this->result !== null)
            ])
            ->statePath('data');
    }
    
    public function analyzeRGB(): void
    {
        $data = $this->form->getState();
        
        try {
            // Set loading state
            $this->isLoading = true;
            
            // Validasi input
            if (!isset($data['red']) || !isset($data['green']) || !isset($data['blue'])) {
                throw new \Exception('Data RGB tidak lengkap');
            }
            
            $red = (int) $data['red'];
            $green = (int) $data['green'];
            $blue = (int) $data['blue'];
            
            // Validasi range RGB
            if ($red < 0 || $red > 255 || $green < 0 || $green > 255 || $blue < 0 || $blue > 255) {
                throw new \Exception('Nilai RGB harus dalam range 0-255');
            }
            
            $controller = app(TomatReadingController::class);
            
            // Simulasi request dengan data RGB
            $request = new Request();
            $request->merge([
                'red' => $red,
                'green' => $green,
                'blue' => $blue
            ]);
            
            $response = $controller->analyze($request);
            $responseData = $response->getData(true);
            
            if ($responseData['success']) {
                $this->result = $responseData['data'];
                
                Notification::make()
                    ->title('✅ Analisis Berhasil!')
                    ->body('Sistem AI telah menganalisis nilai RGB(' . $red . ', ' . $green . ', ' . $blue . ') dan memberikan klasifikasi kematangan.')
                    ->success()
                    ->duration(5000)
                    ->send();
            } else {
                throw new \Exception($responseData['message'] ?? 'Analisis gagal');
            }
            
        } catch (\Exception $e) {
            $this->result = null;
            
            Notification::make()
                ->title('❌ Error Analisis')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->duration(5000)
                ->send();
        } finally {
            // Reset loading state
            $this->isLoading = false;
        }
    }
    
    public function getViewData(): array
    {
        return [
            'form' => $this->form,
            'result' => $this->result
        ];
    }
}
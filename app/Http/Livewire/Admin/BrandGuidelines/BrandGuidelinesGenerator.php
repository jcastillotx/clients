<?php

namespace App\Http\Livewire\Admin\BrandGuidelines;

use App\Services\BrandGuidelines\GeminiBrandAnalyzer;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class BrandGuidelinesGenerator extends Component
{
    use WithFileUploads;

    public $brandName;
    public $industry;
    public $targetAudience;
    public $logoFile;
    public $additionalAssets = [];

    public $analyzing = false;
    public $guidelines = null;
    public $colorPalette = null;
    public $tailwindConfig = null;
    public $tokensUsed = 0;
    public $cost = 0;

    public $activeTab = 'upload';

    protected $rules = [
        'brandName' => 'required|min:2',
        'industry' => 'required',
        'targetAudience' => 'required',
        'logoFile' => 'required|image|max:10240', // 10MB
        'additionalAssets.*' => 'nullable|image|max:10240',
    ];

    public function mount()
    {
        $this->authorize('access admin panel');
    }

    public function generateGuidelines()
    {
        $this->validate();

        $this->analyzing = true;

        try {
            // Store uploaded files temporarily
            $logoPath = $this->logoFile->store('temp/brand-analysis', 'local');
            $assetPaths = [$logoPath];

            foreach ($this->additionalAssets as $asset) {
                if ($asset) {
                    $assetPaths[] = $asset->store('temp/brand-analysis', 'local');
                }
            }

            // Get full paths for analysis
            $fullPaths = array_map(fn ($path) => storage_path("app/{$path}"), $assetPaths);

            /** @var GeminiBrandAnalyzer $analyzer */
            $analyzer = app(GeminiBrandAnalyzer::class);

            // Generate complete guidelines
            $result = $analyzer->generateCompleteGuidelines($fullPaths, [
                'name' => $this->brandName,
                'industry' => $this->industry,
                'target_audience' => $this->targetAudience,
                'client_id' => auth()->user()->client_id,
            ]);

            $this->guidelines = $result['guidelines'];
            $this->tokensUsed = $result['tokens_used']['total'] ?? 0;
            $this->cost = $result['cost'] ?? 0;

            // Also extract color palette from logo
            $paletteResult = $analyzer->extractColorPalette($fullPaths[0]);
            $this->colorPalette = $paletteResult['palette'];

            // Generate Tailwind config
            if ($this->colorPalette) {
                $this->tailwindConfig = $analyzer->generateTailwindConfig($this->colorPalette);
            }

            // Clean up temp files
            foreach ($assetPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            $this->activeTab = 'results';

            session()->flash('message', 'Brand guidelines generated successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error generating guidelines: '.$e->getMessage());
        } finally {
            $this->analyzing = false;
        }
    }

    public function downloadGuidelines()
    {
        if (! $this->guidelines) {
            return;
        }

        $filename = str_replace(' ', '_', strtolower($this->brandName)).'_brand_guidelines.md';

        return response()->streamDownload(function () {
            echo $this->guidelines;
        }, $filename, [
            'Content-Type' => 'text/markdown',
        ]);
    }

    public function downloadTailwindConfig()
    {
        if (! $this->tailwindConfig) {
            return;
        }

        return response()->streamDownload(function () {
            echo $this->tailwindConfig;
        }, 'tailwind.config.js', [
            'Content-Type' => 'application/javascript',
        ]);
    }

    public function reset()
    {
        $this->reset([
            'brandName',
            'industry',
            'targetAudience',
            'logoFile',
            'additionalAssets',
            'guidelines',
            'colorPalette',
            'tailwindConfig',
            'tokensUsed',
            'cost',
        ]);

        $this->activeTab = 'upload';
    }

    public function render()
    {
        return view('livewire.admin.brand-guidelines.brand-guidelines-generator');
    }
}

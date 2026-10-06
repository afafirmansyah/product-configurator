<?php

namespace App\Livewire\Configurator;

use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Services\Configurator\ConfiguratorService;
use Illuminate\Support\Str;
use Livewire\Component;

class ProductConfigurator extends Component
{
    public Product $product;
    public array $selections = [];
    public int $currentStep = 0;
    public array $steps = [];
    public ?array $result = null;
    public bool $showSummary = false;

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->product->load(['productAttributes.attribute.options']);
        $this->buildSteps();
        $this->initSelections();
    }

    private function buildSteps(): void
    {
        $grouped = $this->product->productAttributes
            ->groupBy(fn($pa) => $pa->step_name ?: 'Configuration');

        $this->steps = $grouped->keys()->values()->toArray();
    }

    private function initSelections(): void
    {
        foreach ($this->product->productAttributes as $pa) {
            if ($pa->default_value !== null) {
                $this->selections[$pa->attribute_id] = $pa->default_value;
            }
        }
    }

    public function updatedSelections(): void
    {
        $this->recalculate();
    }

    public function recalculate(): void
    {
        $service = app(ConfiguratorService::class);
        $this->result = $service->evaluate($this->product, $this->selections)->toArray();
    }

    public function nextStep(): void
    {
        if ($this->currentStep < count($this->steps) - 1) {
            $this->currentStep++;
        } else {
            $this->showSummary = true;
            $this->recalculate();
        }
    }

    public function previousStep(): void
    {
        if ($this->showSummary) {
            $this->showSummary = false;
        } elseif ($this->currentStep > 0) {
            $this->currentStep--;
        }
    }

    public function saveConfiguration(): void
    {
        $service = app(ConfiguratorService::class);
        $result = $service->evaluate($this->product, $this->selections);

        $session = ConfigurationSession::create([
            'ulid' => Str::ulid(),
            'configuration_number' => $this->generateConfigNumber(),
            'product_id' => $this->product->id,
            'user_id' => auth()->id(),
            'status' => 'draft',
            'total_price' => $result->pricing['total'],
            'currency' => $result->pricing['currency'],
            'configuration_code' => $result->configurationCode,
        ]);

        foreach ($this->selections as $attributeId => $value) {
            $session->items()->create([
                'attribute_id' => $attributeId,
                'value' => $value,
            ]);
        }

        foreach ($result->bom as $item) {
            $session->bomItems()->create([
                'component_id' => $item['component_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'is_mandatory' => $item['is_mandatory'],
            ]);
        }

        $this->redirect(route('configurator.show', $session->ulid));
    }

    private function generateConfigNumber(): string
    {
        $year = now()->year;
        $count = ConfigurationSession::whereYear('created_at', $year)->count() + 1;
        return sprintf('CFG-%d-%06d', $year, $count);
    }

    public function getCurrentStepAttributes()
    {
        if (empty($this->steps)) {
            return $this->product->productAttributes;
        }

        $stepName = $this->steps[$this->currentStep] ?? null;

        return $this->product->productAttributes->filter(
            fn($pa) => ($pa->step_name ?: 'Configuration') === $stepName
        );
    }

    public function render()
    {
        if (!$this->result) {
            $this->recalculate();
        }

        return view('livewire.configurator.product-configurator', [
            'stepAttributes' => $this->getCurrentStepAttributes(),
            'result' => $this->result,
        ]);
    }
}

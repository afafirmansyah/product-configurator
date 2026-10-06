<?php

namespace App\DTOs;

class ConfigurationResult
{
    public function __construct(
        public readonly string $configurationId,
        public readonly array $product,
        public readonly string $configurationCode,
        public readonly string $status,
        public readonly array $options,
        public readonly array $visibleAttributes,
        public readonly array $requiredAttributes,
        public readonly array $disabledOptions,
        public readonly array $technicalSpecifications,
        public readonly array $bom,
        public readonly array $pricing,
        public readonly array $validation,
        public readonly array $ruleTrace = [],
    ) {}

    public function toArray(): array
    {
        return [
            'configuration_id' => $this->configurationId,
            'product' => $this->product,
            'configuration_code' => $this->configurationCode,
            'status' => $this->status,
            'options' => $this->options,
            'visible_attributes' => $this->visibleAttributes,
            'required_attributes' => $this->requiredAttributes,
            'disabled_options' => $this->disabledOptions,
            'technical_specifications' => $this->technicalSpecifications,
            'bom' => $this->bom,
            'pricing' => $this->pricing,
            'validation' => $this->validation,
            'rule_trace' => $this->ruleTrace,
        ];
    }
}

<?php

use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\User;
use App\Services\Configuration\ConfigurationNumberGenerator;
use Illuminate\Database\QueryException;

test('generating many numbers yields distinct values', function () {
    $generator = app(ConfigurationNumberGenerator::class);

    $product = Product::factory()->create();
    $user = User::factory()->create();

    $numbers = [];

    for ($i = 0; $i < 10; $i++) {
        $number = $generator->next();
        $numbers[] = $number;

        ConfigurationSession::factory()->create([
            'configuration_number' => $number,
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);
    }

    expect($numbers)->toHaveCount(10);
    expect(array_unique($numbers))->toHaveCount(10);
});

test('the generated number uses the CFG-{year}-{6pad} format', function () {
    $number = app(ConfigurationNumberGenerator::class)->next();

    expect($number)->toMatch('/^CFG-\d{4}-\d{6}$/');
    expect($number)->toStartWith('CFG-'.date('Y').'-');
});

test('inserting a duplicate configuration number throws a unique-constraint error', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    ConfigurationSession::factory()->create([
        'configuration_number' => 'CFG-'.date('Y').'-000001',
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    ConfigurationSession::factory()->create([
        'configuration_number' => 'CFG-'.date('Y').'-000001',
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);
})->throws(QueryException::class);

<?php

namespace Tests\Feature;

use App\Models\Catalog\Product;
use App\Services\Catalog\ProductService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DynamicProductArchitectureFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Dynamic product architecture tests require MySQL in this project environment.');
        }

        $this->withoutMiddleware();
    }

    public function test_admin_can_create_product_with_dynamic_configurations(): void
    {
        $refs = $this->seedBaseCatalog(701);

        $response = $this->post(route('admin.products.store'), [
            'supplier_id' => $refs['supplier_id'],
            'category_id' => $refs['category_id'],
            'name' => 'Dynamic Product Create 701',
            'model' => 'DYN-CREATE-701',
            'car_models' => [],
            'description' => 'Dynamic create test',
            'status' => 'active',
            'units' => [
                [
                    'unit_id' => $refs['unit_id'],
                    'wholesale_price' => 100,
                    'retail_price' => 125,
                    'conversion_factor' => 1,
                    'stock_quantity' => 10,
                    'low_stock_threshold' => 2,
                ],
            ],
            'configurations' => [
                [
                    'name' => 'Red Config',
                    'sku' => 'CFG-701-RED',
                    'barcode' => 'BR-701-RED',
                    'is_default' => true,
                    'status' => 'active',
                    'attribute_value_ids' => [$refs['value_red_id']],
                    'units' => [
                        [
                            'unit_id' => $refs['unit_id'],
                            'wholesale_price' => 110,
                            'retail_price' => 140,
                            'conversion_factor' => 1,
                            'stock_quantity' => 5,
                            'low_stock_threshold' => 1,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $productId = (int) DB::table('products')->where('model', 'DYN-CREATE-701')->value('id');
        $this->assertTrue($productId > 0);

        $configurationId = (int) DB::table('product_configurations')
            ->where('product_id', $productId)
            ->where('sku', 'CFG-701-RED')
            ->value('id');

        $this->assertTrue($configurationId > 0);

        $this->assertDatabaseHas('product_configuration_attribute_values', [
            'product_configuration_id' => $configurationId,
            'product_attribute_value_id' => $refs['value_red_id'],
        ]);

        $this->assertDatabaseHas('product_configuration_units', [
            'product_configuration_id' => $configurationId,
            'unit_id' => $refs['unit_id'],
        ]);
    }

    public function test_admin_can_update_dynamic_product_configuration(): void
    {
        $refs = $this->seedBaseCatalog(702);

        $this->post(route('admin.products.store'), [
            'supplier_id' => $refs['supplier_id'],
            'category_id' => $refs['category_id'],
            'name' => 'Dynamic Product Update 702',
            'model' => 'DYN-UPD-702',
            'car_models' => [],
            'description' => 'Initial version',
            'status' => 'active',
            'units' => [
                [
                    'unit_id' => $refs['unit_id'],
                    'wholesale_price' => 90,
                    'retail_price' => 120,
                    'conversion_factor' => 1,
                ],
            ],
            'configurations' => [
                [
                    'name' => 'Initial Config',
                    'sku' => 'CFG-702-OLD',
                    'barcode' => 'BR-702-OLD',
                    'is_default' => true,
                    'status' => 'active',
                    'attribute_value_ids' => [$refs['value_red_id']],
                    'units' => [
                        [
                            'unit_id' => $refs['unit_id'],
                            'wholesale_price' => 95,
                            'retail_price' => 125,
                            'conversion_factor' => 1,
                        ],
                    ],
                ],
            ],
        ])->assertRedirect(route('admin.products.index'));

        $productRow = DB::table('products')->select('id', 'uuid')->where('model', 'DYN-UPD-702')->first();
        $this->assertNotNull($productRow);
        $this->assertTrue((int) $productRow->id > 0);
        $this->assertIsString($productRow->uuid);

        $service = app(ProductService::class);
        $productModel = Product::query()->findOrFail((int) $productRow->id);

        $service->update($productModel, [
            'supplier_id' => $refs['supplier_id'],
            'category_id' => $refs['category_id'],
            'name' => 'Dynamic Product Update 702',
            'model' => 'DYN-UPD-702',
            'car_models' => [],
            'description' => 'Updated version',
            'status' => 'active',
            'units' => [
                [
                    'unit_id' => $refs['unit_id'],
                    'wholesale_price' => 95,
                    'retail_price' => 130,
                    'conversion_factor' => 1,
                ],
            ],
            'configurations' => [
                [
                    'name' => 'Updated Config',
                    'sku' => 'CFG-702-NEW',
                    'barcode' => 'BR-702-NEW',
                    'is_default' => true,
                    'status' => 'active',
                    'attribute_value_ids' => [$refs['value_blue_id']],
                    'units' => [
                        [
                            'unit_id' => $refs['unit_id'],
                            'wholesale_price' => 98,
                            'retail_price' => 138,
                            'conversion_factor' => 1,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseMissing('product_configurations', [
            'product_id' => (int) $productRow->id,
            'sku' => 'CFG-702-OLD',
        ]);

        $newConfigurationId = (int) DB::table('product_configurations')
            ->where('product_id', (int) $productRow->id)
            ->where('sku', 'CFG-702-NEW')
            ->value('id');

        $this->assertTrue($newConfigurationId > 0);

        $this->assertDatabaseHas('product_configuration_attribute_values', [
            'product_configuration_id' => $newConfigurationId,
            'product_attribute_value_id' => $refs['value_blue_id'],
        ]);
    }

    public function test_admin_products_index_can_filter_by_dynamic_attribute_value(): void
    {
        $refs = $this->seedBaseCatalog(703);

        $this->post(route('admin.products.store'), [
            'supplier_id' => $refs['supplier_id'],
            'category_id' => $refs['category_id'],
            'name' => 'Filter Match Product 703',
            'model' => 'FILTER-A-703',
            'car_models' => [],
            'description' => null,
            'status' => 'active',
            'units' => [
                [
                    'unit_id' => $refs['unit_id'],
                    'wholesale_price' => 100,
                    'retail_price' => 130,
                    'conversion_factor' => 1,
                ],
            ],
            'configurations' => [
                [
                    'name' => 'Match Config',
                    'sku' => 'FILTER-A-SKU-703',
                    'is_default' => true,
                    'status' => 'active',
                    'attribute_value_ids' => [$refs['value_red_id']],
                    'units' => [
                        [
                            'unit_id' => $refs['unit_id'],
                            'wholesale_price' => 100,
                            'retail_price' => 130,
                            'conversion_factor' => 1,
                        ],
                    ],
                ],
            ],
        ])->assertRedirect(route('admin.products.index'));

        $this->post(route('admin.products.store'), [
            'supplier_id' => $refs['supplier_id'],
            'category_id' => $refs['category_id'],
            'name' => 'Filter Other Product 703',
            'model' => 'FILTER-B-703',
            'car_models' => [],
            'description' => null,
            'status' => 'active',
            'units' => [
                [
                    'unit_id' => $refs['unit_id'],
                    'wholesale_price' => 100,
                    'retail_price' => 130,
                    'conversion_factor' => 1,
                ],
            ],
            'configurations' => [
                [
                    'name' => 'Other Config',
                    'sku' => 'FILTER-B-SKU-703',
                    'is_default' => true,
                    'status' => 'active',
                    'attribute_value_ids' => [$refs['value_blue_id']],
                    'units' => [
                        [
                            'unit_id' => $refs['unit_id'],
                            'wholesale_price' => 100,
                            'retail_price' => 130,
                            'conversion_factor' => 1,
                        ],
                    ],
                ],
            ],
        ])->assertRedirect(route('admin.products.index'));

        $this->get(route('admin.products.index', [
            'attribute_value_ids' => [$refs['value_red_id']],
        ]))
            ->assertOk()
            ->assertSee('Filter Match Product 703')
            ->assertDontSee('Filter Other Product 703');
    }

    /**
     * @return array<string, int>
     */
    private function seedBaseCatalog(int $seed): array
    {
        $now = now();

        $supplierId = (int) DB::table('suppliers')->orderBy('id')->value('id');
        if ($supplierId <= 0) {
            $this->markTestSkipped('No supplier seed is available for dynamic product tests in this environment.');
        }

        $categoryId = 770000 + $seed;
        DB::table('categories')->updateOrInsert(
            ['id' => $categoryId],
            [
                'name' => 'Category ' . $seed,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $unitId = 660000 + $seed;
        DB::table('units')->updateOrInsert(
            ['id' => $unitId],
            [
                'name' => 'Unit ' . $seed,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $attributeId = 550000 + $seed;
        DB::table('product_attributes')->updateOrInsert(
            ['id' => $attributeId],
            [
                'name' => 'Color ' . $seed,
                'slug' => 'color-' . $seed,
                'is_filterable' => 1,
                'is_variation' => 1,
                'is_required' => 0,
                'sort_order' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $valueRedId = 551000 + $seed;
        DB::table('product_attribute_values')->updateOrInsert(
            ['id' => $valueRedId],
            [
                'product_attribute_id' => $attributeId,
                'value' => 'Red ' . $seed,
                'normalized_value' => 'red-' . $seed,
                'slug' => 'red-' . $seed,
                'sort_order' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $valueBlueId = 552000 + $seed;
        DB::table('product_attribute_values')->updateOrInsert(
            ['id' => $valueBlueId],
            [
                'product_attribute_id' => $attributeId,
                'value' => 'Blue ' . $seed,
                'normalized_value' => 'blue-' . $seed,
                'slug' => 'blue-' . $seed,
                'sort_order' => 1,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return [
            'supplier_id' => $supplierId,
            'category_id' => $categoryId,
            'unit_id' => $unitId,
            'attribute_id' => $attributeId,
            'value_red_id' => $valueRedId,
            'value_blue_id' => $valueBlueId,
        ];
    }
}

<?php

use Illuminate\Database\Seeder;

class HIRDLProductsSeeder extends Seeder
{
    private $business;
    private $categories = [];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->business = \App\Team::where('slug', 'hirdl')
            ->with('business')
            ->first()
            ->business;

        $categories = [
            'Personne suppl.',
            'Divers'
        ];

        $defs = [
            [
                'name' => 'Dortoir',
                'category' => '__root__',
                'variants' => [
                    [
                        'name' => 'Non-membre',
                        'price' => 28.67,
                        'taxes' => [
                            'TPS' => 0.44,
                            'TVQ' => 0.89,
                        ],
                    ],
                    [
                        'name' => 'Membre',
                        'price' => 24.33,
                        'taxes' => [
                            'TPS' => 0.22,
                            'TVQ' => 0.45,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Chambre privée',
                'category' => '__root__',
                'description' => 'Une ou deux personnes',
                'variants' => [
                    ['name' => 'Non-membre', 'price' => 60.89 ],
                    ['name' => 'Membre', 'price' => 54.36 ],
                ],
            ],
            [
                'name' => 'Chambre familiale',
                'category' => '__root__',
                'description' => 'Un ou deux adultes + enfants (0-17 ans)',
                'variants' => [
                    ['name' => 'Non-membre', 'price' => 83.24 ],
                    ['name' => 'Membre', 'price' => 75.67 ],
                ],
            ],
            [
                'name' => 'Adulte suppl.',
                'category' => 0,
                'variants' => [
                    [
                        'name' => 'Non-membre',
                        'price' => 28.67,
                        'taxes' => [
                            'TPS' => 0.44,
                            'TVQ' => 0.89,
                        ],
                    ],
                    [
                        'name' => 'Membre',
                        'price' => 24.33,
                        'taxes' => [
                            'TPS' => 0.22,
                            'TVQ' => 0.45,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Enfant suppl. (7-17 ans)',
                'category' => 0,
                'variants' => [
                    [
                        'name' => 'Non-membre',
                        'price' => 14.78,
                        'taxes' => [
                            'TPS' => 4.78*.05,
                            'TVQ' => 4.78*.09975,
                        ],
                    ],
                    [
                        'name' => 'Membre',
                        'price' => 13.04,
                        'taxes' => [
                            'TPS' => 0.15,
                            'TVQ' => 0.31,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Enfant suppl. (0-6 ans)',
                'category' => 0,
                'price' => 0,
            ],
            [
                'name' => 'Souper',
                'category' => 1,
                'variants' => [
                    [
                        'name' => 'Non-membre',
                        'price' => 10.88,
                        'taxes' => [
                            'TPS' => 0.54,
                            'TVQ' => 1.08,
                        ],
                    ],
                    [
                        'name' => 'Membre',
                        'price' => 9.79,
                        'taxes' => [
                            'TPS' => 0.49,
                            'TVQ' => 0.97,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Internet',
                'category' => 1,
                'price' => 0.87,
            ],
            [
                'name' => 'Lavage',
                'category' => 1,
                'price' => 4.35,
            ],
            [
                'name' => 'Carte de membre',
                'category' => 1,
                'price' => 25,
            ],
            [
                'name' => 'Camping',
                'category' => 1,
                'price' => 20,
                'taxes' => [
                    'TPS' => 0,
                    'TVQ' => 0,
                ],
            ],
            [
                'name' => 'Déjeuner',
                'category' => 1,
                'price' => 4.35,
            ],
            [
                'name' => 'Carte téléphonique 5$',
                'category' => 1,
                'price' => 4.99,
            ],
            [
                'name' => 'Carte téléphonique 10$',
                'category' => 1,
                'price' => 9.98,
            ],
        ];

        $root = $this->createCategory('__root__', '__root__');
        foreach ($categories as $index => $category) {
            $this->createCategory($category, $index, $root);
        }

        foreach ($defs as $productDef) {
            $this->createProduct($productDef);
        }
    }

    private function createCategory($name, $key, $parent = null)
    {
        $category = new \App\ProductCategory();
        $category->name = $name;
        $category->business()->associate($this->business);

        if ($parent) {
            $category->parent()->associate($parent);
        }

        $category->save();

        $this->categories[$key] = $category;

        return $category;
    }

    private function createProduct($def, $parent = null)
    {
        \App\Product::reguard();
        $product = \App\Product::make($def);
        $product->business()->associate($this->business);

        if ($parent) {
            $product->parent()->associate($parent);
        }

        $product->save();

        if (array_key_exists('taxes', $def)) {
            $this->setTaxes($product, $def['taxes']);
        }

        if (array_key_exists('variants', $def)) {
            foreach ($def['variants'] as $variant) {
                $this->createProduct($variant, $product);
            }
        }

        if (array_key_exists('category', $def) && array_key_exists($def['category'], $this->categories)) {
            $this->categories[$def['category']]->products()->save($product);
        }
    }

    private function setTaxes($prod, $taxes)
    {
        $inserts = [];

        foreach ($taxes as $name => $amount) {
            $tax = \App\Tax::where([
                'business_id' => $this->business->id,
                'name' => $name,
            ])->first();

            if (!$tax) {
                return;
            }

            $inserts[] = [
                'amount' => $amount,
                'product_id' => $prod->id,
                'tax_id' => $tax->id,
                'type' => \App\Tax::TYPE_ABSOLUTE,
            ];
        }

        if (count($inserts)) {
            \Illuminate\Support\Facades\DB::table('product_tax')->insert($inserts);
        }
    }
}

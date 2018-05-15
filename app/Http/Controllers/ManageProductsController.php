<?php

namespace App\Http\Controllers;

use App\Business;
use App\Product;
use App\ProductCategory;
use App\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Exception\NotFoundException;

class ManageProductsController extends Controller
{
    public function list()
    {
        $categories = $this->getCategories();
        $viewData = [
            'productCategories' => $categories,
            'orphanProducts'
        ];
        return view('manageProducts.list', $viewData);
    }

    public function editProduct(Product $product)
    {
        $categories = $this->getCategories();
        $categoryOptions = $this->getCategoriesOptions($categories);

        $viewData = [
            'product' => $product,
            'categoryOptions' => $categoryOptions,
            'categoryIds' => $product->categories()->pluck('product_category_id'),
            'taxes' => $this->getProductTaxes($product),
            'postUrl' => route('productCategories.products.update', ['product' => $product]),
        ];
        return view('manageProducts.products.edit', $viewData);
    }

    public function newProduct($parentId = null)
    {
        $categories = $this->getCategories();
        $categoryOptions = $this->getCategoriesOptions($categories);
        $product = new Product();
        $parent = is_null($parentId) ? null : Product::findOrFail($parentId);
        $product->parent = $parent;

        $viewData = [
            'product' => $product,
            'categoryOptions' => $categoryOptions,
            'categoryIds' => new Collection([]),
            'taxes' => $this->getProductTaxes($product),
            'postUrl' => route('productCategories.products.create', ['parent' => $parentId]),
        ];
        return view('manageProducts.products.edit', $viewData);
    }

    public function createProduct(Request $request, $parentId = null)
    {
        $business = Auth::user()->currentTeam->business;
        $product = new Product();
        $product->business_id = $business->id;

        if (!is_null($parentId)) {
            $parent = Product::findOrFail($parentId);
            $product->parent_id = $parent->id;
        }

        return $this->updateProduct($request, $product);
    }

    public function updateProduct(Request $request, Product $product)
    {
        $business = Auth::user()->currentTeam->business;
        $product->name = $request->get('name');
        $product->description = $request->get('description');
        $modifications = [];

        $price = trim($request->get('price'));

        if (strlen($price)) {
            $product->price = floatval($price);
        }

        $product->save();

        // Categories
        if ($request->has('categories')) {
            $product->categories()->detach(); // detach all
            foreach ($request->get('categories') as $categoryId) {
                $product->categories()->attach($categoryId);
            }

            $modifications[] = Business::MODIFICATION_CATEGORIES;
        }

        // Taxes
        if ($request->has('taxes')) {
            foreach ($request->get('taxes') as $taxId => $value) {
                $value = trim($value);

                if (!strlen($value)) {
                    $this->getProductTaxQuery($product, $taxId)->delete();
                } else {
                    $value = floatval($value);
                    $hasAlreadyTaxes = $this->getProductTaxQuery($product, $taxId)->count();

                    if ($hasAlreadyTaxes) {
                        $this->getProductTaxQuery($product, $taxId)
                          ->update(['amount' => $value]);
                    } else {
                        DB::table('product_tax')->insert([
                            [
                                'product_id' => $product->id,
                                'tax_id' => $taxId,
                                'type' => Tax::TYPE_ABSOLUTE,
                                'amount' => $value,
                            ],
                        ]);
                    }
                }
            }
        }

        $modifications[] = Business::MODIFICATION_PRODUCTS;
        $business->bumpVersion($modifications);

        if ($product->parent) {
            return redirect(route('productCategories.products.edit', ['product' => $product->parent]));
        }

        return redirect(route('productCategories.list'));
    }

    /**
     * @param \App\Product $product
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function deleteProduct(Product $product)
    {
        $business = Auth::user()->currentTeam->business;
        $parent = $product->parent;
        $product->variants()->delete(); // soft deletes
        // Remove from all categories
        $product->categories()->detach();
        $product->delete(); // soft deletes
        $business->bumpVersion([Business::MODIFICATION_PRODUCTS]);

        if ($parent) {
            return redirect(route('productCategories.products.edit', ['product' => $parent]));
        }

        return redirect(route('productCategories.list'));
    }

    public function editCategory(ProductCategory $category)
    {
        $categories = $this->getCategories();
        $categoryOptions = $this->getCategoriesOptions($categories);

        $viewData = [
            'productCategory' => $category,
            'categoryOptions' => $categoryOptions,
            'currentParent' => $category->parent ? $category->parent->id : '',
        ];
        return view('manageProducts.categories.edit', $viewData);
    }

    public function newCategory()
    {
        $categories = $this->getCategories();
        $categoryOptions = $this->getCategoriesOptions($categories);

        $viewData = [
            'categoryOptions' => $categoryOptions,
        ];
        return view('manageProducts.categories.new', $viewData);
    }

    public function createCategory(Request $request)
    {
        $business = Auth::user()->currentTeam->business;
        $parent = $this->getCategory($request->get('parent'));
        $category =  new ProductCategory([
            'name' => $request->get('name'),
        ]);
        $category->parent_id = $parent->id;
        $category->business_id = $business->id;
        $category->save();

        $business->bumpVersion([Business::MODIFICATION_CATEGORIES]);

        return redirect(route('productCategories.list'));
    }

    public function updateCategory(Request $request, ProductCategory $category)
    {
        $business = Auth::user()->currentTeam->business;
        $parent = $this->getCategory($request->get('parent'));
        $category->name = $request->get('name');
        $category->parent_id = $parent->id;
        $category->save();

        $business->bumpVersion([Business::MODIFICATION_CATEGORIES]);

        return redirect(route('productCategories.list'));
    }

    /**
     * @param \App\ProductCategory $category
     *
     * @throws \Exception
     */
    public function deleteCategory(ProductCategory $category)
    {
        $business = Auth::user()->currentTeam->business;

        if ($category->products()->count() || $category->categories()->count()) {
            throw new \Exception("Category not empty. Cannot delete it.");
        }

        $category->delete();

        $business->bumpVersion([Business::MODIFICATION_CATEGORIES]);

        return redirect(route('productCategories.list'));
    }

    protected function getCategory($id)
    {
        $business = Auth::user()->currentTeam->business;

        $category = ProductCategory::where('id', $id)
             ->where('business_id', $business->id)
             ->first();

        if (!$category) {
            throw new NotFoundException();
        }

        return $category;
    }

    /**
     * @return \Illuminate\Support\Collection|static
     */
    protected function getCategories($parent = null)
    {
        $isRoot = false;

        if ($parent === null) {
            $business = Auth::user()->currentTeam->business;
            $root = $business->rootProductCategory;
            $isRoot = true;
            $categories = new Collection([$root]);
        } else {
            $categories = ProductCategory::where('parent_id', $parent->id)->get();
        }


        /**
         * @var $category ProductCategory
         */
        return $categories->map(function ($category) use ($isRoot) {
            $hasChildren = $category->categories()->count() > 0;
            $children = null;

            if ($hasChildren) {
                $children = $this->getCategories($category);
            }

            return [
                'category' => $category,
                'children' => $children,
                'isRoot' => $isRoot,
            ];
        });
    }

    protected function getCategoriesOptions($categories, $level = 0)
    {
        $options = [];

        foreach ($categories as $category) {
            $pre = str_repeat('&nbsp;', $level);
            $isRoot = $category['isRoot'];
            $options[] = [
                'value' => $category['category']->id,
                'label' => $pre . ($isRoot ? 'Racine' : $category['category']->name),
            ];
            if ($category['children'] !== null) {
                $subOptions = $this->getCategoriesOptions($category['children'], $level + 1);
                $options = array_merge($options, $subOptions);
            }
        }

        return $options;
    }

    protected function getProductTaxes(Product $product)
    {
        $business = Auth::user()->currentTeam->business;
        $taxes = $business->taxes;
        $productTaxes = DB::table('product_tax')
            ->select('amount', 'type', 'tax_id')
            ->where('product_id', $product->id)
            ->get()
            ->keyBy('tax_id');

        // Only works with absolute tax values, not percentage
        return $taxes->map(function ($tax) use ($productTaxes) {
            $amount = $productTaxes->has($tax->id) ? $productTaxes[$tax->id]->amount : null;
            return [
                'tax' => $tax,
                'amount' => $amount,
            ];
        });
    }

    protected function getProductTaxQuery(Product $product, $taxId)
    {
        return DB::table('product_tax')
                 ->where(['product_id' => $product->id, 'tax_id' => $taxId]);
    }
}

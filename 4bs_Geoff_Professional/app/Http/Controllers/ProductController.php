<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('products')->orderBy('brand');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $products = $query->paginate(20);

        return view('admin.products', compact('products'));
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'brand' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:120'],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'unit' => ['required', 'string', 'max:30'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        DB::table('products')->insert([
            ...$data,
            'status' => $data['quantity'] > 0 ? 'available' : 'unavailable',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $logger->log('product.added', 'product', null, "Added {$data['brand']} {$data['name']} ({$data['quantity']} {$data['unit']})", $request);

        return back()->with('success', 'Product added.');
    }

    public function sell(Request $request, int $id, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:1000000']]);

        $result = DB::transaction(function () use ($id, $data, $request, $logger): bool {
            $product = DB::table('products')->where('id', $id)->lockForUpdate()->first();
            if (! $product || $product->quantity < $data['quantity']) {
                return false;
            }

            $remaining = $product->quantity - $data['quantity'];
            DB::table('products')->where('id', $id)->update([
                'quantity' => $remaining,
                'status' => $remaining > 0 ? 'available' : 'unavailable',
                'updated_at' => now(),
            ]);

            $total = $data['quantity'] * $product->price;
            DB::table('sales')->insert([
                'product_id' => $id,
                'quantity' => $data['quantity'],
                'total' => $total,
                'sold_at' => today(),
                'note' => 'Manual admin deduction',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $logger->log('product.sold', 'product', $id, "Sold {$data['quantity']} {$product->unit} of {$product->brand} {$product->name} for ₱{$total}", $request);

            return true;
        }, 3);

        return back()->with($result ? 'success' : 'error', $result ? 'Inventory deducted.' : 'There is not enough stock.');
    }
}

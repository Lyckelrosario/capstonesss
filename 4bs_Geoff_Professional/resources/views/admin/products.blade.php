@extends('layouts.app')
@section('title', 'Inventory | 4BS Garage')
@section('content')
<div class="sidebar-layout">
@include('partials.admin-sidebar')
<section class="page-enter">
  <p class="eyebrow">Stock control</p>
  <h1 class="page-title">Inventory</h1>
  <p class="page-copy">Add products and record sold quantities. Deductions use a database lock to prevent stock from going below zero.</p>

  <div class="card">
   <form method="post" action="/admin/products">@csrf
    <div class="grid">
     <div><label>Brand</label><input name="brand" value="{{ old('brand') }}" maxlength="120" required></div>
     <div><label>Product name</label><input name="name" value="{{ old('name') }}" maxlength="160" required></div>
     <div><label>Category</label><input name="category" value="{{ old('category') }}" maxlength="120" required></div>
     <div><label>Quantity</label><input name="quantity" type="number" min="0" max="1000000" value="{{ old('quantity',0) }}" required></div>
     <div><label>Unit</label><input name="unit" value="{{ old('unit','pcs') }}" maxlength="30" required></div>
     <div><label>Unit price</label><input name="price" type="number" min="0" max="99999999.99" step=".01" value="{{ old('price') }}" required></div>
    </div><button class="btn primary">Add product</button>
   </form>
  </div><br>

  {{-- Search & Filter --}}
  <form class="card" method="get" style="padding:16px;margin-bottom:18px">
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
      <div style="flex:1;min-width:200px">
        <label for="search" style="margin-bottom:4px;font-size:12px">Search products</label>
        <input id="search" name="search" type="text" value="{{ request('search') }}" placeholder="Brand, name, category..." style="margin:0">
      </div>
      <div style="min-width:140px">
        <label for="status" style="margin-bottom:4px;font-size:12px">Status</label>
        <select id="status" name="status" style="margin:0">
          <option value="">All</option>
          <option value="available" @selected(request('status')==='available')>Available</option>
          <option value="unavailable" @selected(request('status')==='unavailable')>Unavailable</option>
        </select>
      </div>
      <button class="btn primary" type="submit" style="margin-bottom:0">Filter</button>
      @if(request('search') || request('status'))
        <a class="btn ghost" href="{{ route('admin.products') }}">Clear</a>
      @endif
    </div>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Brand</th><th>Product</th><th>Category</th><th>Qty</th><th>Status</th><th>Price</th><th>Sold / Deduct</th></tr></thead>
      <tbody>
      @forelse($products as $p)
       <tr>
         <td>{{ $p->brand }}</td>
         <td>{{ $p->name }}</td>
         <td><small>{{ $p->category }}</small></td>
         <td>{{ $p->quantity }} {{ $p->unit }}</td>
         <td><span class="pill {{ $p->status==='available'?'completed':'cancelled' }}">{{ ucfirst($p->status) }}</span></td>
         <td>₱{{ number_format($p->price,2) }}</td>
         <td>
           @if($p->quantity>0)
             <form class="actions" method="post" action="/admin/products/{{ $p->id }}/sell">@csrf<input aria-label="Quantity sold" style="max-width:90px;margin:0" name="quantity" type="number" min="1" max="{{ $p->quantity }}" required><button class="btn small primary">Deduct</button></form>
           @else
             <span class="muted">Out of stock</span>
           @endif
         </td>
       </tr>
      @empty<tr><td colspan="7">No inventory products yet.</td></tr>@endforelse
      </tbody>
    </table>
  </div>

  <div class="actions" style="margin-top:18px;justify-content:center">
    {{ $products->appends(request()->only(['search', 'status']))->links() }}
  </div>
</section>
</div>
@endsection

@extends('layouts.app')
@section('content')
<div class="sidebar-layout">
@include('partials.client-sidebar')
<div><h2>Rate Completed Services</h2>
 @forelse($appointments as $a)  <form class="card" method="post" action="{{ url('client/feedback') }}">@csrf
  <input type="hidden" name="appointment_id" value="{{ $a->id }}">
  <h3>{{ $a->service }} with {{ $a->mechanic }}</h3>
  <p class="muted">{{ $a->appointment_date }} — Your feedback helps improve the shop.</p>
  <label>Shop Rating</label><br><div class="stars">@for($i=5;$i>=1;$i--)<input id="shop{{ $a->id }}-{{ $i }}" type="radio" name="shop_rating" value="{{ $i }}" required><label for="shop{{ $a->id }}-{{ $i }}">★</label>@endfor</div><br>
  <label>Mechanic Rating</label><br><div class="stars">@for($i=5;$i>=1;$i--)<input id="mech{{ $a->id }}-{{ $i }}" type="radio" name="mechanic_rating" value="{{ $i }}" required><label for="mech{{ $a->id }}-{{ $i }}">★</label>@endfor</div>
  <textarea name="comment" placeholder="Write your feedback" required></textarea><button class="btn">Submit Feedback</button>
 </form><br>
 @empty<div class="card"><p class="muted">No completed service available for rating.</p></div>@endforelse
 </div>
</div>
@endsection

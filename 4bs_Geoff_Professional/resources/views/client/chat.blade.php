@extends('layouts.app')
@section('title', 'AI & Live Support | 4BS Garage')
@section('content')
<div class="sidebar-layout">
@include('partials.client-sidebar')
<div>
  <div id="react-page" data-component="ChatPage"></div>
  <script id="page-props" type="application/json">{!! json_encode($chatProps, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
</div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Live Chat | 4BS Garage')
@section('content')
<div class="sidebar-layout">
@include('partials.admin-sidebar')
<div>
  <div id="react-page" data-component="AdminLiveChatPage"></div>
  <script id="page-props" type="application/json">{!! json_encode($chatProps, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
</div>
</div>
@endsection

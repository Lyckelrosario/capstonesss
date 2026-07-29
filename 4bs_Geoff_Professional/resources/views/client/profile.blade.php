@extends('layouts.app')
@section('title', 'My Profile | 4BS Garage')
@section('content')
<div class="sidebar-layout">
@include('partials.client-sidebar')
<div>
  <div id="react-page" data-component="ProfilePage"></div>
  <script id="page-props" type="application/json">{!! json_encode($profileProps, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
</div>
</div>
@endsection

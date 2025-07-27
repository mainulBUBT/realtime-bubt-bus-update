@extends('layouts.app')

@section('title', 'BUBT Bus Tracker')

@section('content')
<!-- Home Screen -->
<div id="home-screen" class="screen active-screen">
    @livewire('bus-list')
</div>
@endsection
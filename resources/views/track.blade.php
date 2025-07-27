@extends('layouts.track')

@section('title', 'BUBT Bus Tracker - Live Tracking')

@section('content')
<!-- Track Screen -->
<div id="track-screen" class="screen active">
    @livewire('bus-tracker', ['busId' => $busId])
</div>
@endsection
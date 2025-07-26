@extends('layouts.app')

@section('title', 'BUBT Bus Tracker')

@section('content')
<!-- Home Screen -->
<div id="home-screen" class="screen active-screen">
    <!-- Modern Mobile Header -->
    <div class="mobile-header">
        <div class="header-top">
            <button class="menu-btn" id="menu-btn">
                <i class="bi bi-list"></i>
            </button>
            <div class="header-title">
                <h1>BUBT Bus Tracker</h1>
                <span class="location-indicator">
                    <i class="bi bi-geo-alt"></i>
                    Dhaka, Bangladesh
                </span>
            </div>
            <button class="notification-btn" id="notification-btn">
                <i class="bi bi-bell"></i>
                <span class="notification-badge">3</span>
            </button>
        </div>

        <!-- Bus Dropdown Selector -->
        <div class="bus-dropdown-container">
            <div class="bus-dropdown" id="bus-dropdown">
                <div class="dropdown-header" id="dropdown-header">
                    <span>Select Bus Route</span>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="dropdown-menu" id="dropdown-menu">
                    <div class="dropdown-item active" data-bus-id="all">
                        <span>All Buses</span>
                    </div>
                    <div class="dropdown-item" data-bus-id="B1">
                        <div class="bus-badge-mini">B1</div>
                        <span>Buriganga</span>
                    </div>
                    <div class="dropdown-item" data-bus-id="B2">
                        <div class="bus-badge-mini">B2</div>
                        <span>Brahmaputra</span>
                    </div>
                    <div class="dropdown-item" data-bus-id="B3">
                        <div class="bus-badge-mini">B3</div>
                        <span>Padma</span>
                    </div>
                    <div class="dropdown-item" data-bus-id="B4">
                        <div class="bus-badge-mini">B4</div>
                        <span>Meghna</span>
                    </div>
                    <div class="dropdown-item" data-bus-id="B5">
                        <div class="bus-badge-mini">B5</div>
                        <span>Jamuna</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bus Cards -->
    <div class="home-content">
        <h2 class="home-section-title">Available Buses</h2>

        <div class="home-bus-cards-container">
            <div class="home-bus-card" data-bus-id="B1">
                <div class="home-bus-left">
                    <div class="home-bus-badge">B1</div>
                </div>
                <div class="home-bus-middle">
                    <h3 class="home-bus-name">Buriganga</h3>
                    <p class="home-bus-schedule">Departure: 7:00 AM | Return: 5:00 PM</p>
                </div>
                <div class="home-bus-right">
                    <span class="home-bus-status active"></span>
                    <i class="bi bi-chevron-right"></i>
                </div>
            </div>

            <div class="home-bus-card" data-bus-id="B2">
                <div class="home-bus-left">
                    <div class="home-bus-badge">B2</div>
                </div>
                <div class="home-bus-middle">
                    <h3 class="home-bus-name">Brahmaputra</h3>
                    <p class="home-bus-schedule">Departure: 7:00 AM | Return: 5:00 PM</p>
                </div>
                <div class="home-bus-right">
                    <span class="home-bus-status delayed"></span>
                </div>
            </div>

            <div class="home-bus-card" data-bus-id="B3">
                <div class="home-bus-left">
                    <div class="home-bus-badge">B3</div>
                </div>
                <div class="home-bus-middle">
                    <h3 class="home-bus-name">Padma</h3>
                    <p class="home-bus-schedule">Departure: 7:00 AM | Return: 5:00 PM</p>
                </div>
                <div class="home-bus-right">
                    <span class="home-bus-status active"></span>
                    <i class="bi bi-chevron-right"></i>
                </div>
            </div>

            <div class="home-bus-card" data-bus-id="B4">
                <div class="home-bus-left">
                    <div class="home-bus-badge">B4</div>
                </div>
                <div class="home-bus-middle">
                    <h3 class="home-bus-name">Meghna</h3>
                    <p class="home-bus-schedule">Departure: 4:10 PM | Return: 9:25 PM</p>
                </div>
                <div class="home-bus-right">
                    <span class="home-bus-status inactive"></span>
                </div>
            </div>

            <div class="home-bus-card" data-bus-id="B5">
                <div class="home-bus-left">
                    <div class="home-bus-badge">B5</div>
                </div>
                <div class="home-bus-middle">
                    <h3 class="home-bus-name">Jamuna</h3>
                    <p class="home-bus-schedule">Departure: 7:00 AM | Return: 5:00 PM</p>
                </div>
                <div class="home-bus-right">
                    <span class="home-bus-status active"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
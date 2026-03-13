@extends('layouts.admin')

@section('title', 'Add New Bus')

@section('content')
<div class="mb-6 md:mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Add New Bus</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Fill in the details to add a new bus to your fleet</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 p-6 md:p-8">
    <form action="{{ route('admin.buses.store') }}" method="POST" class="space-y-6" data-form-submit>
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Bus Code --}}
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="code">
                    <i class="bi bi-tag text-emerald-500"></i>
                    Bus Code <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white @error('code') border-red-500 @enderror"
                           id="code" type="text" name="code" placeholder="e.g. B1" value="{{ old('code') }}" required>
                </div>
                @error('code')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1">
                        <i class="bi bi-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Plate Number --}}
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="plate_number">
                    <i class="bi bi-credit-card text-emerald-500"></i>
                    Plate Number <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white @error('plate_number') border-red-500 @enderror"
                           id="plate_number" type="text" name="plate_number" placeholder="e.g. DHAKA-A-12345" value="{{ old('plate_number') }}" required>
                </div>
                @error('plate_number')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1">
                        <i class="bi bi-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Display Name --}}
            <div class="md:col-span-2">
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="display_name">
                    <i class="bi bi-type text-emerald-500"></i>
                    Display Name <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white @error('display_name') border-red-500 @enderror"
                           id="display_name" type="text" name="display_name" placeholder="e.g. Buriganga Express" value="{{ old('display_name') }}" required>
                </div>
                @error('display_name')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1">
                        <i class="bi bi-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Device ID --}}
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="device_id">
                    <i class="bi bi-broadcast text-emerald-500"></i>
                    Device ID
                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(Optional)</span>
                </label>
                <div class="relative">
                    <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white @error('device_id') border-red-500 @enderror"
                           id="device_id" type="text" name="device_id" placeholder="e.g. GPS-001" value="{{ old('device_id') }}">
                </div>
                @error('device_id')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1">
                        <i class="bi bi-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Capacity --}}
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="capacity">
                    <i class="bi bi-people text-emerald-500"></i>
                    Capacity <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white @error('capacity') border-red-500 @enderror"
                           id="capacity" type="number" name="capacity" placeholder="e.g. 50" value="{{ old('capacity') }}" required min="1">
                </div>
                @error('capacity')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1">
                        <i class="bi bi-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Status --}}
            <div class="md:col-span-2">
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="status">
                    <i class="bi bi-toggle-on text-emerald-500"></i>
                    Status <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <select class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white appearance-none cursor-pointer @error('status') border-red-500 @enderror"
                            id="status" name="status" required>
                        <option value="">Select Status</option>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>🟢 Active</option>
                        <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>🔧 Maintenance</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>🔴 Inactive</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                </div>
                @error('status')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1">
                        <i class="bi bi-exclamation-circle"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('admin.buses.index') }}" class="px-6 py-3 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white font-semibold transition-colors inline-flex items-center gap-2">
                <i class="bi bi-x-lg"></i>
                Cancel
            </a>
            <button type="submit" class="bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 focus:outline-none focus:ring-4 focus:ring-emerald-500/50 transition-all duration-300 transform hover:scale-[1.02] inline-flex items-center gap-2">
                <i class="bi bi-plus-circle"></i>
                Create Bus
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('[data-form-submit]');
    const submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', function() {
        setButtonLoading(submitBtn, '<i class="bi bi-arrow-clockwise animate-spin mr-2"></i>Creating...');
    });
});
</script>
@endsection

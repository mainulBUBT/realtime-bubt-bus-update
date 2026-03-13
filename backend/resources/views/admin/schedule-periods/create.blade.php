@extends('layouts.admin')

@section('title', 'Add Schedule Period')

@section('content')
<div class="mb-6 md:mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Add Schedule Period</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Create a new academic semester or schedule period</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 p-6 md:p-8">
    <form action="{{ route('admin.schedule-periods.store') }}" method="POST" class="space-y-6" data-form-submit>
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="name">
                    <i class="bi bi-type text-orange-500"></i>
                    Period Name <span class="text-red-500">*</span>
                </label>
                <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                       id="name" type="text" name="name" placeholder="e.g. Fall Semester 2025" value="{{ old('name') }}" required>
                @error('name')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="start_date">
                    <i class="bi bi-calendar-event text-orange-500"></i>
                    Start Date <span class="text-red-500">*</span>
                </label>
                <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all dark:bg-gray-700 dark:text-white @error('start_date') border-red-500 @enderror"
                       id="start_date" type="date" name="start_date" value="{{ old('start_date') }}" required>
                @error('start_date')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="end_date">
                    <i class="bi bi-calendar-check text-orange-500"></i>
                    End Date <span class="text-red-500">*</span>
                </label>
                <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all dark:bg-gray-700 dark:text-white @error('end_date') border-red-500 @enderror"
                       id="end_date" type="date" name="end_date" value="{{ old('end_date') }}" required>
                @error('end_date')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }} id="is_active" class="w-5 h-5 text-orange-600 border-gray-300 rounded focus:ring-orange-500 focus:ring-2">
            <div class="flex-1">
                <label for="is_active" class="text-sm font-bold text-gray-700 dark:text-gray-300 cursor-pointer flex items-center gap-2">
                    <i class="bi bi-toggle-on text-orange-500"></i>
                    Set as active period
                </label>
                <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">Only one period can be active at a time</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('admin.schedule-periods.index') }}" class="px-6 py-3 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white font-semibold transition-colors inline-flex items-center gap-2">
                <i class="bi bi-x-lg"></i>
                Cancel
            </a>
            <button type="submit" class="bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 focus:outline-none focus:ring-4 focus:ring-orange-500/50 transition-all duration-300 transform hover:scale-[1.02] inline-flex items-center gap-2">
                <i class="bi bi-plus-circle"></i>
                Create Period
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

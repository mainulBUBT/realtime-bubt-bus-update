@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="mb-6 md:mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Edit User</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Update details for <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $user->name }}</span></p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 p-6 md:p-8">
    <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-6" data-form-submit>
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Name --}}
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="name">
                    <i class="bi bi-person text-emerald-500"></i>
                    Name <span class="text-red-500">*</span>
                </label>
                <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                    id="name" type="text" name="name" placeholder="e.g. John Doe" value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="email">
                    <i class="bi bi-envelope text-emerald-500"></i>
                    Email <span class="text-red-500">*</span>
                </label>
                <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white @error('email') border-red-500 @enderror"
                    id="email" type="email" name="email" placeholder="e.g. john@example.com" value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            {{-- Phone --}}
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="phone">
                    <i class="bi bi-telephone text-emerald-500"></i>
                    Phone
                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(Optional)</span>
                </label>
                <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white @error('phone') border-red-500 @enderror"
                    id="phone" type="text" name="phone" placeholder="e.g. +880 1700 000000" value="{{ old('phone', $user->phone) }}">
                @error('phone')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            {{-- Role --}}
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="role">
                    <i class="bi bi-person-badge text-emerald-500"></i>
                    Role <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <select class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white appearance-none cursor-pointer @error('role') border-red-500 @enderror"
                        id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin"   {{ old('role', $user->role) == 'admin'   ? 'selected' : '' }}>🛡️ Admin</option>
                        <option value="driver"  {{ old('role', $user->role) == 'driver'  ? 'selected' : '' }}>🚌 Driver</option>
                        <option value="student" {{ old('role', $user->role) == 'student' ? 'selected' : '' }}>🎓 Student</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                </div>
                @error('role')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="approval_status">
                    <i class="bi bi-patch-check text-emerald-500"></i>
                    Approval Status
                </label>
                <div class="relative">
                    <select class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white appearance-none cursor-pointer @error('approval_status') border-red-500 @enderror"
                        id="approval_status" name="approval_status">
                        <option value="approved" {{ old('approval_status', $user->approval_status ?? 'approved') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="pending" {{ old('approval_status', $user->approval_status ?? 'approved') == 'pending' ? 'selected' : '' }}>Pending approval</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Used for driver accounts. Non-driver accounts are kept approved.</p>
                @error('approval_status')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            {{-- New Password --}}
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="password">
                    <i class="bi bi-lock text-emerald-500"></i>
                    New Password
                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(Leave blank to keep current)</span>
                </label>
                <div class="relative">
                    <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white @error('password') border-red-500 @enderror"
                        id="password" type="password" name="password" placeholder="Min. 8 characters">
                    <button type="button" onclick="togglePassword('password', 'eye-password')" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="bi bi-eye" id="eye-password"></i>
                    </button>
                </div>
                @error('password')
                    <p class="text-red-500 text-xs mt-2 flex items-center gap-1"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div>
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 flex items-center gap-2" for="password_confirmation">
                    <i class="bi bi-lock-fill text-emerald-500"></i>
                    Confirm Password
                </label>
                <div class="relative">
                    <input class="w-full px-4 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white"
                        id="password_confirmation" type="password" name="password_confirmation" placeholder="Re-enter new password">
                    <button type="button" onclick="togglePassword('password_confirmation', 'eye-confirm')" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="bi bi-eye" id="eye-confirm"></i>
                    </button>
                </div>
            </div>

        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('admin.users.index') }}" class="px-6 py-3 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white font-semibold transition-colors inline-flex items-center gap-2">
                <i class="bi bi-x-lg"></i>
                Cancel
            </a>
            <button type="submit" class="bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 focus:outline-none focus:ring-4 focus:ring-emerald-500/50 transition-all duration-300 transform hover:scale-[1.02] inline-flex items-center gap-2">
                <i class="bi bi-check-circle"></i>
                Save Changes
            </button>
        </div>
    </form>
</div>

<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('[data-form-submit]');
    const submitBtn = form.querySelector('button[type="submit"]');
    form.addEventListener('submit', function() {
        setButtonLoading(submitBtn, '<i class="bi bi-arrow-clockwise animate-spin mr-2"></i>Saving...');
    });
});
</script>
@endsection

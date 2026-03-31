@extends('layouts.admin')

@section('title', 'Users')
@section('breadcrumb-title', 'Users')

@section('content')
<div class="mb-6 md:mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Manage Users</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Manage admins, drivers, and students</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 transition-all duration-300 transform hover:scale-[1.02]">
        <i class="bi bi-plus-lg"></i>
        Add New User
    </a>
</div>

{{-- Search & Filter Bar --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-100 dark:border-gray-700 mb-6">
    <div class="p-4 md:p-6">
        <form id="search-filter-form" action="{{ route('admin.users.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <div class="relative">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="search-input" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or phone..."
                        class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white">
                </div>
            </div>
            <div class="flex gap-3">
                <select id="role-filter" name="role" class="px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white font-medium">
                    <option value="">All Roles</option>
                    <option value="admin"   {{ request('role') === 'admin'   ? 'selected' : '' }}>Admin</option>
                    <option value="driver"  {{ request('role') === 'driver'  ? 'selected' : '' }}>Driver</option>
                    <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>Student</option>
                </select>
                <button type="submit" class="px-4 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-xl transition-colors flex items-center gap-2">
                    <i class="bi bi-funnel"></i>
                    <span class="hidden sm:inline">Filter</span>
                </button>
                <a href="{{ route('admin.users.index') }}" class="px-4 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-colors flex items-center gap-2">
                    <i class="bi bi-x-lg"></i>
                    <span class="hidden sm:inline">Clear</span>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="max-w-full bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="max-w-full overflow-x-auto">
        <table class="w-full min-w-[980px] divide-y divide-gray-100 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trips</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Joined</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white text-sm
                                @if($user->role === 'admin') bg-gradient-to-br from-purple-500 to-indigo-600
                                @elseif($user->role === 'driver') bg-gradient-to-br from-emerald-500 to-teal-600
                                @else bg-gradient-to-br from-blue-500 to-cyan-600 @endif">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white">{{ $user->name }}</p>
                                @if($user->id === auth()->id())
                                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">(You)</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $user->email }}</p>
                        @if($user->phone)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                <i class="bi bi-telephone mr-1"></i>{{ $user->phone }}
                            </p>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        @if($user->role === 'admin')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30 text-purple-700 dark:text-purple-400">
                                <i class="bi bi-shield-check"></i> Admin
                            </span>
                        @elseif($user->role === 'driver')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 text-emerald-700 dark:text-emerald-400">
                                <i class="bi bi-person-badge"></i> Driver
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-blue-100 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/30 text-blue-700 dark:text-blue-400">
                                <i class="bi bi-mortarboard"></i> Student
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 font-semibold">
                            <i class="bi bi-signpost-2"></i>
                            {{ $user->trips_count }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $user->created_at->format('d M Y') }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.users.edit', $user) }}"
                               class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 flex items-center justify-center transition-all duration-200 hover:scale-110"
                               title="Edit">
                                <i class="bi bi-pencil-fill text-lg"></i>
                            </a>
                            @if($user->id !== auth()->id())
                            <button onclick="deleteItem('{{ route('admin.users.destroy', $user) }}', '{{ $user->name }}')"
                                    class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 flex items-center justify-center transition-all duration-200 hover:scale-110"
                                    title="Delete">
                                <i class="bi bi-trash-fill text-lg"></i>
                            </button>
                            @else
                            <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-900/30 text-gray-300 dark:text-gray-600 flex items-center justify-center" title="Cannot delete yourself">
                                <i class="bi bi-trash-fill text-lg"></i>
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <div class="w-24 h-24 bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 rounded-3xl flex items-center justify-center mb-4">
                                <i class="bi bi-people text-emerald-500 dark:text-emerald-400 text-5xl"></i>
                            </div>
                            <p class="text-gray-900 dark:text-white text-xl font-bold mb-2">No users found</p>
                            <p class="text-gray-500 dark:text-gray-400 mb-6">Get started by adding your first user</p>
                            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 transition-all duration-300 transform hover:scale-[1.02]">
                                <i class="bi bi-plus-lg"></i>
                                Add Your First User
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($users->hasPages())
<div class="mt-6 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm p-4 md:p-5">
    {{ $users->onEachSide(1)->links() }}
</div>
@endif
@endsection

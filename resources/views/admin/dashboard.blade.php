<x-layouts.admin>
    <div class="px-4 py-6 space-y-6">
        <!-- Welcome Section -->
        <div class="text-center py-4">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Admin Dashboard</h2>
            <p class="text-gray-600 text-sm">Monitor and manage the bus tracking system</p>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2v0a2 2 0 01-2-2v-2a2 2 0 00-2-2H8z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">5</p>
                        <p class="text-xs text-gray-500">Active Buses</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">42</p>
                        <p class="text-xs text-gray-500">Students Online</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Overview -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">System Overview</h3>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="text-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">12</p>
                    <p class="text-xs text-gray-500">Total Trips Today</p>
                </div>
                
                <div class="text-center">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">6</p>
                    <p class="text-xs text-gray-500">Scheduled Routes</p>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-3 border border-green-100">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-green-800 text-sm">System Status: Online</p>
                        <p class="text-green-600 text-xs">All services running normally</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="space-y-3">
            <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
            
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">System Initialized</p>
                        <p class="text-xs text-gray-500">Bus tracking system is ready for configuration</p>
                    </div>
                    <span class="text-xs text-gray-400">Just now</span>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2v0a2 2 0 01-2-2v-2a2 2 0 00-2-2H8z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">Database configured</p>
                        <p class="text-xs text-gray-500">SQLite database ready for bus data</p>
                    </div>
                    <span class="text-xs text-gray-400">5m ago</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="space-y-3">
            <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
            
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('admin.schedules') }}" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 rounded-2xl shadow-sm active:scale-95 transition-transform">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-white text-sm">Schedules</h4>
                        <p class="text-white/80 text-xs mt-1">Manage routes</p>
                    </div>
                </a>
                
                <a href="{{ route('admin.trips') }}" class="bg-gradient-to-r from-purple-500 to-purple-600 text-white p-4 rounded-2xl shadow-sm active:scale-95 transition-transform">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2v0a2 2 0 01-2-2v-2a2 2 0 00-2-2H8z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-white text-sm">Trips</h4>
                        <p class="text-white/80 text-xs mt-1">Monitor buses</p>
                    </div>
                </a>
                
                <a href="{{ route('admin.live-map') }}" class="bg-gradient-to-r from-green-500 to-green-600 text-white p-4 rounded-2xl shadow-sm active:scale-95 transition-transform">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-white text-sm">Live Map</h4>
                        <p class="text-white/80 text-xs mt-1">View locations</p>
                    </div>
                </a>
                
                <a href="{{ route('admin.reports') }}" class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-4 rounded-2xl shadow-sm active:scale-95 transition-transform">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-white text-sm">Reports</h4>
                        <p class="text-white/80 text-xs mt-1">View analytics</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- System Setup Info -->
        <div class="bg-gradient-to-r from-slate-50 to-blue-50 rounded-2xl p-6 border border-slate-200">
            <div class="text-center">
                <div class="w-12 h-12 bg-slate-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Next Development Phase</h3>
                <p class="text-sm text-gray-600 leading-relaxed mb-4">
                    The BUBT Bus Tracker system is ready for the next development phase. Database schema and core functionality will be implemented in upcoming tasks.
                </p>
                <div class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-xl text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Database Setup Coming Next
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
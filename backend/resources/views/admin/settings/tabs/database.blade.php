<div id="tab-database" class="tab-content hidden">
    <div class="space-y-6">
        <!-- Warning Notice -->
        <div class="bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 border-l-4 border-red-500 p-5 rounded-r-xl">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-red-500/30 flex-shrink-0">
                    <i class="bi bi-exclamation-triangle text-white text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-red-900 dark:text-red-100 text-lg flex items-center gap-2">
                        Warning: Destructive Operations
                    </h4>
                    <p class="text-red-700 dark:text-red-300 text-sm mt-1.5">
                        Actions taken here cannot be undone. Protected tables (users, settings, migrations, etc.) cannot be emptied. Please backup your data before performing any destructive operations.
                    </p>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-5 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                        <i class="bi bi-database text-white text-lg"></i>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase tracking-wide">Total Tables</p>
                        <p id="total-tables" class="text-2xl font-bold text-gray-900 dark:text-white">-</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-5 border border-emerald-200 dark:border-emerald-800">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/30">
                        <i class="bi bi-table text-white text-lg"></i>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase tracking-wide">Total Rows</p>
                        <p id="total-rows" class="text-2xl font-bold text-gray-900 dark:text-white">-</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-5 border border-purple-200 dark:border-purple-800">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/30">
                        <i class="bi bi-shield-check text-white text-lg"></i>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase tracking-wide">Protected</p>
                        <p id="protected-tables" class="text-2xl font-bold text-gray-900 dark:text-white">-</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tables List -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-list-ul text-blue-600 dark:text-blue-400"></i>
                        Database Tables
                    </h3>
                    <button onclick="loadDatabaseTables()" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white text-sm font-medium rounded-lg transition-all flex items-center gap-2">
                        <i class="bi bi-arrow-clockwise"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <div id="database-tables" class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[500px] overflow-y-auto">
                <!-- Loading State -->
                <div class="p-8 text-center">
                    <div class="inline-block w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                    <p class="text-gray-600 dark:text-gray-400">Loading database tables...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load tables when database tab is opened
        const dbTabBtn = document.querySelector('[data-tab="database"]');
        if (dbTabBtn) {
            dbTabBtn.addEventListener('click', function() {
                loadDatabaseTables();
            });
        }
    });

    async function loadDatabaseTables() {
        const container = document.getElementById('database-tables');

        try {
            const response = await fetch('{{ route('admin.settings.database.info') }}');
            const tables = await response.json();

            // Calculate stats
            const totalTables = tables.length;
            const totalRows = tables.reduce((sum, table) => sum + table.rows, 0);
            const protectedCount = tables.filter(t => t.protected).length;

            // Update stats
            document.getElementById('total-tables').textContent = totalTables;
            document.getElementById('total-rows').textContent = totalRows.toLocaleString();
            document.getElementById('protected-tables').textContent = protectedCount;

            // Render tables
            container.innerHTML = tables.map(table => `
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-all">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br ${getTableGradient(table.name)} rounded-xl flex items-center justify-center shadow-lg">
                                <i class="bi bi-table text-white text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    ${table.name}
                                    ${table.protected ? '<span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-xs font-medium rounded-full">Protected</span>' : ''}
                                </h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">${table.rows?.toLocaleString() || 0} rows</p>
                            </div>
                        </div>
                        ${!table.protected ? `
                            <button onclick="truncateTable('${table.name}')"
                                    class="px-4 py-2 bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 text-white text-sm font-medium rounded-lg transition-all flex items-center gap-2 shadow-lg shadow-red-500/30 hover:shadow-red-500/50">
                                <i class="bi bi-trash"></i>
                                Empty Table
                            </button>
                        ` : `
                            <span class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm font-medium rounded-lg flex items-center gap-2">
                                <i class="bi bi-shield-lock"></i>
                                Protected
                            </span>
                        `}
                    </div>
                </div>
            `).join('');

        } catch (error) {
            console.error('Error loading database tables:', error);
            container.innerHTML = `
                <div class="p-8 text-center">
                    <div class="inline-block w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-4">
                        <i class="bi bi-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                    <p class="text-red-600 dark:text-red-400 font-medium">Failed to load database tables</p>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">${error.message}</p>
                </div>
            `;
        }
    }

    function getTableGradient(tableName) {
        const gradients = {
            'users': 'from-purple-500 to-pink-500',
            'buses': 'from-blue-500 to-cyan-500',
            'routes': 'from-emerald-500 to-teal-500',
            'schedules': 'from-yellow-500 to-orange-500',
            'trips': 'from-red-500 to-rose-500',
            'settings': 'from-indigo-500 to-purple-500',
        };
        return gradients[tableName] || 'from-gray-500 to-gray-600';
    }

    async function truncateTable(tableName) {
        const rowCount = await getRowCount(tableName);

        showConfirmModal({
            title: `Empty "${tableName}" Table?`,
            message: `This will permanently delete all ${rowCount} rows from the "${tableName}" table. This action cannot be undone.`,
            icon: 'bi-exclamation-triangle',
            iconBgClass: 'bg-gradient-to-br from-red-500 to-orange-500',
            confirmBtnClass: 'bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 shadow-red-500/30 hover:shadow-red-500/50',
            confirmIcon: 'bi-trash',
            confirmText: 'Empty Table',
            onConfirm: async function() {
                try {
                    const response = await fetch('{{ route('admin.settings.database.truncate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ table: tableName })
                    });

                    const result = await response.json();

                    if (result.success) {
                        toastr.success(`Table "${tableName}" emptied successfully`);
                        loadDatabaseTables(); // Reload the list
                    } else {
                        toastr.error(result.error || 'Failed to empty table');
                    }
                } catch (error) {
                    console.error('Error emptying table:', error);
                    toastr.error('Error emptying table: ' + error.message);
                }
            }
        });
    }

    async function getRowCount(tableName) {
        try {
            const response = await fetch('{{ route('admin.settings.database.info') }}');
            const tables = await response.json();
            const table = tables.find(t => t.name === tableName);
            return table?.rows?.toLocaleString() || '?';
        } catch {
            return '?';
        }
    }
    </script>
</div>

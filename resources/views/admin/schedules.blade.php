<x-layouts.admin>
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-bold mb-2">Schedule Management</h1>
                <p class="text-lg text-base-content/70">Manage bus schedules for all routes</p>
            </div>
            <button class="btn btn-primary" onclick="toastr.info('Schedule CRUD will be implemented in later tasks')">
                Add Schedule
            </button>
        </div>
        
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title mb-4">Current Schedules</h2>
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Route</th>
                                <th>Direction</th>
                                <th>Departure Time</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-base-content/70">
                                    No schedules configured yet. Database schema will be created in the next task.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
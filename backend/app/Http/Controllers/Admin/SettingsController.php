<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\User;
use App\Services\DatabaseManagementService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService,
        private DatabaseManagementService $dbService
    ) {}

    /**
     * Display system settings.
     */
    public function index()
    {
        $stats = [
            'total_buses' => Bus::count(),
            'active_buses' => Bus::where('status', 'active')->count(),
            'total_routes' => Route::count(),
            'active_routes' => Route::where('is_active', true)->count(),
            'total_schedules' => Schedule::count(),
            'active_schedules' => Schedule::where('is_active', true)->count(),
            'total_users' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'drivers' => User::where('role', 'driver')->count(),
            'students' => User::where('role', 'student')->count(),
        ];

        $dbInfo = [
            'database' => config('database.connections.mysql.database'),
            'connection' => config('database.default'),
        ];

        // Get settings for the tabs
        $generalSettings = $this->settingsService->getGroup('general');
        $emailSettings = $this->settingsService->getGroup('email');

        return view('admin.settings.index', compact('stats', 'dbInfo', 'generalSettings', 'emailSettings'));
    }

    /**
     * Update general settings.
     */
    public function updateGeneral(Request $request)
    {
        $this->settingsService->updateBatch($request->except('_token'));

        return redirect()->back()
            ->with('toastr', [['type' => 'success', 'message' => 'General settings updated successfully.']]);
    }

    /**
     * Update email settings.
     */
    public function updateEmail(Request $request)
    {
        $this->settingsService->updateBatch($request->except('_token'));

        return redirect()->back()
            ->with('toastr', [['type' => 'success', 'message' => 'Email settings updated successfully.']]);
    }

    /**
     * Get database table information.
     */
    public function getDatabaseInfo()
    {
        $tables = $this->dbService->getAllTables();
        $tableInfo = [];

        foreach ($tables as $table) {
            $tableInfo[] = $this->dbService->getTableInfo($table);
        }

        return response()->json($tableInfo);
    }

    /**
     * Truncate a database table.
     */
    public function truncateTable(Request $request)
    {
        $request->validate(['table' => 'required|string|max:255']);

        $tableName = $request->input('table');

        if ($this->dbService->isProtectedTable($tableName)) {
            return response()->json(['error' => 'Cannot truncate protected table'], 403);
        }

        try {
            $this->dbService->truncateTable($tableName);

            return response()->json(['success' => true, 'message' => "Table '{$tableName}' truncated successfully"]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

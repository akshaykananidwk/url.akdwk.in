<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-click database export. Produces a portable SQL dump (MySQL) or a copy
 * of the SQLite file, streamed as a download — no shell access required.
 */
class BackupController extends Controller
{
    public function download()
    {
        AuditLog::record('backup.downloaded');
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $path = DB::connection()->getDatabaseName();
            abort_unless(is_file($path), 404);

            return response()->download($path, 'backup-' . now()->format('Y-m-d-His') . '.sqlite');
        }

        return response()->streamDownload(function () {
            echo "-- " . site_name() . " database backup\n-- Generated " . now()->toDateTimeString() . "\nSET FOREIGN_KEY_CHECKS=0;\n\n";
            foreach (Schema::getTableListing() as $table) {
                $create = DB::selectOne('SHOW CREATE TABLE `' . str_replace('`', '', $table) . '`');
                $createSql = ((array) $create)['Create Table'] ?? null;
                if (! $createSql) {
                    continue;
                }
                echo "DROP TABLE IF EXISTS `{$table}`;\n{$createSql};\n\n";

                DB::table($table)->orderByRaw('1')->chunk(500, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        $values = array_map(function ($v) {
                            if ($v === null) {
                                return 'NULL';
                            }
                            return DB::connection()->getPdo()->quote((string) $v);
                        }, (array) $row);
                        echo "INSERT INTO `{$table}` VALUES (" . implode(',', $values) . ");\n";
                    }
                });
                echo "\n";
            }
            echo "SET FOREIGN_KEY_CHECKS=1;\n";
        }, 'backup-' . now()->format('Y-m-d-His') . '.sql', ['Content-Type' => 'application/sql']);
    }
}

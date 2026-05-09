<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleAdmin;
use App\Models\ModuleUserAccess;
use Illuminate\Database\Seeder;

class DefaultModulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gantiGo = Module::updateOrCreate(
            ['slug' => 'ganti-go'],
            [
                'name' => 'Ganti Go',
                'icon' => 'GG',
                'route_prefix' => '/ganti-go',
                'description' => 'Semester-based class replacement management system.',
                'is_active' => true,
            ]
        );

        Module::updateOrCreate(
            ['slug' => 'passport-photo'],
            [
                'name' => 'Passport Photo System',
                'icon' => 'PP',
                'route_prefix' => null,
                'description' => 'Manage lecturer passport photo records.',
                'is_active' => true,
            ]
        );

        Module::updateOrCreate(
            ['slug' => 'photo-repository'],
            [
                'name' => 'Photo Repository',
                'icon' => 'PR',
                'route_prefix' => '/photo-repository',
                'description' => 'Centralized official portrait and profile photo repository.',
                'is_active' => true,
            ]
        );

        $retiredModule = Module::query()
            ->where('slug', 'class-replacement')
            ->first();

        if (! $retiredModule || $retiredModule->is($gantiGo)) {
            return;
        }

        ModuleUserAccess::query()
            ->where('module_id', $retiredModule->id)
            ->get()
            ->each(function (ModuleUserAccess $access) use ($gantiGo): void {
                $target = ModuleUserAccess::firstOrNew([
                    'user_id' => $access->user_id,
                    'module_id' => $gantiGo->id,
                ]);

                $target->fill([
                    'granted_by' => $target->granted_by ?? $access->granted_by,
                    'granted_at' => $target->granted_at ?? $access->granted_at,
                    'is_active' => (bool) $target->is_active || (bool) $access->is_active,
                ])->save();
            });

        ModuleAdmin::query()
            ->where('module_id', $retiredModule->id)
            ->get()
            ->each(function (ModuleAdmin $admin) use ($gantiGo): void {
                $target = ModuleAdmin::firstOrNew([
                    'user_id' => $admin->user_id,
                    'module_id' => $gantiGo->id,
                ]);

                $target->fill([
                    'assigned_by' => $target->assigned_by ?? $admin->assigned_by,
                    'assigned_at' => $target->assigned_at ?? $admin->assigned_at,
                    'is_active' => (bool) $target->is_active || (bool) $admin->is_active,
                ])->save();
            });

        $retiredModule->delete();
    }
}

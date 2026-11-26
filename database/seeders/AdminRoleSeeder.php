<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Database\Seeder;

class AdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Administrator',
                'slug' => 'super-administrator',
                'description' => 'Full access to every platform area.',
                'permissions' => ['*'],
                'is_system' => true,
            ],
            [
                'name' => 'Administrator',
                'slug' => 'administrator',
                'description' => 'Manage day-to-day platform operations.',
                'permissions' => AdminPermissions::all(),
                'is_system' => true,
            ],
            [
                'name' => 'Designer',
                'slug' => 'designer',
                'description' => 'Manage assets, templates, formats, fonts and palettes.',
                'permissions' => [
                    'dashboard.view', 'analytics.view', 'assets.view', 'assets.manage',
                    'templates.view', 'templates.manage', 'platforms.manage', 'catalog.manage',
                    'collections.manage', 'ai.view',
                ],
                'is_system' => false,
            ],
            [
                'name' => 'AI Manager',
                'slug' => 'ai-manager',
                'description' => 'Manage AI providers, prompts, generations, jobs and costs.',
                'permissions' => [
                    'dashboard.view', 'analytics.view', 'ai.view', 'ai.providers',
                    'ai.prompts', 'ai.generations', 'ai.jobs', 'ai.costs', 'templates.view',
                ],
                'is_system' => false,
            ],
            [
                'name' => 'Moderator',
                'slug' => 'moderator',
                'description' => 'Review users, designs, reports and content safety.',
                'permissions' => [
                    'dashboard.view', 'users.view', 'designs.view', 'designs.moderate',
                    'support.view', 'support.manage', 'content.view', 'activity.view',
                ],
                'is_system' => false,
            ],
            [
                'name' => 'Support Agent',
                'slug' => 'support-agent',
                'description' => 'Handle support tickets and user feedback.',
                'permissions' => [
                    'dashboard.view', 'support.view', 'support.manage', 'users.view',
                ],
                'is_system' => false,
            ],
        ];

        foreach ($roles as $role) {
            AdminRole::query()->updateOrCreate(['slug' => $role['slug']], $role);
        }

        $super = AdminRole::query()->where('slug', 'super-administrator')->first();
        if ($super !== null) {
            User::query()
                ->where('is_admin', true)
                ->whereNull('role_id')
                ->update(['role_id' => $super->id]);
        }
    }
}

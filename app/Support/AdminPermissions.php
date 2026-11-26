<?php

namespace App\Support;

class AdminPermissions
{
    /**
     * @return array<string, array{label: string, permissions: array<string, string>}>
     */
    public static function groups(): array
    {
        return [
            'overview' => [
                'label' => 'Overview',
                'permissions' => [
                    'dashboard.view' => 'View dashboard',
                    'analytics.view' => 'View analytics',
                ],
            ],
            'design' => [
                'label' => 'Design management',
                'permissions' => [
                    'assets.view' => 'View assets',
                    'assets.manage' => 'Manage assets',
                    'templates.view' => 'View templates',
                    'templates.manage' => 'Manage templates',
                    'platforms.manage' => 'Manage platforms and formats',
                    'catalog.manage' => 'Manage industries, categories, styles, fonts and palettes',
                    'collections.manage' => 'Manage template collections',
                ],
            ],
            'ai' => [
                'label' => 'AI management',
                'permissions' => [
                    'ai.view' => 'View AI operations',
                    'ai.providers' => 'Manage AI providers',
                    'ai.prompts' => 'Manage prompts',
                    'ai.generations' => 'Review AI generations',
                    'ai.jobs' => 'Manage generation jobs',
                    'ai.costs' => 'Manage AI pricing and costs',
                ],
            ],
            'users' => [
                'label' => 'User management',
                'permissions' => [
                    'users.view' => 'View users',
                    'users.manage' => 'Manage users and roles',
                    'designs.view' => 'View user designs',
                    'designs.moderate' => 'Moderate user designs',
                    'roles.manage' => 'Manage roles and permissions',
                    'activity.view' => 'View activity logs',
                ],
            ],
            'content' => [
                'label' => 'Content management',
                'permissions' => [
                    'content.view' => 'View website content',
                    'content.manage' => 'Manage website content and SEO',
                ],
            ],
            'support' => [
                'label' => 'Support and moderation',
                'permissions' => [
                    'support.view' => 'View support and reports',
                    'support.manage' => 'Manage support and moderation',
                ],
            ],
            'system' => [
                'label' => 'System',
                'permissions' => [
                    'settings.manage' => 'Manage platform settings',
                    'features.manage' => 'Manage feature availability',
                    'system.view' => 'View system health and queues',
                ],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $group) => array_keys($group['permissions']))
            ->values()
            ->all();
    }
}

<?php

namespace App\Services\Sidebar;

use App\Models\Menu;
use App\Services\Auth\RoutePermissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class SidebarService
{
    public static function getSidebarMenu()
    {
        $user = Auth::user();
        $currentRoute = Route::currentRouteName();

        $menus = Menu::with('children')
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        return $menus->filter(function ($menu) use ($user) {
            if ($menu->children->isNotEmpty()) {
                return $menu->children->contains(fn ($child) => self::checkMenuPermission($user, $child));
            }

            return self::checkMenuPermission($user, $menu);
        })->map(function ($menu) use ($user, $currentRoute) {
            $menu->children = $menu->children->filter(function ($child) use ($user) {
                return self::checkMenuPermission($user, $child);
            });

            $menu->is_active = self::checkActive($menu, $currentRoute);
            foreach ($menu->children as $child) {
                $child->is_active = self::checkActive($child, $currentRoute);
                if ($child->is_active) {
                    $menu->is_active = true;
                }
            }

            return $menu;
        });
    }

    private static function checkMenuPermission($user, $menu)
    {
        if (! $menu->is_active) {
            return false;
        }

        return $menu->route && RoutePermissionService::canAccess($user, $menu->route);
    }

    private static function checkActive($menu, $currentRoute)
    {
        if ($menu->route) {
            return $menu->route == $currentRoute;
        }

        if ($menu->url) {
            return request()->is($menu->url);
        }

        return false;
    }
}

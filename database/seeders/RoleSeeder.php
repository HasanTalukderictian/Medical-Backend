<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * php artisan db:seed --class=RoleSeeder
 * Bar bar chalaleo shomossha nai (updateOrCreate).
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::updateOrCreate(
            ['slug' => 'super_admin'],
            ['name' => 'Super Admin', 'permissions' => [], 'is_system' => true]
        );

        // Shuru te kaje lagte pare emon kichu role (pore panel theke bodlano jabe)
        $defaults = [
            ['name' => 'Manager', 'permissions' => ['dashboard', 'queries', 'treatment_plans', 'appointments', 'consultations', 'medicine_requests', 'bookings', 'visa', 'reports']],
            ['name' => 'Content Editor', 'permissions' => ['dashboard', 'home', 'medical_services', 'non_medical_services', 'hospitals', 'doctors', 'packages', 'company', 'media', 'career']],
            ['name' => 'Support Staff', 'permissions' => ['queries', 'appointments', 'messages']],
        ];

        foreach ($defaults as $r) {
            Role::firstOrCreate(
                ['slug' => Str::slug($r['name'], '_')],
                ['name' => $r['name'], 'permissions' => $r['permissions'], 'is_system' => false]
            );
        }

        // Purono users table e "role" column ("admin") thakle, tader Super Admin kore dey
        if (Schema::hasColumn('users', 'role')) {
            User::whereNull('role_id')
                ->whereIn('role', ['admin', 'super_admin'])
                ->update(['role_id' => $superAdmin->id]);
        }

        // Kono user er role na thakle, prothom user ke Super Admin kore dey
        if (! User::where('role_id', $superAdmin->id)->exists()) {
            $first = User::orderBy('id')->first();
            if ($first) {
                $first->update(['role_id' => $superAdmin->id, 'active' => true]);
            }
        }
    }
}

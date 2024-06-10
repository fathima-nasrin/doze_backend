<?php

namespace Marvel\Console;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Marvel\Database\Models\Settings;
use Spatie\Permission\Models\Permission;
use Marvel\Enums\Permission as UserPermission;
use Marvel\Enums\Role as UserRole;
use Spatie\Permission\Models\Role;
use Marvel\Database\Seeders\MarvelSeeder;
use Marvel\Database\Models\User; // Import User model
use PDO;
use PDOException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class InstallCommand extends Command
{
    protected $signature = 'marvel:install';

    protected $description = 'Installing Marvel Dependencies';

    public function handle()
    {
        $this->info('Installing Marvel Dependencies...');

        // Automatically answer 'yes' to migrate tables and seed dummy data questions
        $migrateTables = true;
        $seedDummyData = true;

        if ($migrateTables) {
            $this->info('Migrating Tables Now....');

            $this->call('migrate:fresh');

            $this->info('Tables Migration completed.');
        }

        if ($seedDummyData) {
            $this->call('marvel:seed');
            $this->call('db:seed', [
                '--class' => MarvelSeeder::class
            ]);

            $this->info('Importing required settings...');

            $this->call(
                'db:seed',
                [
                    '--class' => '\\Marvel\\Database\\Seeders\\SettingsSeeder',
                ]
            );

            $this->info('Settings import is completed.');
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => UserPermission::SUPER_ADMIN]);
        Permission::firstOrCreate(['name' => UserPermission::CUSTOMER]);
        Permission::firstOrCreate(['name' => UserPermission::STORE_OWNER]);
        Permission::firstOrCreate(['name' => UserPermission::STAFF]);

        $superAdminPermissions = [UserPermission::SUPER_ADMIN, UserPermission::STORE_OWNER, UserPermission::CUSTOMER];
        $storeOwnerPermissions = [UserPermission::STORE_OWNER, UserPermission::CUSTOMER];
        $staffPermissions = [UserPermission::STAFF, UserPermission::CUSTOMER];
        $customerPermissions = [UserPermission::CUSTOMER];

        Role::firstOrCreate(['name' => UserRole::SUPER_ADMIN])->syncPermissions($superAdminPermissions);
        Role::firstOrCreate(['name' => UserRole::STORE_OWNER])->syncPermissions($storeOwnerPermissions);
        Role::firstOrCreate(['name' => UserRole::STAFF])->syncPermissions($staffPermissions);
        Role::firstOrCreate(['name' => UserRole::CUSTOMER])->syncPermissions($customerPermissions);

        // Automatically create admin user with default email and password
        $this->createAdminUser();

        $this->call('marvel:copy-files');

        $this->modifySettingsData();

        $this->info('Everything is successful. Now clearing all cached...');

        $this->call('optimize:clear');

        $this->info('Thank You.');
    }

    private function modifySettingsData(): void
    {
        $language = isset(request()['language']) ? request()['language'] : DEFAULT_LANGUAGE;
        Cache::flush();
        $settings = Settings::getData($language);
        $settings->update([
            'options' => [
                ...$settings->options,
                'app_settings' => [
                    'last_checking_time' => Carbon::now(), // Update with current time
                    'trust' => true, // Assuming trust is always true now
                ]
            ]
        ]);
    }

    private function createAdminUser(): void
    {
        // Define default admin email and password
        $adminEmail = 'dozemode.cloud@gmail.com';
        $adminPassword = 'opklopkl';

        // Check if admin user already exists
        $admin = User::where('email', $adminEmail)->first();

        // If admin does not exist, create one
        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin',
                'email' => $adminEmail,
                'password' => bcrypt($adminPassword),
            ]);

            // Assign super admin role to the admin user
            $admin->assignRole(UserRole::SUPER_ADMIN);
        }
    }
}

<?php

namespace Iquesters\UserManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class BaseModuleSeeder extends Seeder
{
    protected string $moduleName;
    protected string $description;
    protected array $metas = [];

    public function run(): void
    {
        // Insert or update module
        DB::table('modules')->updateOrInsert(
            ['name' => $this->moduleName],
            [
                'uid' => (string) Str::ulid(),
                'description' => $this->description,
                'status' => 'active',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // Insert module meta if defined
        if (!empty($this->metas)) {
            $id = DB::table('modules')->where('name', $this->moduleName)->value('id');

            foreach ($this->metas as $key => $value) {
                DB::table('module_metas')->updateOrInsert(
                    ['ref_parent' => $id, 'meta_key' => $key],
                    [
                        'meta_value' => $value,
                        'status' => 'active',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
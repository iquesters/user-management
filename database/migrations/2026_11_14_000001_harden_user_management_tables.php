<?php

use Illuminate\Database\Migrations\Migration;
use Iquesters\Foundation\Database\SchemaHardening;

/**
 * Platform review D1, D2, D7:
 * - user_metas.meta_value VARCHAR(255) -> LONGTEXT (values were truncated)
 * - unique (ref_parent, meta_key) on meta tables
 * - created_by / updated_by as nullable foreign keys to users
 */
return new class extends Migration
{
    private array $metaTables = ['user_metas', 'otp_attempt_metas'];

    private array $auditTables = ['user_metas', 'otp_attempts', 'otp_attempt_metas'];

    public function up(): void
    {
        SchemaHardening::longText('user_metas', 'meta_value');

        foreach ($this->metaTables as $table) {
            SchemaHardening::uniqueMetaKeys($table);
        }

        foreach ($this->auditTables as $table) {
            SchemaHardening::auditForeignKeys($table);
        }
    }

    public function down(): void
    {
        foreach ($this->auditTables as $table) {
            SchemaHardening::dropAuditForeignKeys($table);
        }

        foreach ($this->metaTables as $table) {
            SchemaHardening::dropUniqueMetaKeys($table);
        }
    }
};

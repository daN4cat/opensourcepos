<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBoutiqueFieldsAndReports extends Migration
{
    public function up(): void
    {
        helper('migration');

        if (! $this->db->fieldExists('season', 'items')) {
            $this->forge->addColumn('items', [
                'season' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 25,
                    'null'       => false,
                    'default'    => '',
                    'after'      => 'category',
                ],
                'size' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 25,
                    'null'       => false,
                    'default'    => '',
                    'after'      => 'season',
                ],
                'color' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => false,
                    'default'    => '',
                    'after'      => 'size',
                ],
            ]);

            if (! indexExists('items', 'season', $this->db)) {
                $this->forge->addKey('season');
                $this->forge->processIndexes('items');
            }
        }

        if (! $this->db->fieldExists('season', 'sales_items')) {
            $this->forge->addColumn('sales_items', [
                'season' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 25,
                    'null'       => false,
                    'default'    => '',
                    'after'      => 'item_id',
                ],
            ]);
        }

        $this->db->table('permissions')->ignore(true)->insertBatch([
            [
                'permission_id' => 'reports_cost_sales',
                'module_id'     => 'reports',
            ],
            [
                'permission_id' => 'reports_cost_sales_by_supplier',
                'module_id'     => 'reports',
            ],
        ]);

        $this->db->table('grants')->ignore(true)->insertBatch([
            [
                'permission_id' => 'reports_cost_sales',
                'person_id'     => 1,
                'menu_group'    => '--',
            ],
            [
                'permission_id' => 'reports_cost_sales_by_supplier',
                'person_id'     => 1,
                'menu_group'    => '--',
            ],
        ]);

        $this->db->resetDataCache();
    }

    public function down(): void
    {
        helper('migration');

        $this->db->table('grants')
            ->whereIn('permission_id', ['reports_cost_sales', 'reports_cost_sales_by_supplier'])
            ->delete();

        $this->db->table('permissions')
            ->whereIn('permission_id', ['reports_cost_sales', 'reports_cost_sales_by_supplier'])
            ->delete();

        dropColumnIfExists('sales_items', 'season');
        dropColumnIfExists('items', 'color');
        dropColumnIfExists('items', 'size');
        dropColumnIfExists('items', 'season');

        $this->db->resetDataCache();
    }
}

<?php

namespace App\Models\Reports;

use Config\OSPOS;

class Summary_cost_sales_by_supplier extends Summary_report
{
    protected function _get_data_columns(): array
    {
        return [
            ['season' => lang('Reports.season'), 'sortable' => false],
            ['supplier' => lang('Reports.supplier')],
            ['stock_cost' => lang('Reports.stock_cost'), 'sorter' => 'number_sorter'],
            ['cost_of_sale' => lang('Reports.cost_of_sale'), 'sorter' => 'number_sorter'],
            ['total_cost' => lang('Reports.total_cost'), 'sorter' => 'number_sorter'],
            ['revenue' => lang('Reports.revenue'), 'sorter' => 'number_sorter'],
            ['mark_up' => lang('Reports.mark_up')],
        ];
    }

    public function getData(array $inputs): array
    {
        $config = config(OSPOS::class)->settings;
        $where  = '';

        if (empty($config['date_or_time_format'])) {
            $where .= 'DATE(sales.sale_time) BETWEEN ' . $this->db->escape($inputs['start_date']) . ' AND ' . $this->db->escape($inputs['end_date']);
        } else {
            $where .= 'sales.sale_time BETWEEN ' . $this->db->escape(rawurldecode($inputs['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($inputs['end_date']));
        }

        $query = $this->db->query('
            SELECT
                Stock.season,
                Stock.supplier,
                Stock.stock_cost,
                IFNULL(Sales.cost_of_sale, 0) AS cost_of_sale,
                Stock.stock_cost + IFNULL(Sales.cost_of_sale, 0) AS total_cost,
                IFNULL(Sales.revenue, 0) AS revenue,
                IFNULL(Sales.revenue / NULLIF(Stock.stock_cost + IFNULL(Sales.cost_of_sale, 0), 0), 0) AS mark_up
            FROM
                (SELECT season, MAX(suppliers.company_name) AS supplier, SUM(cost_price * quantities.quantity) AS stock_cost
                FROM ' . $this->db->prefixTable('items') . ' AS items
                JOIN ' . $this->db->prefixTable('item_quantities') . ' AS quantities ON items.item_id = quantities.item_id
                JOIN ' . $this->db->prefixTable('suppliers') . ' AS suppliers ON items.supplier_id = suppliers.person_id
                WHERE items.season = ' . $this->db->escape($inputs['season']) . '
                GROUP BY season, items.supplier_id
                ORDER BY supplier ASC) AS Stock
            LEFT JOIN
                (SELECT sales_items.season, MAX(suppliers.company_name) AS supplier, SUM(quantity_purchased * item_cost_price) AS cost_of_sale, SUM(quantity_purchased * item_unit_price * (100 - discount) / 100) AS revenue
                FROM ' . $this->db->prefixTable('sales_items') . ' AS sales_items
                JOIN ' . $this->db->prefixTable('sales') . ' AS sales ON sales.sale_id = sales_items.sale_id
                JOIN ' . $this->db->prefixTable('items') . ' AS items ON sales_items.item_id = items.item_id
                JOIN ' . $this->db->prefixTable('suppliers') . ' AS suppliers ON items.supplier_id = suppliers.person_id
                WHERE ' . $where . ' AND sales_items.season = ' . $this->db->escape($inputs['season']) . '
                GROUP BY sales_items.season, items.supplier_id
                ORDER BY supplier ASC) AS Sales
            ON
                Stock.season = Sales.season AND Stock.supplier = Sales.supplier
        ');

        return $query->getResultArray();
    }
}

<?php

namespace App\Models\Reports;

use Config\OSPOS;

class Summary_cost_sales extends Summary_report
{
    protected function _get_data_columns(): array
    {
        return [
            ['season' => lang('Reports.season')],
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
                Stock.stock_cost,
                IFNULL(Sales.cost_of_sale, 0) AS cost_of_sale,
                Stock.stock_cost + IFNULL(Sales.cost_of_sale, 0) AS total_cost,
                IFNULL(Sales.revenue, 0) AS revenue,
                IFNULL(Sales.revenue / NULLIF(Stock.stock_cost + IFNULL(Sales.cost_of_sale, 0), 0), 0) AS mark_up
            FROM
                (SELECT season, SUM(cost_price * quantities.quantity) AS stock_cost
                FROM ' . $this->db->prefixTable('items') . ' AS items
                JOIN ' . $this->db->prefixTable('item_quantities') . ' AS quantities
                WHERE items.item_id = quantities.item_id
                GROUP BY season) AS Stock
            LEFT JOIN
                (SELECT season, SUM(quantity_purchased * item_cost_price) AS cost_of_sale, SUM(quantity_purchased * item_unit_price * (100 - discount) / 100) AS revenue
                FROM ' . $this->db->prefixTable('sales_items') . ' AS items
                JOIN ' . $this->db->prefixTable('sales') . ' AS sales
                WHERE sales.sale_id = items.sale_id AND ' . $where . '
                GROUP BY season) AS Sales
            ON
                Stock.season = Sales.season
        ');

        return $query->getResultArray();
    }
}

<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("Summary_report.php");

class Summary_cost_sales extends Summary_report
{
	protected function _get_data_columns()
	{
		return array(
			array('season' => $this->lang->line('reports_season')),
			array('stock_cost' => $this->lang->line('reports_stock_cost'), 'sorter' => 'number_sorter'),
			array('cost_of_sale' => $this->lang->line('reports_cost_of_sale'), 'sorter' => 'number_sorter'),
            array('total_cost' => $this->lang->line('reports_total_cost'), 'sorter' => 'number_sorter'),
            array('revenue' => $this->lang->line('reports_revenue'), 'sorter' => 'number_sorter'),
            array('mark_up' => $this->lang->line('reports_mark_up')));
    }

	public function getData(array $inputs)
	{
		$where = '';

		if(empty($this->config->item('date_or_time_format')))
		{
			$where .= 'DATE(sales.sale_time) BETWEEN ' . $this->db->escape($inputs['start_date']) . ' AND ' . $this->db->escape($inputs['end_date']);
		}
		else
		{
			$where .= 'sales.sale_time BETWEEN ' . $this->db->escape(rawurldecode($inputs['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($inputs['end_date']));
		}

		$query = $this->db->query('
            SELECT
                Stock.season,
                Stock.stock_cost,
                Sales.cost_of_sale,
                Stock.stock_cost + Sales.cost_of_sale AS total_cost,
                Sales.revenue,
                IFNULL(Sales.revenue / (Stock.stock_cost + Sales.cost_of_sale), 0) AS mark_up
            FROM
                (SELECT season, SUM(cost_price * quantities.quantity) AS stock_cost
                FROM ' . $this->db->dbprefix('items') . ' AS items
                JOIN ' . $this->db->dbprefix('item_quantities') . ' AS quantities
                WHERE items.item_id = quantities.item_id
                GROUP BY season) AS Stock
            LEFT JOIN
                (SELECT season, SUM(quantity_purchased * item_cost_price) AS cost_of_sale, SUM(quantity_purchased * item_unit_price * (100 - discount) / 100) AS revenue
                FROM ' . $this->db->dbprefix('sales_items') . ' AS items
                JOIN ' . $this->db->dbprefix('sales') . ' AS sales
                WHERE sales.sale_id = items.sale_id AND ' . $where . '
                GROUP BY season) AS Sales
            ON
                Stock.season = Sales.season
        ');

		return $query->result_array();
	}
}
?>

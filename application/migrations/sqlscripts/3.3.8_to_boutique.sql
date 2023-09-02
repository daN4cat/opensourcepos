INSERT INTO `ospos_permissions` (`permission_id`, `module_id`) VALUES
('reports_cost_sales', 'reports'),
('reports_cost_sales_by_supplier', 'reports');

INSERT INTO `ospos_grants` (`permission_id`, `person_id`, `menu_group`) VALUES
('reports_cost_sales', 1, '--'),
('reports_cost_sales_by_supplier', 1, '--');
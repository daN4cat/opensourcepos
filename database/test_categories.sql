INSERT INTO `ospos`.`ospos_items_categories` (`category_id`, `category_name`, `category_short_name`, `supplier_id`, `deleted`) VALUES (NULL, 'Fabric', 'FA', NULL, '0'); 
INSERT INTO `ospos`.`ospos_item_units` (`unit_id`, `unit_name`, `deleted`) VALUES (NULL, 'm', '0'), (NULL, 'kg', '0'); 
INSERT INTO `ospos`.`ospos_items_units_categories` (`category_id`, `unit_id`, `inventory_check`, `unit_conversion`) VALUES ('2', '2', '0', '1'), ('2', '3', '1', '1'); 

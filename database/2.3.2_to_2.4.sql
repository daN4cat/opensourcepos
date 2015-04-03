
-- add item size and category id's
ALTER TABLE `ospos_items` 
   ADD COLUMN `category_id` int(10) DEFAULT NULL AFTER `name`,
   ADD COLUMN `size_id` int(10) DEFAULT NULL AFTER `pic_id`;

CREATE TABLE IF NOT EXISTS `ospos_items_categories` (
  `category_id` int(10) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(16) NOT NULL,
  `category_short_name` varchar(8) DEFAULT NULL,
  `supplier_id` int(10) DEFAULT NULL,
  `deleted` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`category_id`),
  KEY `fk_suppliers` (`supplier_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ospos_items_sizes` (
  `size_id` int(10) NOT NULL AUTO_INCREMENT,
  `size_name` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`size_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ospos_items_sizes_categories` (
  `category_id` int(10) NOT NULL,
  `size_id` int(10) NOT NULL,
  PRIMARY KEY (`category_id`, `size_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

ALTER TABLE `ospos_items_categories`
  ADD CONSTRAINT `ospos_items_categories_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `ospos_suppliers` (`person_id`);

ALTER TABLE `ospos_items_sizes`
  ADD CONSTRAINT `ospos_items_sizes_ibfk_1` FOREIGN KEY (`item_size_category_id`) REFERENCES `ospos_items_sizes_categories` (`id`);

ALTER TABLE `ospos_items`
  ADD CONSTRAINT `ospos_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `ospos_items_categories` (`id`),
  ADD CONSTRAINT `ospos_items_ibfk_3` FOREIGN KEY (`size_id`) REFERENCES `ospos_items_sizes` (`id`);
  
ALTER TABLE `ospos_inventory`
  ADD CONSTRAINT `ospos_inventory_ibfk_4` FOREIGN KEY (`trans_unit`) REFERENCES `ospos_item_units` (`unit_id`);

CREATE TABLE IF NOT EXISTS `ospos_item_units` (
  `unit_id` int(10) NOT NULL AUTO_INCREMENT,
  `unit_name` varchar(8) NOT NULL,
  `deleted` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`unit_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8  ;

INSERT INTO `ospos_app_config` (`key`, `value`) VALUES 
('barcode_separator', '@'),
('thousands_separator', ''),
('decimal_point', '');

-- insert default item unit
INSERT INTO `ospos_item_units` (`unit_id`, `unit_name`) VALUES (1, '');

CREATE TABLE `ospos_items_sizes_categories` (
  `category_id` int(10) NOT NULL,
  `size_id` int(10) NOT NULL,
  PRIMARY KEY (`category_id`, `size_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


CREATE TABLE `ospos_items_units_categories` (
  `category_id` int(10) NOT NULL,
  `unit_id` int(10) NOT NULL,
  `unit_conversion` int(1) DEFAULT '0',
  PRIMARY KEY (`category_id`, `unit_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

INSERT INTO `ospos_items_units_categories` (`category_id`, `unit_id`) VALUES (1, 1);

ALTER TABLE `ospos_item_quantities` 
  ADD COLUMN `unit_id` int(10) NOT NULL,
  ADD COLUMN `conversion_rate` decimal(15,3) DEFAULT NULL,
  ADD COLUMN `conversion_margin` int(8) DEFAULT NULL,
  ADD COLUMN `initial_quantity` decimal(15,2) DEFAULT NULL,
  MODIFY quantity decimal(15,2) DEFAULT 0.00; 
  
ALTER TABLE `ospos_sales_items`
  ADD COLUMN `unit_id` int(10) NOT NULL,
  ADD KEY `unit_id` (`unit_id`),
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`sale_id`,`item_id`,`line`,`unit_id`);
  
ALTER TABLE `ospos_receivings_items`
  ADD COLUMN `unit_id` int(10) NOT NULL,
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `item_location` (`item_location`),
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`receiving_id`,`item_id`,`line`,`unit_id`);
  
ALTER TABLE `ospos_sales_suspended_items`
  ADD COLUMN `unit_id` int(10) NOT NULL,
  ADD KEY `unit_id` (`unit_id`);  
    
ALTER TABLE `ospos_item_kit_items`
  ADD COLUMN `unit_id` int(10) NOT NULL,
  ADD KEY `unit_id` (`unit_id`); 
  
ALTER TABLE `ospos_items_sizes_categories`
  ADD CONSTRAINT `ospos_items_sizes_categories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `ospos_items_categories` (`category_id`),
  ADD CONSTRAINT `ospos_items_sizes_categories_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `ospos_items_sizes` (`size_id`);
 
ALTER TABLE `ospos_items_units_categories`
  ADD CONSTRAINT `ospos_items_units_categories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `ospos_items_categories` (`category_id`),
  ADD CONSTRAINT `ospos_items_units_categories_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `ospos_item_units` (`unit_id`);
  
ALTER TABLE `ospos_item_quantities`
  ADD CONSTRAINT `ospos_item_quantities_ibfk_3` FOREIGN KEY (`unit_id`) REFERENCES `ospos_item_units` (`unit_id`);

ALTER TABLE `ospos_sales_items`
  ADD CONSTRAINT `ospos_sales_items_ibfk_4` FOREIGN KEY (`unit_id`) REFERENCES `ospos_unit_items` (`unit_id`);

ALTER TABLE `ospos_receivings_items`
  ADD CONSTRAINT `ospos_receivings_items_ibfk_3` FOREIGN KEY (`item_location`) REFERENCES `ospos_stock_locations` (`location_id`),
  ADD CONSTRAINT `ospos_receivings_items_ibfk_4` FOREIGN KEY (`unit_id`) REFERENCES `ospos_unit_items` (`unit_id`);

ALTER TABLE `ospos_item_kit_items`
  ADD CONSTRAINT `ospos_item_kit_items_ibfk_3` FOREIGN KEY (`unit_id`) REFERENCES `ospos_item_units` (`unit_id`);
  
ALTER TABLE `ospos_sales_suspended_items`
  ADD CONSTRAINT `ospos_sales_suspended_items_ibfk_4` FOREIGN KEY (`unit_id`) REFERENCES `ospos_unit_items` (`unit_id`);

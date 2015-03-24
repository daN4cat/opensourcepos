
-- add item size and category id's
ALTER TABLE `ospos_items` 
   ADD COLUMN `category_id` int(10) DEFAULT NULL AFTER `name`,
   ADD COLUMN `size_id` int(10) DEFAULT NULL AFTER `pic_id`;

ALTER TABLE `ospos_item_quantities` CHANGE `quantity` `quantity` DECIMAL(15,2) NULL DEFAULT '0.00'; 

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
  `exact` int(1) DEFAULT '0',
  PRIMARY KEY (`category_id`, `unit_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

INSERT INTO `ospos_items_units_categories` (`category_id`, `unit_id`) VALUES (1, 1);

ALTER TABLE `ospos_item_quantities` 
  ADD COLUMN `unit_id` int(10) NOT NULL DEFAULT '1',
  ADD COLUMN `initial_quantity` decimal(15,2) NOT NULL DEFAULT '0',
  ADD_COLUMN `margin` int(8) NOT NULL,
  MODIFY quantity decimal(15,2); 
  
ALTER TABLE `ospos_items_sizes_categories`
  ADD CONSTRAINT `ospos_items_sizes_categories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `ospos_items_categories` (`category_id`),
  ADD CONSTRAINT `ospos_items_sizes_categories_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `ospos_items_sizes` (`size_id`);
 
ALTER TABLE `ospos_items_units_categories`
  ADD CONSTRAINT `ospos_items_units_categories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `ospos_items_categories` (`category_id`),
  ADD CONSTRAINT `ospos_items_units_categories_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `ospos_item_units` (`unit_id`);

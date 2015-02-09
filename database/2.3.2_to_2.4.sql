
-- add item_pic column to items table
ALTER TABLE `ospos_items` 
   ADD COLUMN `item_category_id` int(10) DEFAULT NULL AFTER `name`,
   ADD COLUMN `item_size_id` int(10) DEFAULT NULL AFTER `pic_id`;


CREATE TABLE IF NOT EXISTS `ospos_items_categories` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `description` text NOT NULL,
  `abbreviation` varchar(32) DEFAULT NULL,
  `item_size_category_id` int(10) DEFAULT NULL,
  `supplier_id` int(10) DEFAULT NULL,
  `deleted` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_items_sizes_categories` (`item_size_category_id`),
  KEY `fk_suppliers` (`supplier_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `ospos_items_sizes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `size` varchar(255) DEFAULT NULL,
  `item_size_category_id` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_items_sizes_categories` (`item_size_category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ospos_items_sizes_categories` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `abbreviation` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `ospos_items_categories`
  ADD CONSTRAINT `ospos_items_categories_ibfk_2` FOREIGN KEY (`item_size_category_id`) REFERENCES `ospos_items_sizes_categories` (`id`),
  ADD CONSTRAINT `ospos_items_categories_ibfk_3` FOREIGN KEY (`supplier_id`) REFERENCES `ospos_suppliers` (`person_id`);

ALTER TABLE `ospos_items_sizes`
  ADD CONSTRAINT `ospos_items_sizes_ibfk_1` FOREIGN KEY (`item_size_category_id`) REFERENCES `ospos_items_sizes_categories` (`id`);

ALTER TABLE `ospos_items`
  ADD CONSTRAINT `ospos_items_ibfk_2` FOREIGN KEY (`item_category_id`) REFERENCES `ospos_items_categories` (`id`),
  ADD CONSTRAINT `ospos_items_ibfk_3` FOREIGN KEY (`item_size_id`) REFERENCES `ospos_items_sizes` (`id`);
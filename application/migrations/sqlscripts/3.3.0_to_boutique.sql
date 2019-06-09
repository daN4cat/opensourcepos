--
-- Add fashion boutique
--

ALTER TABLE `ospos_items`
  ADD COLUMN `season` varchar(25) NOT NULL AFTER `category`,
  ADD COLUMN `size` varchar(25) NOT NULL AFTER `season`,
  ADD COLUMN `color` varchar(50) NOT NULL AFTER `size`;

ALTER TABLE `ospos_sales_items`
  ADD COLUMN `season` varchar(25) NOT NULL AFTER `item_id`;

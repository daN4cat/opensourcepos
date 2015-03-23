ALTER TABLE ospos_sales_suspended_items MODIFY quantity_purchased decimal(15,2);
ALTER TABLE ospos_item_quantities MODIFY quantity decimal(15,2);
ALTER TABLE ospos_items MODIFY reorder_level decimal(15,2);
ALTER TABLE ospos_item_kit_items MODIFY quantity decimal(15,2); 

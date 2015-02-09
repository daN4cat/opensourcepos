INSERT INTO ospos_categories (description) (SELECT DISTINCT category FROM ospos_items WHERE deleted = 0);
UPDATE ospos_items items SET category_id = (SELECT category_id FROM ospos_categories categories WHERE categories.description = items.category WHERE deleted = 0);
ALTER TABLE ospos_items DROP COLUMN category;

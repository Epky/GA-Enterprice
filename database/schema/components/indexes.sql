CREATE INDEX idx_orders_customer_id ON orders (customer_id);
CREATE INDEX idx_inventory_logs_product_id ON inventory_logs (product_id);
CREATE INDEX idx_customers_email ON customers (email);
CREATE INDEX idx_admins_username ON admins (username);
CREATE INDEX idx_orders_created_at ON orders (created_at);
CREATE INDEX idx_contact_messages_is_read ON contact_messages (is_read);
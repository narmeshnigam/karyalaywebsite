-- Add performance optimization indexes
-- This migration adds composite indexes for common query patterns

-- Subscriptions: Filter by customer and status (common in customer portal)
CREATE INDEX idx_subscriptions_customer_status ON subscriptions(customer_id, status);

-- Subscriptions: Filter by status and end date (common for expiration checks)
CREATE INDEX idx_subscriptions_status_end_date ON subscriptions(status, end_date);

-- Orders: Filter by customer and status (common in billing history)
CREATE INDEX idx_orders_customer_status ON orders(customer_id, status);

-- Orders: Filter by status and created date (common in admin reports)
CREATE INDEX idx_orders_status_created ON orders(status, created_at);

-- Ports: Filter by plan and status (common for port allocation)
CREATE INDEX idx_ports_plan_status ON ports(plan_id, status);

-- Tickets: Filter by customer and status (common in support portal)
CREATE INDEX idx_tickets_customer_status ON tickets(customer_id, status);

-- Tickets: Filter by status and assigned user (common for admin ticket management)
CREATE INDEX idx_tickets_status_assigned ON tickets(status, assigned_to);

-- Ticket Messages: Filter by ticket and created date (for chronological ordering)
CREATE INDEX idx_ticket_messages_ticket_created ON ticket_messages(ticket_id, created_at);

-- Blog Posts: Filter by status and published date (common for public blog)
CREATE INDEX idx_blog_posts_status_published ON blog_posts(status, published_at);

-- Leads: Filter by status and created date (common for lead management)
CREATE INDEX idx_leads_status_created ON leads(status, created_at);

-- Port Allocation Logs: Filter by subscription (common for audit trail)
CREATE INDEX idx_port_allocation_logs_subscription ON port_allocation_logs(subscription_id);

-- Port Allocation Logs: Filter by port (common for port history)
CREATE INDEX idx_port_allocation_logs_port ON port_allocation_logs(port_id);

-- Modules: Filter by status and display order (common for public display)
CREATE INDEX idx_modules_status_order ON modules(status, display_order);

-- Features: Filter by status and display order (common for public display)
CREATE INDEX idx_features_status_order ON features(status, display_order);

-- Case Studies: Filter by status (common for public display)
-- Already has idx_status, no additional index needed

-- Media Assets: Filter by uploaded user and created date (common for media library)
CREATE INDEX idx_media_assets_uploader_created ON media_assets(uploaded_by, created_at);

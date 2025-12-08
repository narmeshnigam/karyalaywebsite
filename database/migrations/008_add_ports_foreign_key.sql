-- Add foreign key constraint from ports to subscriptions
-- This is done after subscriptions table is created
ALTER TABLE ports
ADD CONSTRAINT fk_ports_subscription
FOREIGN KEY (assigned_subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL;

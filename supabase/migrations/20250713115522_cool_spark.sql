-- Fix payment_method column size issue
-- This script updates the payments table to accommodate longer payment method names

-- Update the payment_method column to allow longer values
ALTER TABLE payments MODIFY COLUMN payment_method ENUM(
    'crypto', 
    'binance', 
    'plisio', 
    'balance', 
    'manual', 
    'system',
    'bank_transfer',
    'paypal',
    'stripe'
) NOT NULL DEFAULT 'crypto';

-- Also update the withdrawal_requests table if it exists
ALTER TABLE withdrawal_requests MODIFY COLUMN withdrawal_method ENUM(
    'crypto', 
    'binance', 
    'plisio',
    'bank_transfer',
    'paypal'
) NOT NULL DEFAULT 'crypto';

-- Update any existing records that might have truncated values
UPDATE payments SET payment_method = 'plisio' WHERE payment_method = 'plisi' OR payment_method = 'plis';
UPDATE payments SET payment_method = 'binance' WHERE payment_method = 'binan' OR payment_method = 'bina';

-- Add indexes for better performance
CREATE INDEX idx_payments_method_status ON payments(payment_method, status);
CREATE INDEX idx_payments_user_type ON payments(user_id, type);
-- Sentinel Cameroon — Monetization Migration
-- Run this to add monetization tables and columns

-- 1. Add sponsor tier columns to partners table
ALTER TABLE partners 
  ADD COLUMN IF NOT EXISTS sponsor_tier ENUM('gold','silver','listed') DEFAULT 'listed',
  ADD COLUMN IF NOT EXISTS is_sponsored TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS monthly_fee INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS sponsor_expires_at DATE NULL,
  ADD COLUMN IF NOT EXISTS website VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS police TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS fire TINYINT(1) DEFAULT 0;

-- 2. Partner sponsorships ledger
CREATE TABLE IF NOT EXISTS partner_sponsorships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    tier ENUM('gold','silver','listed') NOT NULL DEFAULT 'listed',
    amount_fcfa INT NOT NULL DEFAULT 0,
    payment_method VARCHAR(50) DEFAULT 'mobile_money',
    payment_reference VARCHAR(100),
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    recorded_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Add payment columns to marketplace_listings
ALTER TABLE marketplace_listings
  ADD COLUMN IF NOT EXISTS is_paid TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS listing_fee INT DEFAULT 500,
  ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS payment_phone VARCHAR(20) NULL;

-- 4. Marketplace payments ledger
CREATE TABLE IF NOT EXISTS marketplace_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    user_id INT NOT NULL,
    amount_fcfa INT NOT NULL DEFAULT 500,
    payment_phone VARCHAR(20),
    payment_reference VARCHAR(100),
    status ENUM('pending','confirmed','failed') DEFAULT 'pending',
    confirmed_by INT NULL,
    confirmed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Subscription plans reference table
CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    tier ENUM('gold','silver','listed') NOT NULL,
    price_fcfa INT NOT NULL,
    features TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default plans
INSERT INTO subscription_plans (name, tier, price_fcfa, features) VALUES
('Gold Partner', 'gold', 50000, 'Top placement, Gold badge, Priority calls, Featured on homepage'),
('Silver Partner', 'silver', 25000, 'Priority mid-section, Silver badge, Highlighted card'),
('Free Listing', 'listed', 0, 'Basic listing at bottom of partners page')
ON DUPLICATE KEY UPDATE price_fcfa = VALUES(price_fcfa);

-- Community & Marketplace Expansion Migration
-- Sentinel Cameroon

-- 1. Communities Table
CREATE TABLE IF NOT EXISTS communities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    creator_id INT NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. Community Members Table
CREATE TABLE IF NOT EXISTS community_members (
    community_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('member', 'moderator', 'admin') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (community_id, user_id),
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Community Messages (Chat) Table
CREATE TABLE IF NOT EXISTS community_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    community_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Marketplace Items Table
CREATE TABLE IF NOT EXISTS marketplace_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    category ENUM('Emergency Gear', 'Security Services', 'First Aid', 'Technology', 'Other') DEFAULT 'Other',
    image_path VARCHAR(255),
    status ENUM('active', 'sold', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexing for Chat Performance
CREATE INDEX idx_chat_community ON community_messages(community_id);
CREATE INDEX idx_chat_created ON community_messages(created_at);

-- Insert some default/seed data for testing
-- Community
-- INSERT INTO communities (name, description, creator_id) VALUES ('Yaoundé Safety Watch', 'A community group for residents of Yaoundé to discuss local security and coordinate responders.', 1);
-- Marketplace item
-- INSERT INTO marketplace_items (seller_id, title, description, price, category) VALUES (1, 'Professional Trauma Kit', 'Advanced first aid kit designed for tactical and emergency use.', 45000.00, 'First Aid');

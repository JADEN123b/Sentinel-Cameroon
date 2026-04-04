-- Advanced Chat & Community Management Migration
-- Sentinel Cameroon

-- 1. Enhance Community Messages (Pinning & Deletion)
ALTER TABLE community_messages 
ADD COLUMN is_pinned BOOLEAN DEFAULT FALSE,
ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE;

-- 2. Enhance Communities (Profile Image & Metadata)
ALTER TABLE communities 
ADD COLUMN profile_picture VARCHAR(255) AFTER name,
ADD COLUMN notification_type ENUM('all', 'mentions', 'none') DEFAULT 'all';

-- 3. Reset any previous test data for testing
-- UPDATE community_messages SET is_pinned = 0;

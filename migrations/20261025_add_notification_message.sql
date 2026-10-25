ALTER TABLE notifications
ADD COLUMN message VARCHAR(255) NULL AFTER sent_at;

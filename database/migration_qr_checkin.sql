-- ── Migration: QR Check-in / Check-out support ──
-- Run this once against your existing database

-- 1. Add check-in and check-out timestamps to Reservation
ALTER TABLE Reservation
    ADD CheckedInAt DATETIME NULL,
        CheckedOutAt DATETIME NULL;

-- 2. Make sure QR_Token column exists (already in schema, but safe to run)
-- ALTER TABLE Reservation ADD QR_Token VARCHAR(255) NULL;  -- skip if already exists

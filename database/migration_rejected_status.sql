-- Migration: Add 'rejected' to Member Status values
-- Run this once on your existing database

ALTER TABLE Member
ALTER COLUMN Status VARCHAR(10) NOT NULL;

ALTER TABLE Member
ADD CONSTRAINT CK_Member_Status CHECK (Status IN ('active', 'suspended', 'rejected'));

ALTER TABLE Member
ADD CONSTRAINT DF_Member_Status DEFAULT 'active' FOR Status;
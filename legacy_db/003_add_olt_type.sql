-- Migration 003: Add olt_type column to olts table
-- Supports dynamic OLT type selection (GPON/EPON) during registration

-- Add olt_type column after olt_model
ALTER TABLE `olts` ADD COLUMN `olt_type` ENUM('GPON','EPON') NOT NULL DEFAULT 'GPON' AFTER `olt_model`;

-- Update existing port labels and types to match OLT type (existing data defaults to GPON)

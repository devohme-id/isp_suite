-- 002_add_olt_port_to_backbone_cores.sql
-- Add OLT port mapping at the backbone core level for flexible field topologies

ALTER TABLE `backbone_cores` ADD COLUMN `olt_port_id` INT(11) DEFAULT NULL;
ALTER TABLE `backbone_cores` ADD CONSTRAINT `fk_backbone_cores_olt_port` FOREIGN KEY (`olt_port_id`) REFERENCES `olt_ports` (`id`) ON DELETE SET NULL;

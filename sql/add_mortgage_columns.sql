ALTER TABLE `application_recovery_mortgage`
ADD COLUMN `securedExpectedClaim` DECIMAL(15,2) DEFAULT 0.00 AFTER `max_claim`,
ADD COLUMN `unsecuredRemainingClaim` DECIMAL(15,2) DEFAULT 0.00 AFTER `securedExpectedClaim`,
ADD COLUMN `rehabilitationSecuredClaim` DECIMAL(15,2) DEFAULT 0.00 AFTER `unsecuredRemainingClaim`;

ALTER TABLE `application_recovery_mortgage`
CHANGE COLUMN `property_address` `property_detail` VARCHAR(255) NOT NULL COMMENT '등기목적물',
CHANGE COLUMN `property_detail` `property_address` VARCHAR(255) DEFAULT NULL COMMENT '목적물';

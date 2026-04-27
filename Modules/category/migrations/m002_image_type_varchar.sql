-- Migration: m002_image_type_varchar
-- Reason   : Remove ENUM restriction on maa_category_images.image_type.
--             The allowed values are now validated at the application layer
--             (CategoryImageTypeEnum) so the DB column is a plain VARCHAR.
--             This allows new image types to be added without a schema migration.

ALTER TABLE `maa_category_images`
    MODIFY COLUMN `image_type` VARCHAR(50) NOT NULL
        COMMENT 'Image slot identifier. Validated by the application layer (see CategoryImageTypeEnum).';


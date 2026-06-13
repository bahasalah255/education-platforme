-- Migration: extend media table for videos and titled resources per unit
ALTER TABLE media
  ADD COLUMN unit_id INT NULL AFTER module_id,
  ADD COLUMN title VARCHAR(255) NULL AFTER filename,
  ADD COLUMN description TEXT NULL AFTER title,
  ADD FOREIGN KEY fk_media_unit (unit_id) REFERENCES units(id) ON DELETE SET NULL;

-- Increase upload path length just in case
ALTER TABLE media MODIFY path VARCHAR(512) NOT NULL;

-- Short Circuit Company — Lighting Technical Data CMS
-- Migration 003: replace inline SVG icons with an editable "online image" field.
--
-- Run this once against your existing database:
--   mysql -u youruser -p your_db < migration_003_image_urls.sql
--
-- This is safe to run on a fresh database too (schema.sql already includes
-- image_url, so the ADD COLUMN below will just be skipped by IF NOT EXISTS
-- logic on MySQL 8+ / MariaDB 10.5+; on older MySQL, ignore the duplicate-
-- column error if you already applied schema.sql from this same drop).

SET NAMES utf8mb4;

ALTER TABLE articles ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) DEFAULT NULL AFTER icon;
ALTER TABLE tools    ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) DEFAULT NULL AFTER icon;

-- ---------------------------------------------------------------
-- Example images. Six rows below use real, freely-licensed (CC-BY-SA)
-- photos/diagrams from Wikimedia Commons — hotlinked via Commons'
-- official Special:FilePath redirector, the same stable mechanism
-- Wikipedia itself uses, so these won't rot or need an API key.
-- The remaining rows use an on-brand placehold.co placeholder (SC red
-- #EB1B26) since a genuinely relevant, freely-licensed photo wasn't
-- confidently available for that exact topic — swap any of these (real
-- photo or placeholder alike) from Admin > Articles/Tools any time.
-- ---------------------------------------------------------------

UPDATE articles SET image_url = 'https://placehold.co/800x450/eb1b26/ffffff?font=poppins&text=CRI'          WHERE slug = 'cri'                 AND image_url IS NULL;
UPDATE articles SET image_url = 'https://commons.wikimedia.org/wiki/Special:FilePath/Color_temperature_black_body_800-12200K.svg' WHERE slug = 'cct' AND image_url IS NULL;
UPDATE articles SET image_url = 'https://commons.wikimedia.org/wiki/Special:FilePath/Sekonic_Speedmaster_L-858D.jpg'              WHERE slug = 'lux' AND image_url IS NULL;
UPDATE articles SET image_url = 'https://placehold.co/800x450/eb1b26/ffffff?font=poppins&text=LUMENS'       WHERE slug = 'lumens'              AND image_url IS NULL;
UPDATE articles SET image_url = 'https://placehold.co/800x450/eb1b26/ffffff?font=poppins&text=UGR'          WHERE slug = 'ugr'                 AND image_url IS NULL;
UPDATE articles SET image_url = 'https://commons.wikimedia.org/wiki/Special:FilePath/Fluorescent_lamp_spectrum.jpg'               WHERE slug = 'flicker' AND image_url IS NULL;
UPDATE articles SET image_url = 'https://placehold.co/800x450/eb1b26/ffffff?font=poppins&text=UNIFORMITY'   WHERE slug = 'uniformity'          AND image_url IS NULL;
UPDATE articles SET image_url = 'https://placehold.co/800x450/eb1b26/ffffff?font=poppins&text=MELANOPIC'    WHERE slug = 'melanopic'           AND image_url IS NULL;
UPDATE articles SET image_url = 'https://placehold.co/800x450/eb1b26/ffffff?font=poppins&text=VERTICAL+Ev'  WHERE slug = 'vertical'            AND image_url IS NULL;
UPDATE articles SET image_url = 'https://commons.wikimedia.org/wiki/Special:FilePath/Golden_Gate_Bridge_at_Night_Long_Exposure_7105222661.jpg' WHERE slug = 'exposure' AND image_url IS NULL;
UPDATE articles SET image_url = 'https://placehold.co/800x450/eb1b26/ffffff?font=poppins&text=EN+12464-1'   WHERE slug = 'en-12464-1'          AND image_url IS NULL;
UPDATE articles SET image_url = 'https://commons.wikimedia.org/wiki/Special:FilePath/Classroom_with_students.JPG'                 WHERE slug = 'comparing-age-groups' AND image_url IS NULL;

UPDATE tools SET image_url = 'https://placehold.co/640x420/000000/eb1b26?font=poppins&text=LuxSCale'   WHERE name = 'LuxSCale'                              AND image_url IS NULL;
UPDATE tools SET image_url = 'https://placehold.co/640x420/000000/eb1b26?font=poppins&text=SChools'    WHERE name = 'SChools — Lighting Quality Simulator'  AND image_url IS NULL;
UPDATE tools SET image_url = 'https://commons.wikimedia.org/wiki/Special:FilePath/Virtual_Reality_Headset_Prototype.jpg' WHERE name = 'XR Fixture Viewer' AND image_url IS NULL;
UPDATE tools SET image_url = 'https://placehold.co/640x420/000000/eb1b26?font=poppins&text=DIALux'     WHERE name = 'DIALux'                                AND image_url IS NULL;
UPDATE tools SET image_url = 'https://placehold.co/640x420/000000/eb1b26?font=poppins&text=Relux'      WHERE name = 'Relux'                                 AND image_url IS NULL;

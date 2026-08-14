-- Migration 003 -- real images instead of inline icons
-- Run on an EXISTING database (after migration_002_*). Safe to run once.
--
--   mysql -u youruser -p your_db < migration_003_images.sql
--
-- Only 3 rows are pre-filled below with images I could actually verify
-- exist (see the note at the bottom of this file for how to find more) --
-- everything else is left NULL so it falls back to the old icon rather
-- than risk a broken image link. Swap any of these any time from
-- Articles > edit > "Image URL" or Tools > edit > "Image URL".

SET NAMES utf8mb4;

ALTER TABLE articles ADD COLUMN image_url VARCHAR(500) DEFAULT NULL AFTER formula_note;
ALTER TABLE tools    ADD COLUMN image_url VARCHAR(500) DEFAULT NULL AFTER icon;

UPDATE articles SET image_url = 'https://commons.wikimedia.org/wiki/Special:FilePath/Color_temperature_black_body_800-12200K.svg' WHERE slug = 'cct';
UPDATE articles SET image_url = 'https://commons.wikimedia.org/wiki/Special:FilePath/Sekonic Speedmaster L-858D.jpg' WHERE slug = 'lux';
UPDATE articles SET image_url = 'https://commons.wikimedia.org/wiki/Special:FilePath/Led light bulb.jpg' WHERE slug = 'lumens';

-- Where to find more (all free-to-hotlink, no attribution wall):
--   1. Wikimedia Commons -- https://commons.wikimedia.org -- search a term,
--      open a File: page, right-click the full-res image > Copy Image
--      Address. That URL (starts with upload.wikimedia.org) works
--      directly in the Image URL field. Everything on Commons is CC or
--      public domain, so this is the safest source for a public site.
--   2. Pexels -- https://www.pexels.com -- free stock photos, no
--      attribution required. Open a photo, right-click > Copy Image
--      Address on the large preview.
--   3. Unsplash -- https://unsplash.com -- same idea as Pexels.
-- Paste the copied URL straight into the Image URL field in the
-- dashboard; there's nothing else to configure.

-- Migration 002 seed data — run AFTER migration_002_science_depth.sql, on a
-- database that already has content from the original seed_content.sql.
-- Only touches the 5 articles the science deck actually covers (cri, cct,
-- lux, ugr, flicker), adds the new Lumens article, and seeds the
-- terminology matrix. Safe to run once.
--
--   mysql -u youruser -p your_db < migration_002_seed.sql

SET NAMES utf8mb4;

UPDATE articles SET
  physical_text = 'White LEDs typically pair a blue chip with a yellow phosphor coating — this fools the eye into seeing white, but leaves gaps in cyan, deep green, and saturated red. If a light source lacks a wavelength, a surface simply can''t reflect it back, so that colour reads as dull or grey. High-CRI fixtures close these gaps with tri-phosphor blends or violet-pumped chips that reproduce a fuller spectrum.',
  physio_text   = 'Under incomplete spectral output, the eye''s ciliary muscles keep over-adjusting trying to resolve edges and textures, and the visual cortex works harder to interpret colour it isn''t being given — both add up to eye strain and faster cognitive fatigue.',
  psycho_text   = 'Low-CRI spaces read as flat and grey-tinted, which can create subtle discomfort or anxiety, and it visibly dulls fresh produce and skin tones — enough to trigger a genuine drop in appetite in food environments.'
WHERE slug = 'cri';

UPDATE articles SET
  physical_text = 'CCT follows blackbody radiation — the same physics that makes a heated object glow from red through white to blue as it gets hotter. LEDs recreate this by blending chips: dual-channel fixtures mix a 2700K and a 6500K LED to glide across the range, multi-channel RGBW fixtures trace the Planckian locus directly, and simpler fixtures use an on-board switch to lock in a fixed blend. Tight colour binning (measured in MacAdam ellipse / SDCM steps) keeps fixtures from the same batch looking consistent side by side — 3-step is the usual bar for architectural work.',
  physio_text   = 'Blue-rich light above ~5000K stimulates the eye''s ipRGC cells, which tells the brain''s clock to pause melatonin production and raises alertness; warmer light below ~3000K does the opposite, letting melatonin release and supporting rest. CCT above 6500K also raises glare sensitivity and, over time, contributes to eye fatigue.',
  psycho_text   = 'Higher CCT shifts the nervous system toward a more alert, task-focused state; lower CCT favours relaxation and a sense of comfort and safety — this is the basis for matching CCT to what a room is actually used for.'
WHERE slug = 'cct';

UPDATE articles SET
  physical_text = 'Illuminance is what a surface actually receives, while candela describes how tightly a fixture focuses that output in one direction — the same total lumens can read very differently depending on beam spread. Because illuminance falls off with the square of the distance from a point source, small changes in mounting height change perceived brightness far faster than small changes in fixture output. The eye''s own sensitivity also isn''t flat across wavelengths — it peaks around 555nm, which is what the lux unit is weighted against.',
  physio_text   = 'Bright vertical light at the eye in the morning (roughly 250–500 lux) helps anchor circadian rhythm and daytime alertness; very low lux (50–100) forces the pupils to dilate, which reduces depth of field.',
  psycho_text   = 'Adequate task lighting lowers cognitive effort and reads as secure and energising; low overall lux paired with targeted accents does the opposite on purpose — creating quieter, more private-feeling spaces.',
  formula_text  = 'E = I / d² × cos(θ)',
  formula_note  = 'E = illuminance (lux), I = candela, d = distance from the source, θ = angle of incidence — drop the cosine term when the light hits straight-on.'
WHERE slug = 'lux';

UPDATE articles SET
  physical_text = 'Glare comes from stray light scattering inside the eye''s own optics (cornea, lens, vitreous humour), which lays a veil of extra luminance over the retina and reduces image contrast — that''s disability glare. Discomfort glare is milder: high brightness contrast that''s simply annoying without impairing vision. On the hardware side, overdriven chips, cheap constant-voltage drivers, overly narrow beam angles, and degraded reflectors are the usual causes; constant-current drivers, proper TIR optics, adequate heatsinking, and physical baffles or louvers are the fix.',
  physio_text   = 'Squinting against glare contracts the muscles around the eyes and forehead, leading to facial soreness and tension headaches, and it reduces normal blink rate — which dries the tear film faster than usual.',
  psycho_text   = 'Bright sources in the field of view pull attention away from the actual task involuntarily, which measurably slows task completion and adds to mental fatigue over a session.',
  formula_text  = 'UGR = 8·log₁₀(0.25/Lb · Σ(Ls²·ω/p²))',
  formula_note  = 'Lb = background luminance, Ls = luminance of the glowing part of the fixture, ω = solid angle it subtends at the eye, p = Guth position index (adjusts for how far the fixture sits from the direct line of sight).'
WHERE slug = 'ugr';

UPDATE articles SET
  physical_text = 'LEDs have none of the thermal inertia an incandescent filament has, so their output can follow current changes in nanoseconds — any ripple in the driver''s output shows up directly as flicker. Cheap drivers don''t fully smooth the ripple from AC mains, which crosses zero voltage twice every cycle. Percent Flicker is a common spec but it ignores frequency entirely — a light pulsing 100% at a highly visible 5Hz scores identical to one pulsing 100% at an imperceptible 30,000Hz. Pst LM and SVM are better because they weight results against how sensitive the eye-brain system actually is, from 0.3Hz to 80Hz over a 10-minute window.',
  physio_text   = 'Visible flicker below about 70Hz can trigger photosensitive seizures in susceptible people. Even flicker in the 80–200Hz range that nobody consciously notices can still overstimulate retinal pathways and contribute to migraines, tension headaches, and eye strain, and flicker around moving machinery can create dangerous stroboscopic illusions.',
  psycho_text   = 'Subconscious processing of erratic light patterns measurably impairs short-term memory and reading comprehension, and chronic exposure raises irritability and error rates in task-heavy environments.'
WHERE slug = 'flicker';

-- New Lumens article (only inserted if it isn't already there)
INSERT INTO articles (slug, tag, icon, title, excerpt, intro, why_text, simulator_url, simulator_label, sort_order)
SELECT 'lumens', 'Light Level', 'lumens', 'Lumens — Luminous Flux',
  'The total light a fixture puts out — separate from lux, which is what actually lands on your desk.',
  'Luminous flux, measured in lumens (lm), is the total quantity of visible light a source emits in all directions. It''s a property of the fixture itself, not of any particular surface — that''s the job of lux.',
  'Specifying by lumens first, then checking the resulting lux at the task surface, is the right order of operations — it keeps fixture selection and room layout from fighting each other.',
  'https://shortcircuit.company/SChools/', 'Open the full live simulator', 4
WHERE NOT EXISTS (SELECT 1 FROM articles WHERE slug = 'lumens');

INSERT INTO article_ranges (article_id, stage_label, environment_label, range_text, notes, sort_order)
SELECT id, 'Low Output', 'Decorative, ambient, orientation lighting', '100–400 lm', NULL, 1 FROM articles
  WHERE slug = 'lumens' AND NOT EXISTS (SELECT 1 FROM article_ranges WHERE article_id = articles.id);
INSERT INTO article_ranges (article_id, stage_label, environment_label, range_text, notes, sort_order)
SELECT id, 'Medium Output', 'General task, residential, office lighting', '400–1,500 lm', NULL, 2 FROM articles
  WHERE slug = 'lumens' AND (SELECT COUNT(*) FROM article_ranges WHERE article_id = articles.id) = 1;
INSERT INTO article_ranges (article_id, stage_label, environment_label, range_text, notes, sort_order)
SELECT id, 'High Output', 'High-ceiling commercial, industrial, floodlighting', '> 1,500 lm', NULL, 3 FROM articles
  WHERE slug = 'lumens' AND (SELECT COUNT(*) FROM article_ranges WHERE article_id = articles.id) = 2;

-- Terminology matrix (skipped if already seeded)
INSERT INTO standard_terms (parameter, en_12464, iso_8995, ansi_ies, well_v2, sort_order)
SELECT * FROM (SELECT
  'CCT' p, 'Tc / Correlated Colour Temperature' a, 'Correlated Colour Temp (Tc)' b, 'CCT / Chromaticity (Duv)' c, 'CCT / m-EDI' d, 1 s
  UNION ALL SELECT 'CRI', 'Ra / General Index', 'Ra / General Colour Index', 'CRI (Ra), TM-30 (Rf, Rg)', 'CRI (Ra), R9, TM-30', 2
  UNION ALL SELECT 'Flicker', 'Pst LM & SVM', 'TLA (Temporal Light Artefacts)', 'Percent Flicker, Flicker Index, IEEE 1789', 'Percent Flicker, SVM, Pst LM', 3
  UNION ALL SELECT 'Glare', 'UGR (UGRL) & Luminance Limits', 'UGR / UGRL', 'VCP, DGP, UGR', 'UGR (≤16 or ≤19) & Luminance Caps', 4
  UNION ALL SELECT 'Lux', 'Em, Ev, Ez', 'Em (Maintained Illuminance)', 'E / Foot-candles (fc) or Lux (lx)', 'Illuminance (lx), EML, m-EDI', 5
  UNION ALL SELECT 'Lumen', 'Φ / Luminous Efficacy', 'Φ / Luminous Flux', 'Φ / Lumens (lm)', 'Luminous Output / Flux', 6
) seed
WHERE NOT EXISTS (SELECT 1 FROM standard_terms);

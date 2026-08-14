-- Optional starter content, migrated from the original static reference page
-- and enriched with "The Science of Light" deck (physical mechanism, formulas,
-- physiological/psychological impact per parameter, plus the cross-standard
-- terminology matrix). Run AFTER schema.sql. Safe to skip if you'd rather
-- start empty and add everything from the dashboard instead.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------
-- Articles: the 10 lighting parameters + standard explainer + comparison guide
-- ---------------------------------------------------------------
INSERT INTO articles (slug, tag, icon, title, excerpt, intro, why_text, physical_text, physio_text, psycho_text, formula_text, formula_note, simulator_url, simulator_label, sort_order) VALUES

('cri','Colour','cri','CRI — Colour Rendering Index',
 'How faithfully a light source reveals the true colours of objects and skin tones.',
 'CRI (Colour Rendering Index) measures how accurately a light source renders colour compared to natural daylight, on a scale up to 100. Low-CRI sources make colours look flat, muddy, or shifted — high-CRI sources render them true to life.',
 'In classrooms, labs, and painting rooms, accurate colour perception affects safety, learning, and the quality of visual tasks. Most educational spaces in this reference target CRI 90+.',
 'White LEDs typically pair a blue chip with a yellow phosphor coating — this fools the eye into seeing white, but leaves gaps in cyan, deep green, and saturated red. If a light source lacks a wavelength, a surface simply can''t reflect it back, so that colour reads as dull or grey. High-CRI fixtures close these gaps with tri-phosphor blends or violet-pumped chips that reproduce a fuller spectrum.',
 'Under incomplete spectral output, the eye''s ciliary muscles keep over-adjusting trying to resolve edges and textures, and the visual cortex works harder to interpret colour it isn''t being given — both add up to eye strain and faster cognitive fatigue.',
 'Low-CRI spaces read as flat and grey-tinted, which can create subtle discomfort or anxiety, and it visibly dulls fresh produce and skin tones — enough to trigger a genuine drop in appetite in food environments.',
 NULL, NULL,
 'https://shortcircuit.company/SChools/', 'Open the full live simulator', 1),

('cct','Colour','cct','CCT — Correlated Colour Temperature',
 'The warmth or coolness of white light, measured in Kelvin — sets mood and alertness.',
 'CCT describes where a white light sits on the warm-to-cool scale, in Kelvin. Lower values (2700–3500K) look warm and amber; higher values (5000–6500K) look cool and blue-white.',
 'Warmer CCT supports calm and rest (play, napping); cooler CCT supports alertness and focus (labs, exam periods). Matching CCT to activity is one of the highest-leverage design decisions.',
 'CCT follows blackbody radiation — the same physics that makes a heated object glow from red through white to blue as it gets hotter. LEDs recreate this by blending chips: dual-channel fixtures mix a 2700K and a 6500K LED to glide across the range, multi-channel RGBW fixtures trace the Planckian locus directly, and simpler fixtures use an on-board switch to lock in a fixed blend. Tight colour binning (measured in MacAdam ellipse / SDCM steps) keeps fixtures from the same batch looking consistent side by side — 3-step is the usual bar for architectural work.',
 'Blue-rich light above ~5000K stimulates the eye''s ipRGC cells, which tells the brain''s clock to pause melatonin production and raises alertness; warmer light below ~3000K does the opposite, letting melatonin release and supporting rest. CCT above 6500K also raises glare sensitivity and, over time, contributes to eye fatigue.',
 'Higher CCT shifts the nervous system toward a more alert, task-focused state; lower CCT favours relaxation and a sense of comfort and safety — this is the basis for matching CCT to what a room is actually used for.',
 NULL, NULL,
 'https://shortcircuit.company/SChools/', 'Open the full live simulator', 2),

('lux','Light Level','lux','Illuminance (Lux)',
 'The amount of light actually falling on a working surface — the core brightness metric.',
 'Illuminance, measured in lux, is the density of light landing on a surface. It is the primary number lighting designers size a room around, since too little strains the eyes and too much causes glare and fatigue.',
 'Required lux varies sharply by task — a napping room needs near-darkness while a science lab needs strong, even illuminance for detailed work.',
 'Illuminance is what a surface actually receives, while candela describes how tightly a fixture focuses that output in one direction — the same total lumens can read very differently depending on beam spread. Because illuminance falls off with the square of the distance from a point source, small changes in mounting height change perceived brightness far faster than small changes in fixture output. The eye''s own sensitivity also isn''t flat across wavelengths — it peaks around 555nm, which is what the lux unit is weighted against.',
 'Bright vertical light at the eye in the morning (roughly 250–500 lux) helps anchor circadian rhythm and daytime alertness; very low lux (50–100) forces the pupils to dilate, which reduces depth of field.',
 'Adequate task lighting lowers cognitive effort and reads as secure and energising; low overall lux paired with targeted accents does the opposite on purpose — creating quieter, more private-feeling spaces.',
 'E = I / d² × cos(θ)', 'E = illuminance (lux), I = candela, d = distance from the source, θ = angle of incidence — drop the cosine term when the light hits straight-on.',
 'https://shortcircuit.company/SChools/', 'Open the full live simulator', 3),

('lumens','Light Level','lumens','Lumens — Luminous Flux',
 'The total light a fixture puts out — separate from lux, which is what actually lands on your desk.',
 'Luminous flux, measured in lumens (lm), is the total quantity of visible light a source emits in all directions. It''s a property of the fixture itself, not of any particular surface — that''s the job of lux.',
 'Specifying by lumens first, then checking the resulting lux at the task surface, is the right order of operations — it keeps fixture selection and room layout from fighting each other.',
 NULL, NULL, NULL, NULL, NULL,
 'https://shortcircuit.company/SChools/', 'Open the full live simulator', 4),

('ugr','Comfort','ugr','UGR — Unified Glare Rating',
 'A measure of visual discomfort caused by overly bright light sources in the field of view.',
 'UGR quantifies discomfort glare from luminaires in a space, typically on a 10–30 scale. Lower values mean less glare and more visual comfort.',
 'High glare fatigues the eyes during sustained tasks like reading or lab work. Most environments here target UGR ≤ 19 for comfortable, detailed viewing.',
 'Glare comes from stray light scattering inside the eye''s own optics (cornea, lens, vitreous humour), which lays a veil of extra luminance over the retina and reduces image contrast — that''s disability glare. Discomfort glare is milder: high brightness contrast that''s simply annoying without impairing vision. On the hardware side, overdriven chips, cheap constant-voltage drivers, overly narrow beam angles, and degraded reflectors are the usual causes; constant-current drivers, proper TIR optics, adequate heatsinking, and physical baffles or louvers are the fix.',
 'Squinting against glare contracts the muscles around the eyes and forehead, leading to facial soreness and tension headaches, and it reduces normal blink rate — which dries the tear film faster than usual.',
 'Bright sources in the field of view pull attention away from the actual task involuntarily, which measurably slows task completion and adds to mental fatigue over a session.',
 'UGR = 8·log₁₀(0.25/Lb · Σ(Ls²·ω/p²))', 'Lb = background luminance, Ls = luminance of the glowing part of the fixture, ω = solid angle it subtends at the eye, p = Guth position index (adjusts for how far the fixture sits from the direct line of sight).',
 'https://shortcircuit.company/SChools/', 'Open the full live simulator', 5),

('flicker','Comfort','flicker','Flicker',
 'Rapid, often imperceptible fluctuations in light output that can still affect the body.',
 'Flicker is variation in light output over time, usually from driver or dimming behaviour. Even flicker invisible to the eye can contribute to headaches and eye strain over long exposure.',
 'Spaces with extended occupancy — classrooms, labs — call for flicker-free drivers to protect comfort during long sessions.',
 'LEDs have none of the thermal inertia an incandescent filament has, so their output can follow current changes in nanoseconds — any ripple in the driver''s output shows up directly as flicker. Cheap drivers don''t fully smooth the ripple from AC mains, which crosses zero voltage twice every cycle. Percent Flicker is a common spec but it ignores frequency entirely — a light pulsing 100% at a highly visible 5Hz scores identical to one pulsing 100% at an imperceptible 30,000Hz. Pst LM and SVM are better because they weight results against how sensitive the eye-brain system actually is, from 0.3Hz to 80Hz over a 10-minute window.',
 'Visible flicker below about 70Hz can trigger photosensitive seizures in susceptible people. Even flicker in the 80–200Hz range that nobody consciously notices can still overstimulate retinal pathways and contribute to migraines, tension headaches, and eye strain, and flicker around moving machinery can create dangerous stroboscopic illusions.',
 'Subconscious processing of erratic light patterns measurably impairs short-term memory and reading comprehension, and chronic exposure raises irritability and error rates in task-heavy environments.',
 NULL, NULL,
 'https://shortcircuit.company/SChools/', 'Open the full live simulator', 6),

('uniformity','Light Level','uniformity','Uniformity',
 'How evenly light is distributed across a space, rather than pooling in bright and dark patches.',
 'Uniformity compares the minimum to average (or minimum to maximum) illuminance across a surface, on a 0–1 scale. A value near 1 means very even light; lower values mean noticeable bright and dark zones.',
 'Poor uniformity forces the eye to constantly readapt between bright and dim areas, which is tiring during detailed or painting tasks that need consistent light balance.',
 NULL, NULL, NULL, NULL, NULL,
 'https://shortcircuit.company/SChools/', 'Open the full live simulator', 7),

('melanopic','Health','melanopic','Melanopic EDI — Circadian Stimulus',
 'The part of light that regulates the body''s internal clock, alertness, and sleep cycle.',
 'Melanopic Equivalent Daylight Illuminance (EDI) measures how strongly light stimulates the eye''s non-visual, circadian-regulating photoreceptors, expressed in lux.',
 'Adequate melanopic stimulation during the day supports alertness and healthy sleep-wake rhythms — increasingly treated as a core design target for schools alongside visual lux.',
 NULL, NULL, NULL, NULL, NULL,
 'https://shortcircuit.company/SChools/', 'Open the full live simulator', 8),

('vertical','Light Level','vertical','Vertical Illuminance',
 'Light measured on vertical surfaces — faces, whiteboards, walls — not just the desk.',
 'While standard illuminance is measured on horizontal work surfaces, vertical illuminance captures light on vertical planes: faces, whiteboards, and walls.',
 'Good vertical illuminance improves face-to-face visibility and comfort — important in classrooms for reading expressions and in music rooms for reading sheet music and each other.',
 NULL, NULL, NULL, NULL, NULL,
 'https://shortcircuit.company/SChools/', 'Open the full live simulator', 9),

('exposure','Health','exposure','Exposure Duration',
 'How long occupants spend under a given lighting condition each day.',
 'Exposure duration is simply how many hours per day a space is occupied under a lighting condition. It is the multiplier that turns a single lux or CCT reading into a real biological and comfort outcome.',
 'Matching lighting schedules to the academic day keeps circadian and comfort targets aligned with how long students and staff actually spend under each condition.',
 NULL, NULL, NULL, NULL, NULL,
 'https://shortcircuit.company/SChools/', 'Open the full live simulator', 10),

('en-12464-1','Standard','standard','EN 12464-1 — Why These Ranges Exist',
 'The lighting-for-workplaces standard behind the recommended ranges in this reference.',
 'EN 12464-1 is the European standard for lighting in indoor workplaces, defining recommended illuminance, glare, uniformity, and colour-rendering targets by task and room type. The ranges throughout this reference are structured the same way — by environment and activity, not a single blanket number.',
 'Designing to a standard, rather than a rule of thumb, keeps lighting decisions defensible, comparable across projects, and adaptable as a space''s use changes.',
 NULL, NULL, NULL, NULL, NULL,
 NULL, NULL, 11),

('comparing-age-groups','Guide','compare','Comparing Environments by Age Group',
 'Why a kindergarten playground and a secondary school lab need very different light.',
 'Lighting needs shift with both age and activity. Younger children generally need warmer, gentler light for play and rest; older students need cooler, brighter, glare-controlled light for sustained focus and detailed lab work.',
 'Treating every room the same wastes the opportunity lighting has to support the right state — calm, focus, or rest — for the people actually using it.',
 NULL, NULL, NULL, NULL, NULL,
 NULL, NULL, 12);

-- Example range rows for CRI (repeat this pattern per article/environment via the dashboard)
INSERT INTO article_ranges (article_id, stage_label, environment_label, range_text, notes, sort_order)
SELECT id, 'Kindergarten', 'Playground', '90–100', 'Excellent colour rendering for sensitive visual tasks.', 1 FROM articles WHERE slug = 'cri';
INSERT INTO article_ranges (article_id, stage_label, environment_label, range_text, notes, sort_order)
SELECT id, 'Kindergarten', 'Painting', '90–100', 'True colour rendering while painting.', 2 FROM articles WHERE slug = 'cri';
INSERT INTO article_ranges (article_id, stage_label, environment_label, range_text, notes, sort_order)
SELECT id, 'Primary', 'Classroom', '90–100', 'Excellent colour rendering for reading, exams, or labs.', 3 FROM articles WHERE slug = 'cri';
INSERT INTO article_ranges (article_id, stage_label, environment_label, range_text, notes, sort_order)
SELECT id, 'Secondary', 'Laboratory', '90–100', 'Excellent colour rendering for detailed lab work.', 4 FROM articles WHERE slug = 'cri';

-- Output-level range rows for Lumens
INSERT INTO article_ranges (article_id, stage_label, environment_label, range_text, notes, sort_order)
SELECT id, 'Low Output', 'Decorative, ambient, orientation lighting', '100–400 lm', NULL, 1 FROM articles WHERE slug = 'lumens';
INSERT INTO article_ranges (article_id, stage_label, environment_label, range_text, notes, sort_order)
SELECT id, 'Medium Output', 'General task, residential, office lighting', '400–1,500 lm', NULL, 2 FROM articles WHERE slug = 'lumens';
INSERT INTO article_ranges (article_id, stage_label, environment_label, range_text, notes, sort_order)
SELECT id, 'High Output', 'High-ceiling commercial, industrial, floodlighting', '> 1,500 lm', NULL, 3 FROM articles WHERE slug = 'lumens';

-- ---------------------------------------------------------------
-- Standards
-- ---------------------------------------------------------------
INSERT INTO standards (code, name, region, description, official_url, sort_order) VALUES
('EN 12464-1', 'Light and lighting — Lighting of work places — Part 1: Indoor work places', 'Europe',
 'Defines recommended illuminance, glare (UGR), uniformity, and colour-rendering targets by task and room type for indoor workplaces, including schools and offices.',
 'https://www.en-standard.eu/din-en-12464-1/', 1),
('EN 12464-2', 'Light and lighting — Lighting of work places — Part 2: Outdoor work places', 'Europe',
 'The outdoor counterpart to EN 12464-1, covering illuminance and glare targets for yards, loading areas, and other exterior work zones.',
 'https://www.en-standard.eu/din-en-12464-2/', 2),
('EN 13201', 'Road Lighting', 'Europe',
 'Defines lighting classes and photometric requirements for public roads based on traffic type, speed, and complexity.',
 'https://www.en-standard.eu/bs-en-13201-2-road-lighting-performance-requirements/', 3),
('IES RP-1', 'Recommended Practice: Lighting Office Spaces', 'North America',
 'The Illuminating Engineering Society''s recommended illuminance and quality targets for office and workplace lighting.',
 'https://www.ies.org/', 4),
('WELL Light Concept', 'WELL Building Standard — Light', 'Global',
 'A wellness-focused building standard with circadian lighting requirements, including melanopic-equivalent illuminance targets across the day.',
 'https://www.wellcertified.com/', 5);

-- ---------------------------------------------------------------
-- Terminology matrix — how each parameter is named across frameworks
-- ---------------------------------------------------------------
INSERT INTO standard_terms (parameter, en_12464, iso_8995, ansi_ies, well_v2, sort_order) VALUES
('CCT',     'Tc / Correlated Colour Temperature', 'Correlated Colour Temp (Tc)', 'CCT / Chromaticity (Duv)', 'CCT / m-EDI', 1),
('CRI',     'Ra / General Index', 'Ra / General Colour Index', 'CRI (Ra), TM-30 (Rf, Rg)', 'CRI (Ra), R9, TM-30', 2),
('Flicker', 'Pst LM & SVM', 'TLA (Temporal Light Artefacts)', 'Percent Flicker, Flicker Index, IEEE 1789', 'Percent Flicker, SVM, Pst LM', 3),
('Glare',   'UGR (UGRL) & Luminance Limits', 'UGR / UGRL', 'VCP, DGP, UGR', 'UGR (≤16 or ≤19) & Luminance Caps', 4),
('Lux',     'Em, Ev, Ez', 'Em (Maintained Illuminance)', 'E / Foot-candles (fc) or Lux (lx)', 'Illuminance (lx), EML, m-EDI', 5),
('Lumen',   'Φ / Luminous Efficacy', 'Φ / Luminous Flux', 'Φ / Lumens (lm)', 'Luminous Output / Flux', 6);

-- ---------------------------------------------------------------
-- Tools
-- ---------------------------------------------------------------
INSERT INTO tools (name, description, url, icon, is_external, sort_order) VALUES
('LuxSCale', 'AI-powered lighting design system — configure a space''s dimensions and purpose and get code-aligned lighting proposals in minutes.', 'https://shortcircuit.company/LuxSCale/', 'lux2', 0, 1),
('SChools — Lighting Quality Simulator', 'The source of this reference''s education data — pick a grade and environment for tailored lighting recommendations.', 'https://shortcircuit.company/SChools/', 'school', 0, 2),
('XR Fixture Viewer', 'Explore Short Circuit luminaires in 3D, place them in your space with AR, or step into a full VR walkthrough.', 'https://shortcircuit.company/XR/', 'xr', 0, 3),
('DIALux', 'Free, widely used professional lighting design software for calculating illuminance, glare, and energy performance against EN and IES standards.', 'https://www.dialux.com/', 'dialux', 1, 4),
('Relux', 'Lighting simulation software for interior, exterior, and road lighting design with photorealistic rendering and standards compliance checks.', 'https://relux.com/', 'dialux', 1, 5);

-- Enable extensions required by BCA Scrap v2
CREATE EXTENSION IF NOT EXISTS unaccent;

-- Create custom text search configuration using unaccent
CREATE TEXT SEARCH CONFIGURATION portuguese_unaccent (COPY = portuguese);
ALTER TEXT SEARCH CONFIGURATION portuguese_unaccent
    ALTER MAPPING FOR hword, hword_part, word
    WITH unaccent, portuguese_stem;

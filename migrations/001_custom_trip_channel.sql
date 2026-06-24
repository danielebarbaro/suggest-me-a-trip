-- migrations/001_custom_trip_channel.sql
-- Custom trip channel: per-subscriber channel toggles + filter config.
-- Run once against the shared Turso DB:
--   turso db shell <db-name> < migrations/001_custom_trip_channel.sql
-- SQLite has no "ADD COLUMN IF NOT EXISTS"; if a column already exists the
-- statement errors harmlessly and the rest can be applied individually.

ALTER TABLE emails ADD COLUMN sub_daily_trips INTEGER NOT NULL DEFAULT 1;
ALTER TABLE emails ADD COLUMN sub_daily_itineraries INTEGER NOT NULL DEFAULT 1;
ALTER TABLE emails ADD COLUMN sub_custom_trip INTEGER NOT NULL DEFAULT 0;

ALTER TABLE emails ADD COLUMN custom_country TEXT;
ALTER TABLE emails ADD COLUMN custom_direction TEXT;
ALTER TABLE emails ADD COLUMN custom_max_km INTEGER;
ALTER TABLE emails ADD COLUMN custom_date_from TEXT;
ALTER TABLE emails ADD COLUMN custom_date_to TEXT;

ALTER TABLE emails ADD COLUMN unsubscribe_token TEXT;

-- Runs automatically when labs-db initializes a fresh data volume
-- (first start, and every host/reset-labs.sh since it renews the volume).
-- Creates the shared databases that several labs connect to directly without
-- creating them, so those labs work right after a reset even before any
-- self-creating lab has been visited. Per-lab databases are still made by the
-- labs themselves on first request.
CREATE DATABASE IF NOT EXISTS `KrazePlanet_DB` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `KrazePlanet`    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

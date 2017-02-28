-- drop all app schemas
DROP SCHEMA IF EXISTS appback CASCADE;

-- clear the Flyway migration table
DROP TABLE schema_version CASCADE;

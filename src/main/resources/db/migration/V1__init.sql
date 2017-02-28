-- oletusskeeman ja hakuskeemojen valinta
set search_path = appback, pg_catalog;
-- tällä siis käytännössä valitaan mihin skeemaan taulut luodaan!
-- => appback-skeemaan

-- poistetaan koko skeema
DROP SCHEMA IF EXISTS appback CASCADE;
CREATE SCHEMA appback AUTHORIZATION appaccount;

--
-- TAULUT
--
DROP TABLE IF EXISTS julkaisu CASCADE;
CREATE TABLE julkaisu
(
    id bigserial not null primary key,
    
    organisaatiotunnus text null,
    
    julkaisutyyppi text null, --koodi
    julkaisuvuosi int null,
    julkaisunnimi text null,
    tekijat text null,
    julkaisuntekijoidenlukumaara int null,
    -- organisaatiotekija
    konferenssinvakiintunutnimi text null,
    emojulkaisunnimi text null,
    isbn text null,
    emojulkaisuntoimittajat text null,
    lehdenjulkaisusarjannimi text null,
    issn text null,
    volyymi text null,
    numero text null,
    sivut text null,
    artikkelinumero text null,
    kustantaja text null,
    julkaisunkustannuspaikka text null,
    -- avainsana
    julkaisunkieli text null, --koodi
    julkaisunkansainvalisyys int null, --kytkin
    julkaisumaa text null, --koodi
    -- tieteenala
    kansainvalinenyhteisjulkaisu int null, --kytkin
    yhteisjulkaisuyrityksenkanssa int null, --kytkin
    doitunniste text null,
    pysyvaverkkoosoite text null,
    avoinsaatavuus text null, --koodi
    julkaisurinnakkaistallenettu int null, --kytkin
    rinnakkaistallenetunversionverkkoosoite text null,
    jufotunnus text null, --jufoid
    jufoluokitus text null --koodi
);
DROP TABLE IF EXISTS avainsana CASCADE;
CREATE TABLE avainsana
(
    id bigserial not null primary key,
    julkaisuid bigint not null,
    avainsana text not null
);
DROP TABLE IF EXISTS organisaatiotekija CASCADE;
CREATE TABLE organisaatiotekija
(
    id bigserial not null primary key,
    julkaisuid bigint not null,
    etunimet text null,
    sukunimi text null,
    orcid text null
);
DROP TABLE IF EXISTS alayksikko CASCADE;
CREATE TABLE alayksikko
(
    id bigserial not null primary key,
    organisaatiotekijaid bigint not null,
    alayksikko text null
);
DROP TABLE IF EXISTS tieteenala CASCADE;
CREATE TABLE tieteenala
(
    id bigserial not null primary key,
    julkaisuid bigint not null,
    tieteenalakoodi text not null,
    jnro int null
);

--/* VIITEAVAIMET
ALTER TABLE avainsana  ADD CONSTRAINT fk_julkaisu FOREIGN KEY (julkaisuid)
  REFERENCES julkaisu (id) MATCH SIMPLE  ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE organisaatiotekija  ADD CONSTRAINT fk_julkaisu FOREIGN KEY (julkaisuid)
  REFERENCES julkaisu (id) MATCH SIMPLE  ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE alayksikko  ADD CONSTRAINT fk_organisaatiotekija FOREIGN KEY (organisaatiotekijaid)
  REFERENCES organisaatiotekija (id) MATCH SIMPLE  ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE tieteenala  ADD CONSTRAINT fk_julkaisu FOREIGN KEY (julkaisuid)
  REFERENCES julkaisu (id) MATCH SIMPLE  ON UPDATE CASCADE ON DELETE CASCADE;
--*/

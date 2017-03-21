-- Table: julkaisu

-- DROP TABLE julkaisu;

CREATE TABLE julkaisu
(
  id bigserial NOT NULL,
  organisaatiotunnus text,
  julkaisutyyppi text,
  julkaisuvuosi integer,
  julkaisunnimi text,
  tekijat text,
  julkaisuntekijoidenlukumaara integer,
  konferenssinvakiintunutnimi text,
  emojulkaisunnimi text,
  isbn text,
  emojulkaisuntoimittajat text,
  lehdenjulkaisusarjannimi text,
  issn text,
  volyymi text,
  numero text,
  sivut text,
  artikkelinumero text,
  kustantaja text,
  julkaisunkustannuspaikka text,
  julkaisunkieli text,
  julkaisunkansainvalisyys integer,
  julkaisumaa text,
  kansainvalinenyhteisjulkaisu integer,
  yhteisjulkaisuyrityksenkanssa integer,
  doitunniste text,
  pysyvaverkkoosoite text,
  avoinsaatavuus text,
  julkaisurinnakkaistallennettu integer,
  rinnakkaistallenetunversionverkkoosoite text,
  jufotunnus text,
  jufoluokitus text,
  status character varying(5) NOT NULL DEFAULT 0,
  username character varying(100),
  modified timestamp without time zone NOT NULL DEFAULT now(),
  CONSTRAINT julkaisu_pkey PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE julkaisu
  OWNER TO appaccount;




-- Table: avainsana

-- DROP TABLE avainsana;

CREATE TABLE avainsana
(
  id bigserial NOT NULL,
  julkaisuid bigint NOT NULL,
  avainsana text NOT NULL,
  CONSTRAINT avainsana_pkey PRIMARY KEY (id),
  CONSTRAINT fk_julkaisu FOREIGN KEY (julkaisuid)
      REFERENCES julkaisu (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);
ALTER TABLE avainsana
  OWNER TO appaccount;



-- Table: tieteenala

-- DROP TABLE tieteenala;

CREATE TABLE tieteenala
(
  id bigserial NOT NULL,
  julkaisuid bigint NOT NULL,
  tieteenalakoodi text NOT NULL,
  jnro integer,
  CONSTRAINT tieteenala_pkey PRIMARY KEY (id),
  CONSTRAINT fk_julkaisu FOREIGN KEY (julkaisuid)
      REFERENCES julkaisu (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);
ALTER TABLE tieteenala
  OWNER TO appaccount;





-- Table: organisaatiotekija

-- DROP TABLE organisaatiotekija;

CREATE TABLE organisaatiotekija
(
  id bigserial NOT NULL,
  julkaisuid bigint NOT NULL,
  etunimet text,
  sukunimi text,
  orcid text,
  CONSTRAINT organisaatiotekija_pkey PRIMARY KEY (id),
  CONSTRAINT fk_julkaisu FOREIGN KEY (julkaisuid)
      REFERENCES julkaisu (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);
ALTER TABLE organisaatiotekija
  OWNER TO appaccount;


-- Table: alayksikko

-- DROP TABLE alayksikko;

CREATE TABLE alayksikko
(
  id bigserial NOT NULL,
  organisaatiotekijaid bigint NOT NULL,
  alayksikko text,
  CONSTRAINT alayksikko_pkey PRIMARY KEY (id),
  CONSTRAINT fk_organisaatiotekija FOREIGN KEY (organisaatiotekijaid)
      REFERENCES organisaatiotekija (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);
ALTER TABLE alayksikko
  OWNER TO appaccount;

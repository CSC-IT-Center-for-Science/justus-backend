-- View: uijulkaisut

-- DROP VIEW uijulkaisut;

CREATE OR REPLACE VIEW uijulkaisut AS 
 SELECT tab.id, row_to_json(tab.*) AS row_to_json
   FROM ( SELECT j.id, j.organisaatiotunnus, j.julkaisutyyppi, j.julkaisuvuosi, 
            j.julkaisunnimi, j.tekijat, j.julkaisuntekijoidenlukumaara, 
            j.konferenssinvakiintunutnimi, j.emojulkaisunnimi, j.isbn, 
            j.emojulkaisuntoimittajat, j.lehdenjulkaisusarjannimi, j.issn, 
            j.volyymi, j.numero, j.sivut, j.artikkelinumero, j.kustantaja, 
            j.julkaisunkustannuspaikka, j.julkaisunkieli, 
            j.julkaisunkansainvalisyys, j.julkaisumaa, 
            j.kansainvalinenyhteisjulkaisu, j.yhteisjulkaisuyrityksenkanssa, 
            j.doitunniste, j.pysyvaverkkoosoite, j.avoinsaatavuus, 
            j.julkaisurinnakkaistallennettu, 
            j.rinnakkaistallennetunversionverkkoosoite, j.jufotunnus, 
            j.jufoluokitus, j.julkaisuntila, j.username, j.modified, 
            ( SELECT array_to_json(array_agg(avainsana.avainsana)) AS array_to_json
                   FROM avainsana
                  WHERE avainsana.julkaisuid = j.id) AS avainsanat, 
            ( SELECT array_to_json(array_agg(tieteenala.tieteenalakoodi)) AS array_to_json
                   FROM tieteenala
                  WHERE tieteenala.julkaisuid = j.id) AS julkaisuntieteenalat, 
            ( SELECT array_to_json(array_agg(( SELECT t.*::record AS t
                           FROM ( SELECT organisaatiotekija.etunimet, 
                                    organisaatiotekija.sukunimi, 
                                    organisaatiotekija.orcid,
                                    organisaatiotekija.rooli, 
                                    ( SELECT array_to_json(array_agg(alayksikko.alayksikko)) AS array_to_json
                                           FROM alayksikko
                                          WHERE alayksikko.organisaatiotekijaid = organisaatiotekija.id) AS alayksikot) t))) AS array_to_json
                   FROM organisaatiotekija
                  WHERE organisaatiotekija.julkaisuid = j.id) AS organisaationtekijat
           FROM julkaisu j) tab;

ALTER TABLE uijulkaisut
  OWNER TO appaccount;

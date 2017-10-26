CREATE OR REPLACE FUNCTION kaytto_loki_insert()
  RETURNS trigger AS
$$
BEGIN
         UPDATE kaytto_loki set luonti_pvm = current_timestamp
         WHERE id = NEW.id;
 
    RETURN NEW;
END;
$$
LANGUAGE 'plpgsql';

grant execute on function kaytto_loki_insert() to appaccount;


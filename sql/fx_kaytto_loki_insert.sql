CREATE OR REPLACE FUNCTION kaytto_loki_insert() RETURNS trigger AS $$
BEGIN
  UPDATE kaytto_loki set luonti_pvm = current_timestamp
  WHERE id = NEW.id;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

GRANT EXECUTE ON FUNCTION kaytto_loki_insert() TO appaccount;

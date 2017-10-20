
--
-- Sequence
--

create sequence kaytto_seq;
grant all on sequence public.kaytto_seq to appaccount;


--
-- Name: kaytto_loki; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE kaytto_loki (
    id bigint DEFAULT nextval('kaytto_seq'::regclass),
    name character varying(100),
    mail character varying(100),
    uid character varying(50),
    julkaisu bigint,
    organization character varying(100),
    role character varying(50),
    itable character varying(100),
    action character varying(10),
    data json,
    luonti_pvm timestamptz
);


ALTER TABLE public.kaytto_loki OWNER TO postgres;

--
-- Name: kaytto_loki_insert_trg; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER kaytto_loki_insert_trg AFTER INSERT ON kaytto_loki FOR EACH ROW EXECUTE PROCEDURE kaytto_loki_insert();


--
-- Name: kaytto_loki; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE kaytto_loki FROM PUBLIC;
REVOKE ALL ON TABLE kaytto_loki FROM postgres;
GRANT ALL ON TABLE kaytto_loki TO postgres;
GRANT ALL ON TABLE kaytto_loki TO appaccount;

ALTER TABLE julkaisu ADD COLUMN accessid bigint;
--
-- PostgreSQL database dump complete
--


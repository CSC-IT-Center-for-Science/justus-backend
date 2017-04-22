select julkaisu.id,julkaisutyyppi,julkaisuvuosi,julkaisunnimi
,array_to_json((
  select array_agg(r.avainsana)
  from (
    select avainsana
    from avainsana
    where julkaisuid=julkaisu.id
  ) as r
)) as avainsanat
,array_to_json((
  select array_agg(r)
  from (
    select tieteenalakoodi,jnro
    from tieteenala
    where julkaisuid=julkaisu.id
  ) as r
)) as tieteenalat
,array_to_json((
  select array_agg(r)
  from (
    select etunimet,sukunimi,orcid
    ,array_to_json((
      select array_agg(rr.alayksikko)
      from (
        select alayksikko
        from alayksikko
        where organisaatiotekijaid=organisaatiotekija.id
      ) as rr
    )) as alayksikot
    from organisaatiotekija
    where julkaisuid=julkaisu.id
  ) as r
)) as organisaationtekijat
from julkaisu

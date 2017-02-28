         _ _   _  ____ _____ __  _  ____
        | | | | |/ __ |_   _| | | |/ __ |
        | | | | |\__ _  | | | | | |\__ _
     _  | | | | |_  \ \ | | | | | |_  \ \
    | |_| | |_| | |_| | | | | |_| | |_| |
     \___/ \___/ \___/  \_/  \___/ \___/
      | |__  __ _ __| |_____ _ _  __| |
      | '_ \/ _` / _| / / -_) ' \/ _` |
      |_.__/\__,_\__|_\_\___|_||_\__,_|
            :: JUSTUS Backend ::

# Buildaus ja kehitysympäristön asennus - How-to

## Alkuvalmistelut

Koneelle täytyy asentaa: 

* Java 8
* Maven (3.3.1 tai uudempi)
 
## PostgreSQL

Sovellusta ajettaessa PITÄÄ konfiguraatiot olla halutun profiilin mukaisessa `application.yml` tai `application-demo.yml` 
tiedostossa, tai muuten konfiguraatiot luetaan projektin sisältä!

Käännettäessä sovellus, pitää aina muistaa valita myös maven profiili -P demo tai -P prod, jotta pakettiin tulee mukaan 
tarvittavat resurssit.

### Esiasennus

    CREATE USER appaccount WITH PASSWORD 'appaccount' CREATEDB;

    $ psql -U appaccount -W template1 -h 127.0.0.1

    CREATE DATABASE justus ENCODING 'UTF-8'

    CREATE DATABASE justus-test ENCODING 'UTF-8'

## Tietokantarakenteen luonti, populointi ja puhdistus

Tietokannan ajantasaisuudesta vastaa Flyway-migraatiotyökalu.

### Flyway-migraatioiden ajaminen Mavenilla ja JOOQ-tietokantaluokkien generointi

Maven Flyway plugin vie migraatioista löytyvät tietokantarakenteet (aluksi: ``resources/db/migration/V1__init.sql``) 
käännöksen yhteydessä tietokantaan jos Mavenin generate-db -profiili enabloidaan.

Projektissa käytössä oleva JOOQ generoi suoraan tietokannasta käytettäviä DAO- ja entiteettiluokkia. Jos 
tietokantatauluihin tulee muutoksia, JOOQ-luokat pitää generoida uudelleen. JOOQ-luokat generoidaan
generate-db -profiiilin ajon yhteydessä.

**Aja Flyway ja JOOQ:**

#### Entityjen luominen

    $ mvn -Ddb.host=$POSTGRES_IP -Ddb.port=5432 compile -Pgenerate-db

HUOM! Käyttää nyt `application.yml` -tiedoston konfiguraatioita, joten jos postgres-portti tms. asetus on 
eroavainen ko. tiedoston asetuksista, pitää sinne tehdä muutoksia.

#### Populoi tietokanta alustavalla datasetillä

Huom: muistathan ajaa ``mvn ... compile -Pgenerate-db`` ensin, fikstuuri vaatii valmiiksi luodun kantaskeeman.
    
    $ mvn initialize sql:execute@populate-db

#### Pudota flyway-migraatiokanta ja applikaatiospesifiset skeemat (e.g. tabula rasa)

    $ mvn initialize sql:execute@clean-db


## Aja kehitysversiota lokaali-Mavenilla

    $ mvn spring-boot:run # Runs with defined demo settings
  
Kokeile:

    http://localhost:8090/ -> swagger API docs
    
    
TODO: laita toimimaan tuotannossa/servereillä!

Ajaminen omassa ympäristössä paketoituna:

    $ mvn package -P demo 

    $ java -jar target/justus-backend.jar --spring.profiles.active=demo --server.port=8090 


## Sananen konfiguraatiosta

Konfiguraatiot otetaan käyttöön seuraavassa järjestyksessä:

1. Suorat komentoriviargumentit Mavenille (esim: ``-Ddb.host=mydbhost.com``)
2. Profiilispesifiset konfiguraatiot jar-paketin ulkopuolella (ei käytössä toistaiseksi)
3. Profiilispesifiset konfiguraatiot (``<project-root>/src/main/resources/config/application-{profile}.yml``)
4. Yleiskonfiguraatio jar-paketin ulkopuolella (kehittäessä ``<project-root>/application.yml`, tai jar-paketin ulkopuolella palvelimella)
5. Yleiskonfiguraatio jar-paketissa (``<project-root>/src/main/resources/config/application.yml``)

Konfiguraatiotiedostojen Maven-filtteröintiä (e.g. ${placeholder}:ien korvausta) voi käyttää jar-paketin sisällä 
oleville konfiguraatioille.


## Paketointi serveriympäristöihin

Voit rakentaa paketit ajamalla:

    $ mvn package -P demo (tai prod profiililla)

Jos käytät samalla paketin ulkopuolista konfiguraatiota. Mikäli haluat konffit paketin sisään, anna ne mukaan paketoinnissa.

Paketit rakennetaan oikeilla tietokanta-IP:illä, postgressin salasanalla ja Mavenin profiililla 
(yleensä -P prod, tekee prodiin ja stagingiin sopivan paketin):

    $ mvn -Ddb.host=demo.justus.csc.fi -Ddb.pass=<INSERT REAL PASSWORD HERE> package -P prod # build prod package
   
Tällöin asetukset ovat hardkoodattuina paketin sisällä, eikä ulkoista konfiguraatiota tarvita.   
   
   
## Serverillä manuaalinen backendin käynnistys demossa:

    $ java -jar justus-backend.jar --spring.profiles.active=demo --server.port=8080
    
   Manuaalinen konffiesimerkki:
    
    $ java -jar justus-backend.jar --server.port=8080 --db.host=192.168.1.165 --db.pass=appaccount --spring.profiles.active=prod

## Julkaisut

**TODO** Projekti kopioitu ja release-käytäntö ei välttämättä toimi!

Käytössä Maven release plugin. Plugin kysyy ajettaessa tehtävää julkaisuversiota (esim 1.1.2), ja tämän jälkeistä 
snapshot-versiota (esim 1.2.0-SNAPHOT). Plugin hoitaa tagien tekemisen ja puskemisen gittiin automaattisesti, joten
oma git-tunnus ja -salasana on annettava ajon yhteydessä.

### Komennot

**HUOM!** Release-plugin suorittaa oletuksena kaikki testit, joten varmista että kohdan `Testit` vaatimat määritykset on tehty ennenkuin ajat release-komentoja

Kokeile julkaisua:

    $ mvn release:prepare -DdryRun=true -Dusername=<your username> -P demo
  
Poista release pluginin tekemät tiedostot kokeilun jälkeen:

    $ mvn release:clean
    
Tee oikea julkaisu:

    $ mvn release:prepare -Dusername=<your username> -P demo

Jos haluaa elää vaarallisesti, niin testien skippaaminen onnistuu lisäämalla argumentti
    -Darguments="-DskipTests"

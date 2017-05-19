         _ _   _  ____ _____ __  _  ____  
        | | | | |/ __ |_   _| | | |/ __ | 
        | | | | |\__ _  | | | | | |\__ _  
     _  | | | | |_  \ \ | | | | | |_  \ \ 
    | |_| | |_| | |_| | | | | |_| | |_| | 
     \___/ \___/ \___/  \_/  \___/ \___/  
      | |__  __ _ __| |_____ _ _  __| |   
      | '_ \/ _` / _| / / -_) ' \/ _` |   
      |_.__/\__,_\__|_\_\___|_||_\__,_|   
            :: JUSTUS backend ::          

## Requirements

### Server side

#### APACHE

* HTTPS 
    * See _TLS/SSL CERTIFICATE_
* HTTP -> HTTPS redirect always
* Configurations are located at usual place `/etc/httpd/conf.d/`
    * They produce for example:
    * wwwroot = `/opt/justus`
    * backend (`/api`) = `/opt/justus-backend`
    * etc


#### TLS/SSL CERTIFICATE

(NB! For EduuniID authentication with shibboleth uses another self-signed certificate)

* CertBot (Let's Encrypt)
    * See root users `crontab -l`


#### PHP

* Version 5.4
* Packages via yum
    * `php`
    * `php-cli`
    * `php-common`
    * `php-pdo`
    * `php-pgsql`


#### POSTGRES

Install
* Version 9.2
* Basic installation via yum
    * `postgresql`
    * `postgresql-libs`
    * `postgresql-server`
* No access from outside the host machine
* TCP access from localhost (edit `pg_hba.conf` as much)
* Set enabled and start

Preparations for application
* Create a user/role for application (nb! sql files assume user name to be `appaccount`)
* Create a database for application
    * Grant sufficient permissions for application user (owner of database for example)
* Run SQL files (as application user) `sql/tables.sql` and `sql/view-uijulkaisut.sql`
* The account info the application uses is kept in a localhost file `/etc/justus-backend.ini` with structure like:

    [database]
    host = "hostname here (localhost)"
    port = "port number here (5432)"
    name = "database name here (justus)"
    user = "user name here (appaccount)"
    pass = "password here"

#### SHIBBOLETH

* Create self-signed certificate (nb! valid for 10 years)

  `openssl req -x509 -nodes -days 3650 -newkey rsa:2048 -keyout /etc/pki/tls/private/shibboleth-selfsigned.key -out /etc/pki/tls/certs/shibboleth-selfsigned.crt`

* Add shibboleth to yum repositories

  `curl -o /etc/yum.repos.d/shibboleth.repo http://download.opensuse.org/repositories/security://shibboleth/CentOS_7/security:shibboleth.repo`

* Install shibboleth

  `yum install shibboleth`

* Install mod_shibd

  NB! No need if httpd was installed first. Comes automatically.

  `#yum install mod_shibd`

* Get Eduuni meta data

  `cd /etc/shibboleth`

  `wget https://fse.eduuni.fi/eduuni-idp-metadata2.xml`

* Alter `shibboleth2.xml` and `attribute-map.xml`

  https://eduuni.zendesk.com/attachments/token/gmR66wC0hMMuDkLCkpTDMCZcO/?name=Eduuni+ID+kuvaus.docx

  `cat /etc/shibboleth/shibboleth2.xml`

  `cat /etc/shibboleth/attribute-map.xml`

* Set for start-at-boot

  `systemctl enable shibd`

* Start

  `systemctl start shibd`

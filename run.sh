#!/bin/sh

#POSTGRES_IP=$(docker inspect --format '{{ .NetworkSettings.IPAddress }}' backend_postgres_1)
POSTGRES_IP=localhost

sed  "s/POSTGRES_IP/$POSTGRES_IP/g"  application-template.yml > application-demo.yml

mvn -Dspring.profiles.active=demo spring-boot:run

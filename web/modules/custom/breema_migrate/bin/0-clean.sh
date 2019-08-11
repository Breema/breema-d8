#!/bin/sh

drush -y sql-drop;
gzcat /Applications/MAMP-common/htdocs/breema-d8/dumps/live-import-target.sql.gz | `drush sql-connect`
drush -y cim
drush cr
drush -y en breema_migrate
drush -y pmu geocoder

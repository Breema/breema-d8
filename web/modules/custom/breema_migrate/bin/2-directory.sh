#!/bin/sh

drush -y pmu geocoder
drush mim breema_csv_directory_entry

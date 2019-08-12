#!/bin/sh

# Sets are already defined in config, don't need migration.

# Define links for each set.
drush mim breema_csv_shortcut

# Define the right set for each user based on certification level.
drush mim breema_csv_shortcut_user

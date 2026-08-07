#!/bin/bash
php artisan migrate --force
php artisan ca:init
apache2-foreground

#!/bin/bash

# 1. Chạy anh công nhân cào giải ngầm
php artisan xsmb:live-watch --force > /dev/null 2>&1 &

# 2. Chạy Apache foreground (để Docker không bị thoát ra)
apache2-foreground
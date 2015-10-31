#!/bin/bash
cd "$(dirname "$0")";

while(true) do
  /usr/bin/php /app/repostomatic.php;
  killall -9 php;
done;

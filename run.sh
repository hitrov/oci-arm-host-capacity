#!/bin/sh

CODE=$(php ./index.php | jq -r '.code');
 

while [ "$CODE" != "LimitExceeded" ]
do
   REQ=$(php ./index.php);

   CODE=$(echo "${REQ}"| jq -r '.code')
   MSG=$(echo "${REQ}"| jq -r '.message')

   echo $(date +%F_%H-%M-%S) - $CODE - $MSG;
   sleep ${RETRY_DELAY_TIME}m;
done
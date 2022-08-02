#!/bin/sh

if [[ -z $1 ]]
  then
    echo "Please enter the target director:"
    read DIRECTOR_OR_FILE
  else
    DIRECTOR_OR_FILE=$1
fi

echo "the argument you passed is ${DIRECTOR_OR_FILE}"

./tools/php-cs-fixer/vendor/bin/php-cs-fixer fix ${DIRECTOR_OR_FILE}
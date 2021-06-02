#! /bin/bash

PROGRESS_FILE=/tmp/jmqtt_dep;
if [ ! -z $1 ]; then
    PROGRESS_FILE=$1
fi

INSTALL_MOSQUITTO=1
if [ ! -z $2 ] && [ $2 -eq 1 -o $2 -eq 0 ]; then
    INSTALL_MOSQUITTO=$2
fi

echo 0 > ${PROGRESS_FILE}
sleep 1
echo 100 > ${PROGRESS_FILE}
rm ${PROGRESS_FILE}

echo "********************************************************"
echo "*             End dependancy installation              *"
echo "********************************************************"
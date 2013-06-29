#!/bin/sh
set -x
DATE=$(date +%F+%X)
THIS=$(realpath $0)
DIR=$(dirname ${THIS})
FIFO=$(realpath ${DIR}/../upload/pitch/fifo)
FIFODIR=$(dirname ${FIFO})
WWWDIR=$(realpath $DIR/../.)

if [ -a $FIFO ]; then 
    mv $FIFO "$FIFODIR/$DATE.fifo"; 
fi

touch $FIFO

tail -f $FIFO | php $WWWDIR/tracker.php
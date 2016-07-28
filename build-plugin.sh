#!/bin/bash

rm BuycraftPM.phar
phar pack -f BuycraftPM.phar -x "(.git|.idea)" .
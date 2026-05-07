#!/bin/bash

chmod +x scripts/*.sh

cd scripts

./pamScript.sh
./confdns.sh
./phpIniMod.sh
./lacIpxeBoot.sh
./visudodo.sh
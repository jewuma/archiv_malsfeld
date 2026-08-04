#!/bin/bash

rsync -av --delete ./dist/ /home/archiv/production/
rsync -av --delete ./php/ /home/archiv/production/php/

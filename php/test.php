<?php
$schlagworte = [1, 5, 7, "x"];
$schlagwortIds = implode(",", array_map('intval', $schlagworte));
print $schlagwortIds;

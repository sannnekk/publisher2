<?php

$content = file_get_contents('data/Titeldaten/titel.txt');
$content = explode("\n", $content)[2522];
$content = substr($content, 0, 80);

echo iconv('windows-1252', 'utf8//TRANSLIT', $content);

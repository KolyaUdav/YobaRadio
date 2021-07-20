<?php

function check_changes_in_audio_directory($target_arr, $checking_arr) {
    $changed_audio = []; // Аудио, подлежащие изменению в БД (удалённые, добавленные и т.д.)

    foreach ($target_arr as $t_audio) {
        if (!in_array($t_audio, $checking_arr)) {
            /*Добавляем в массив аудио-файлы, которые отсутствуют в БД, 
            либо о них имеются записи в БД, но в директории эти файлы отсутствуют */
            array_push($changed_audio, $t_audio); 
        }
    }

    return $changed_audio;
}

function add_audio_to_db($link, $audio_arr) {
    foreach ($audio_arr as $audio) {
        $query = 'INSERT INTO sounds (path, image_path) VALUES ("'.$audio.'", "'.NO_IMAGE.'")';
        mysqli_query($link, $query);
    }
}

function remove_audio_from_db($link, $audio_arr) {
    foreach ($audio_arr as $audio) {
        $query = 'DELETE FROM sounds WHERE path="'.$audio.'"';
        mysqli_query($link, $query);
    }
}

?>
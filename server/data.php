<?php

function get_data_from_dir($path, $pattern_for_filter_ext) {
    $data_from_dir = scandir($path);
    $data_from_dir = array_splice($data_from_dir, 2, count($data_from_dir)); // Удаляем первые два элемента, которые не содержат имён файлов (".", "..")
    $data_from_dir = filter_file_ext($data_from_dir, $pattern_for_filter_ext); // Добавляем исключительно файлы с расширениеми, прописанными в регулярках

    return $data_from_dir;
}

function get_data_from_db($link) {
    $db_result = get_rows_from_db($link); // Получаем из БД "сырые" данные
    $data_arr = get_data_from_query_result($db_result); // Сортируем данные в большой ассоциативный массив с ключами images и audio

    return $data_arr;
}

// Отфильтровываем исключительно музыкальные файлы/изображения
function filter_file_ext($file_arr, $pattern) {
    $filtered_files = [];

    foreach ($file_arr as $f) {
        if (preg_match($pattern, $f)) {
            array_push($filtered_files, $f);
        }
    }

    return $filtered_files;
}

function get_name_without_ext($filename) {
        $filename_info = pathinfo($filename);
        $filename_n_ext = $filename_info['filename'];

    return $filename_n_ext;
}

function get_rows_from_db($link) {
    $query = 'SELECT * FROM sounds';
    $result = mysqli_query($link, $query);
    
    return $result;
}

function get_data_from_query_result($result) {
    $general_assoc_arr = [];
    $audio_arr = [];
    $image_arr = [];

    while ($row = mysqli_fetch_assoc($result)) {
        array_push($audio_arr, $row['path']);
        array_push($image_arr, $row['image_path']);
    }

    $general_assoc_arr = ['audio' => $audio_arr, 'images' => $image_arr];

    return $general_assoc_arr;
}

?>
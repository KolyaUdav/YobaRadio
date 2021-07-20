<?php

function check_changes_in_images_directory($audio_arr, $images_arr) {
    $added_images = [];
    $audio_array = [];

    foreach ($audio_arr as $audio) {
        $audio_name = get_name_without_ext($audio);
        if (in_array($audio_name.'.jpg', $images_arr)) { // Проверяем ЕСТЬ ЛИ изображения с названием, идентичные названию композици
            array_push($added_images, $audio_name.'.jpg');
            array_push($audio_array, $audio);
        }
    }

    return ['audio' => $audio_array, 'images' => $added_images];
}

function check_deleted_images_in_directory($images_db, $images_dir) {
    $deleted_images = [];

    foreach ($images_db as $i_db) {
        if (!in_array($i_db, $images_dir) && $i_db != NO_IMAGE) {
            /* Добавляем в массив названия изображений,
            которые были удалены из директории */
            array_push($deleted_images, $i_db);
        }
    }

    return $deleted_images;
}

function get_data_from_db_no_image($data_arr, $link) {
    $audio_arr = [];
    $images_arr = [];

    /**Ищем композиции, у которых отсутствует изображение */
    for ($i = 0; $i < count($data_arr['images']); $i++) {
        if ($data_arr['images'][$i] === 'NoImage.jpg') {
            array_push($audio_arr, $data_arr['audio'][$i]);
            array_push($images_arr, $data_arr['images'][$i]);
        }
    }

    $data_no_image_arr = ['audio' => $audio_arr, 'images' => $images_arr];

    return $data_no_image_arr;
}

function add_images_to_db($image_names, $link) {
    for ($i = 0; $i < count($image_names['audio']); $i++) {
        $query = 'UPDATE sounds SET image_path = "'.$image_names['images'][$i].'" WHERE path = "'.$image_names['audio'][$i].'"';
        mysqli_query($link, $query);
    }
}

function remove_image_from_db($link, $images_arr) {
    foreach ($images_arr as $image) {
        $query = 'UPDATE sounds SET image_path = "'.NO_IMAGE.'" WHERE image_path = "'.$image.'"';
        mysqli_query($link, $query);
    }
}

?>
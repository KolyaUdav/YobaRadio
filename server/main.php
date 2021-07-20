<?php
    const AUDIO_PATH = '../music';
    const IMAGES_PATH = '../images';

    const NO_IMAGE = 'NoImage.jpg';

    const AUDIO_PATTERN = '/\.(?:mp3|m4a)$/i';
    const IMAGES_PATTERN = '/\.(?:jpg)$/i';

    $has_changed = false; // Были ли внесены изменения в БД или в директорию

    $link = mysqli_connect('localhost', 'root', '', 'yobaradio_db');

    if (mysqli_connect_errno()) {
        printf('Не удалось подключиться: %s', mysqli_connect_error());
        exit();
    }

    $audio_from_dir = get_data_from_dir(AUDIO_PATH, AUDIO_PATTERN); // Получаем массив аудио из директории

    $data_arr = get_data_from_db($link);

    $new_audio = check_changes_in_audio_directory($audio_from_dir, $data_arr['audio']); // Проверяем наличие в директории новых композиций

    // Добавляем записи о новых аудио в БД
    if (count($new_audio) > 0) {
        add_data_to_db($link, $new_audio);
        $has_changed = true;
    }

    $deleted_audio = check_changes_in_audio_directory($data_arr['audio'], $audio_from_dir); // Проверяем в базе наличие аудио, удалённых из директории

    // Удаляем записи в БД об удалённых из директории композициях
    if (count($deleted_audio) > 0) {
        remove_audio_from_db($link, $deleted_audio);
        $has_changed = true;
    }

    // Работа с изображениями

    $images_from_dir = get_data_from_dir(IMAGES_PATH, IMAGES_PATTERN); // Получаем массив изображений из директории

    if ($has_changed) {
        /* Если были внесены какие-либо изменения (добавление/удаление композиций),
        тогда заполняем $data_arr обновлёнными данными из БД, в противном случае 
        нет необходимости выполнять дополнительный запрос для актуализации данных в $data_arr */
        $data_arr = get_data_from_db($link);
    }
    
    $new_images = check_changes_in_images_directory($data_no_image_arr['audio'], $images_from_dir);

    if (count($new_images) > 0) {
        add_images_to_db($new_images, $link);
    }

    $deleted_images = check_deleted_images_in_directory($data_arr['images'], $images_from_dir);
    if (count($deleted_images) > 0) {
        remove_image_from_db($link, $deleted_images);
    }

    echo json_encode(get_data_from_db($link)); // Передаём данные пользователю, учитывая все изменения в БД

    function get_data_from_dir($path, $pattern_for_filter_ext) {
        $data_from_dir = scandir($path);
        $data_from_dir = array_splice($data_from_dir, 2, count($data_from_dir));
        $data_from_dir = filter_file_ext($data_from_dir, $pattern_for_filter_ext);

        return $data_from_dir;
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

    function check_changes_in_audio_directory($target_arr, $checking_arr) {
        $changed_audio = []; // Аудио, подлежащие изменению в БД (удалённые, добавленные и т.д.)

        foreach ($target_arr as $t_audio) {
            if (!in_array($t_audio, $checking_arr)) {
                array_push($changed_audio, $t_audio);
            }
        }

        return $changed_audio;
    }

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
                array_push($deleted_images, $i_db);
            }
        }

        return $deleted_images;
    }

    function add_data_to_db($link, $audio_arr) {
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

    function get_data_from_db($link) {
        $db_result = get_rows_from_db($link); // Получаем из БД "сырые" данные
        $data_arr = get_data_from_query_result($db_result); // Сортируем данные в большой ассоциативный массив с ключами images и audio

        return $data_arr;
    }

    function get_data_from_db_no_image($link) {
        $query = 'SELECT * FROM sounds WHERE image_path = "'.NO_IMAGE.'"';
        $result = mysqli_query($link, $query);
        $data_arr = get_data_from_query_result($result);

        return $data_arr;
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
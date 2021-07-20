<?php

    require_once('config.php'); // Различные необходимые константы
    require_once('data.php'); // Функции, направленные на работу с обобщёнными данными (аудио и изображения вместе)
    require_once('audio.php'); // Функции, отвечающие за работу с аудио-файлами
    require_once('images.php'); // Функции, отвечающие за работу с изображениями, принадлежащими аудио-файлам

    $has_changed = false; // Были ли внесены изменения в БД или в директорию

    $link = mysqli_connect('localhost', 'root', '', 'yobaradio_db');

    if (mysqli_connect_errno()) {
        printf('Не удалось подключиться: %s', mysqli_connect_error());
        exit();
    }

    $images_from_dir = get_data_from_dir(IMAGES_PATH, IMAGES_PATTERN); // Получаем массив изображений из директории
    $audio_from_dir = get_data_from_dir(AUDIO_PATH, AUDIO_PATTERN); // Получаем массив аудио из директории

    /**Работа с аудио */
    
    $data_arr = get_data_from_db($link); // Получаем данные из БД

    $new_audio = check_changes_in_audio_directory($audio_from_dir, $data_arr['audio']); // Проверяем наличие в директории новых композиций

    // Добавляем записи о новых аудио в БД
    if (count($new_audio) > 0) {
        add_audio_to_db($link, $new_audio);
        $has_changed = true;
    }

    $deleted_audio = check_changes_in_audio_directory($data_arr['audio'], $audio_from_dir); // Проверяем в базе наличие аудио, удалённых из директории

    // Удаляем записи в БД об удалённых из директории композициях
    if (count($deleted_audio) > 0) {
        $non_deleted_images = check_deleted_images_in_db($deleted_audio, $images_from_dir);
        if (count($non_deleted_images) > 0) {
            remove_images_from_directory($non_deleted_images);
            $images_from_dir = get_data_from_dir(IMAGES_PATH, IMAGES_PATTERN); // Обновляем массив изображений из директории
        }
        remove_audio_from_db($link, $deleted_audio);
        $has_changed = true;
    }

    // Работа с изображениями

    if ($has_changed) {
        /* Если были внесены какие-либо изменения (добавление/удаление композиций),
        тогда заполняем $data_arr обновлёнными данными из БД, в противном случае 
        нет необходимости выполнять дополнительный запрос для актуализации данных в $data_arr */
        $data_arr = get_data_from_db($link);
    }

    /**Получаем ассоциативный двумерный массив аудио из БД, у которых отсутствует изображение */
    $data_no_image_arr = get_data_from_db_no_image($data_arr, $link);
    /**Проверяем, были ли добавлены в директорию images новые изображения,
     * информация о которых ещё не была помещена в БД
     */
    $new_images = check_changes_in_images_directory($data_no_image_arr['audio'], $images_from_dir);
    /**Если были, то добавляем их в БД */
    if (count($new_images) > 0) {
        add_images_to_db($new_images, $link);
    }
    /**Проверяем, есть ли информация в БД об изображениях, удалённых из директории */
    $deleted_images = check_deleted_images_in_directory($data_arr['images'], $images_from_dir);
    /**Если такие записи есть, то заменяем их на заглушку NoImage.jpg */
    if (count($deleted_images) > 0) {
        remove_image_from_db($link, $deleted_images);
    }

    echo json_encode(get_data_from_db($link)); // Передаём данные пользователю, учитывая все изменения в БД

?>
<?php

namespace Nazarov {

function pluginCommands(string $dir, array $forbidden): array {
    $result = [];

    // Проверка существования целевой директории
    if (!is_dir($dir)) {
        return $result;
    }

    // Получаем список файлов с обработкой ошибок
    $files = scandir($dir);
    if ($files === false) {
        return $result;
    }

    foreach ($files as $file) {
        $path = $dir . '/' . $file;

        // Пропускаем системные элементы и директории
        if (in_array($file, ['.', '..']) || !is_file($path)) {
            continue;
        }

        // Проверяем расширение .txt
        if (pathinfo($path, PATHINFO_EXTENSION) !== 'txt') {
            continue;
        }

        // Извлекаем базовое имя без расширения
        $filename = pathinfo($path, PATHINFO_FILENAME);

        // Проверка на запрещённые имена
        if (in_array($filename, $forbidden)) {
            continue;
        }

        // Чтение содержимого файла
        $content = @file_get_contents($path);
        if ($content !== false) {
            $result[$filename] = $content;
        }
    }

    return $result;
}


}

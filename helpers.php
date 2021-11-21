<?php


class Helpers
{

    /**
     * Записывает var_export($smth) в лог файл. Если указан $echo - выводит так же на экран
     * @param $smth
     * @param bool $echo
     */
    public static function log($smth, bool $echo = false)
    {
        $str = date('[Y-m-d H:i:s]') . ': ' . var_export($smth, 1) . "\n";
        if ($echo) {
            echo $str;
        }

        file_put_contents('log.log', file_get_contents('log.log') . $str);
    }

    /**
     * Проверяет полученное сообщение на наличие zip архива внутри
     * @param $telegramMessage
     * @return bool
     */
    public static function isZipArchive($telegramMessage): bool
    {
        return isset($telegramMessage->document) && $telegramMessage->document->mime_type === 'multipart/x-zip';
    }

    /**
     * Сохраняет в локальную папку полученный архив и возвращает путь до архива
     * @param $telegramDocument
     * @return string
     */
    public static function storeFileFromTelegramToLocalStorage($telegramDocument): string
    {
        $file = json_decode(file_get_contents(BASE_TELEGRAM_URL . 'getFile?file_id=' . $telegramDocument->file_id))->result;
        $localFile = __DIR__ . '/upload/zips/' . $telegramDocument->file_unique_id . '.zip';
        self::log($file, true);
        file_put_contents(
            $localFile,
            file_get_contents('https://api.telegram.org/file/bot' . BOT_TOKEN . '/' . $file->file_path)
        );

        return $localFile;
    }

    /**
     * Распаковывает указанный файл в папку с уникальным именем
     * @param $file
     * @return string
     * @throws Exception
     */
    public static function unpack($file): string
    {
        $zip = new ZipArchive();

        if ($zip->open($file) === false) {
            throw new Exception('Ошибка открытия файла архива');
        }

        $localUnpackedDir = __DIR__ . '/upload/unpacked/' . self::generateLocalFileName();

        $zip->extractTo($localUnpackedDir);
        $zip->close();

        return $localUnpackedDir;
    }

    public static function executeCommand($dir): string
    {
        exec(EXECUTE_COMMAND . ' ' . $dir . ' 2>&1', $out, $return_var);
        return implode("\n", $out);
    }

    public static function generateLocalFileName(): string
    {
        return uniqid();
    }

    /**
     * Отправляет в чат $chatId сообщение $text
     * @param $chatId
     * @param $text
     */
    public static function sendMessage($chatId, $text)
    {
        file_get_contents(BASE_TELEGRAM_URL . 'sendMessage?text=' . urlencode($text) . '&chat_id=' . $chatId);
    }
}

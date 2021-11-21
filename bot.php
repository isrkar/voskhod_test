<?php

include 'conf.php';


if (!is_file('last_update_id')) {
    file_put_contents('last_update_id', '');
}

while (true) {
    $lastUpdateId = (((int)trim(file_get_contents('last_update_id'))) + 1) ?? 0;

    $updates = file_get_contents(BASE_TELEGRAM_URL . 'getUpdates?offset=' . $lastUpdateId . '&allowed_updates[]=message&limit=1');
    $updates = json_decode($updates);

    if (!isset($updates->result[0])) {
        echo "updates->result[0] is empty, skip processing.\n";
        sleep(SLEEP_TIME);
        continue;
    }

    Helpers::log($updates->result, true);

    $lastUpdateId = (int)$updates->result[0]->update_id;

    file_put_contents('last_update_id', $lastUpdateId);

    $currentMessage = $updates->result[0]->message;

    if (Helpers::isZipArchive($currentMessage)) {
        try {
            $localArchive = Helpers::storeFileFromTelegramToLocalStorage($currentMessage->document);
            $localUnpackedDir = Helpers::unpack($localArchive);
            $out = Helpers::executeCommand($localUnpackedDir);
            file_put_contents($localUnpackedDir . '/__result.txt', $out);
            Helpers::sendMessage($currentMessage->from->id, "Результат обработки:\n" . $out);
        } catch (\Throwable $e) {
            Helpers::log($lastUpdateId . ' file processing error: ' . $e->getMessage());
        }
    } else {
        Helpers::sendMessage($currentMessage->from->id, 'Нет файла для обработки.');
    }

    sleep(SLEEP_TIME);
}

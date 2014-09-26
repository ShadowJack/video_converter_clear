video_converter_clear
=====================

##DB Schema
 * id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
 * title VARCHAR(100)
 * FLV VARCHAR(100)
 * MP4 VARCHAR(100)
 * created DATETIME DEFAULT NULL
 * status VARCHAR(1) - c: converting, f - finished

##Actions
* GET: /videos - получить список всех видео
* GET: /videos/new - форма для добавления нового файла
* POST: /videos - залить файл
* GET: /videos/:id/flv - скачать flv
* GET: /videos/:id/mp4 - скачать mp4
* GET: /videos/:id/meta - получить метаданные
* DELETE: /video/:id/- удалить flv и mp4 видеозаписи, соответствующие id

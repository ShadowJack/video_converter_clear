video_converter_clear
=====================

DB Schema:
 * id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
 * title VARCHAR(100)
 * FLV VARCHAR(100)
 * MP4 VARCHAR(100)
 * created DATETIME DEFAULT NULL
 * status VARCHAR(1) - c: converting, f - finished

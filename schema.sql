CREATE TABLE sensors
(
    sensor_id SMALLINT UNSIGNED PRIMARY KEY                NOT NULL,
    face      ENUM ('north', 'east', 'south', 'west') NOT NULL,
    status    ENUM ('removed', 'active') NOT NULL DEFAULT 'active',
    INDEX face (face)
);

CREATE TABLE temperatures
(
    id          BIGINT PRIMARY KEY AUTO_INCREMENT,
    sensor_id   SMALLINT UNSIGNED NOT NULL,
    timestamp   TIMESTAMP    NOT NULL,
    temperature DOUBLE       NOT NULL,
    INDEX sensor_id_timestamp (sensor_id,timestamp),
    FOREIGN KEY (sensor_id) REFERENCES sensors (sensor_id) ON DELETE CASCADE
);
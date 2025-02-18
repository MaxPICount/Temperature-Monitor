CREATE TABLE sensors
(
    sensor_id SMALLINT UNSIGNED PRIMARY KEY                NOT NULL,
    face      ENUM ('north', 'east', 'south', 'west') NOT NULL
);

CREATE TABLE temperatures
(
    id          BIGINT PRIMARY KEY AUTO_INCREMENT,
    sensor_id   SMALLINT UNSIGNED NOT NULL,
    timestamp   TIMESTAMP    NOT NULL,
    temperature DOUBLE       NOT NULL,
    FOREIGN KEY (sensor_id) REFERENCES sensors (sensor_id) ON DELETE CASCADE
);

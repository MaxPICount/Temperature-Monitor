#!/bin/bash
interval=10 # minutes
for i in $(seq 1 20); do # sensors
  face=$(shuf -e north east south west -n 1)
  for t in $(seq 0 $((7 * 24 * 60 / interval))); do # each 10 minutes
     echo -en $i $t "\r"
    timestamp=$(( $(date +%s) - t * 60 * interval))
    temperature=$(( timestamp % 20 + RANDOM % 10))

    if (( $t % 1279 == 0 )); then # malfunctions: Missing Data
      continue
    fi

    if (( $t % 311 == 0 )); then # malfunctions: Wrong Data
      temperature=151
    fi

    curl -X POST http://localhost/sensor-data \
         -H "Content-Type: application/json" \
         -d "{\"id\": $i, \"face\": \"$face\", \"timestamp\": $timestamp, \"temperature\": $temperature}" \
         > /dev/null 2>&1 &

    if (( $t % 500 == 0 )); then
      wait
    fi
  done
done
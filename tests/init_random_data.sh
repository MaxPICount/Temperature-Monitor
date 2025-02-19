for i in $(seq 1 10000); do
  curl -X POST http://localhost/sensor-data \
       -H "Content-Type: application/json" \
       -d "{\"id\": $i, \"face\": \"$(shuf -e north east south west -n 1)\", \"timestamp\": $(date +%s), \"temperature\": $((20 + RANDOM % 10))}" &
done
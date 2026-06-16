cat {{ $log_path }} 2>/dev/null | awk -v since="$(date -d '1 minute ago' '+%d/%b/%Y:%H:%M' 2>/dev/null || date -v-1M '+%d/%b/%Y:%H:%M' 2>/dev/null)" '
BEGIN { requests=0; bytes=0; s2xx=0; s3xx=0; s4xx=0; s5xx=0; total_time=0; time_count=0; }
{
    match($0, /\[([^\]]+)\]/, ts)
    if (ts[1] >= since) {
        requests++
        match($0, /" ([0-9]+) ([0-9]+)/, resp)
        status = resp[1]+0
        bytes += resp[2]+0
        if (status >= 200 && status < 300) s2xx++
        else if (status >= 300 && status < 400) s3xx++
        else if (status >= 400 && status < 500) s4xx++
        else if (status >= 500) s5xx++
        if (match($0, /rt=([0-9]+\.[0-9]+)/, rt)) {
            t = rt[1]+0
            total_time += t
            times[++time_count] = t
        }
    }
}
END {
    avg_ms = (time_count > 0) ? (total_time / time_count * 1000) : 0
    p95_ms = 0
    if (time_count > 0) {
        n = asort(times)
        rank = int(0.95 * n)
        if (rank < 1) rank = 1
        if (rank > n) rank = n
        p95_ms = times[rank] * 1000
    }
    error_rate = (requests > 0) ? ((s4xx + s5xx) / requests * 100) : 0
    printf "requests:%d\n", requests
    printf "bytes:%d\n", bytes
    printf "status_2xx:%d\n", s2xx
    printf "status_3xx:%d\n", s3xx
    printf "status_4xx:%d\n", s4xx
    printf "status_5xx:%d\n", s5xx
    printf "avg_response_ms:%.2f\n", avg_ms
    printf "p95_response_ms:%.2f\n", p95_ms
    printf "error_rate:%.2f\n", error_rate
}'

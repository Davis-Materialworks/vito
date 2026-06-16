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
        match($0, / ([0-9]+\.[0-9]+)$/, rt)
        if (rt[1]+0 > 0) { total_time += rt[1]+0; time_count++ }
    }
}
END {
    avg_ms = (time_count > 0) ? (total_time / time_count * 1000) : 0
    error_rate = (requests > 0) ? ((s4xx + s5xx) / requests * 100) : 0
    printf "requests:%d\n", requests
    printf "bytes:%d\n", bytes
    printf "status_2xx:%d\n", s2xx
    printf "status_3xx:%d\n", s3xx
    printf "status_4xx:%d\n", s4xx
    printf "status_5xx:%d\n", s5xx
    printf "avg_response_ms:%.2f\n", avg_ms
    printf "error_rate:%.2f\n", error_rate
}'

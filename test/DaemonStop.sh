ps -aux | grep "php Daemon.php"| grep -v "grep" | awk '{print $2}' | xargs kill -9

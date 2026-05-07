# SwpMemoryProfiler

Profiles peak memory usage per HTTP request in Shopware 6. Stores data in Log File

Built to size PHP `memory_limit` based on real measurements instead of guesses.

Analyse 

# Top 20 Memory-Hogs – numerisch nach extrahiertem MB-Wert
awk -F'|' '{
  m=$4; gsub(/[^0-9.]/,"",m);
  printf "%7.1f MB | %s\n", m, $0
}' /var/www/vhosts/domain.tld/path/sw6-memory.log \
| sort -rn | head -20

# Peak gesamt
awk -F'|' '{m=$4; gsub(/[^0-9.]/,"",m); if(m+0>max)max=m+0} END{print "Peak gesamt:", max, "MB"}' \
  /var/www/vhosts/domain.tld/path/sw6-memory.log

# Peak pro Kontext
awk -F'|' '{
  ctx=$2; gsub(/ /,"",ctx);
  m=$4; gsub(/[^0-9.]/,"",m); m=m+0;
  if(m>peak[ctx]) peak[ctx]=m;
  count[ctx]++;
  sum[ctx]+=m;
}
END {
  for(c in peak) printf "%-6s | n=%5d | avg=%6.1f MB | peak=%6.1f MB\n",
    c, count[c], sum[c]/count[c], peak[c]
}' /var/www/vhosts/domain.tld/path/sw6-memory.log

## Requirements

- Shopware 6.6.x
- PHP 8.3+
- MariaDB 10.4+ / MySQL 8.0+

## Installation

```bash
cd custom/plugins/
unzip SwpMemoryProfiler.zip

cd ../..
php bin/console plugin:refresh
php bin/console plugin:install --activate SwpMemoryProfiler
php bin/console cache:clear
```

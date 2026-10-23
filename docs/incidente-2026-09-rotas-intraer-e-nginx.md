   auto <IF_INTERNA>
   iface <IF_INTERNA> inet static
   address <IP_DA_VM>/23
   up ip route add <REDE_INTRAER> via <GATEWAY_INTRAER> dev <IF_INTERNA>
   ```
3. A conectividade com o CENDOC e ICEA foi retestada e validada com `HTTP/1.1 200 OK`.

### 3.2. Resolução Dinâmica de DNS no Nginx
O arquivo `docker/nginx/default.conf` foi reconfigurado para utilizar o servidor DNS interno do Docker (`127.0.0.11`) e avaliar o upstream dinamicamente por meio de variável:
```nginx
resolver 127.0.0.11 valid=10s;

location ~ \.php$ {
    set $php_upstream php:9000;
    fastcgi_pass $php_upstream;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
    fastcgi_read_timeout 300;
}
```
Com isso, qualquer recriação ou troca de IP entre containers é assimilada pelo Nginx em no máximo 10 segundos, eliminando o erro 502 por IP estático em cache.

### 3.3. Criação do Comando de Auditoria e Recuperação Retroativa
Foi desenvolvido o comando Artisan `app/Console/Commands/ProcessarRetroativosCommand.php` (`php artisan bca:retroativo`):
* Permite definir intervalo de datas (`--de` e `--ate`).
* Baixa e extrai textos dos boletins não processados.

# Relatório Técnico de Incidente: Indisponibilidade de Downloads Intraer e Erro 502 no Nginx

**Data do Incidente:** 03 de Agosto de 2026 a 04 de Setembro de 2026  
**Data da Resolução:** 04 de Setembro de 2026  
**Sistemas Afetados:** Interface Web (HTTP 502) e Serviço de Busca/Download Automático de BCAs (`BaixarBcaJob`)  
**Impacto:** 32 dias sem download automático de novos boletins e impossibilidade de acesso à interface web.

---

## 1. Sintomas Observados

1. **Acesso Web Indisponível:** Qualquer requisição HTTP para a porta `18080` do servidor retornava `HTTP/1.1 502 Bad Gateway` após alguns segundos.
2. **Falha Contínua nos Jobs de Fila:** A cada hora, o job `App\Jobs\BaixarBcaJob` tentava baixar o boletim do dia e falhava após 2 minutos com erro de timeout:
   ```text
   production.WARNING: BCA CENDOC POST failed: cURL error 28: Connection timed out after 10001 milliseconds
   production.INFO: BCA [data]: CENDOC POST failed, brute-forcing ICEA 1-366...
   production.ERROR: BaixarBcaJob failed: App\Jobs\BaixarBcaJob has timed out.
   ```
3. **Acúmulo de Falhas:** Mais de 230 execuções horárias consecutivas falharam entre 03/08/2026 e 04/09/2026.

---

## 2. Diagnóstico e Causa Raiz

A investigação identificou **duas causas raízes independentes** que ocorreram concomitantemente durante manutenção realizada em 03/08/2026:

### Causa Raiz A: Perda de Rota para a Intraer no Host Linux
* **Topologia de Rede:** O servidor possui duas interfaces de rede físicas:
  * `ens192` (`161.24.238.125/24`): Interface externa/DMZ com gateway padrão `161.24.238.200`.
  * `ens160` (`10.132.64.125/23`): Interface interna da Intraer com gateway `10.132.64.200`.
* **A Falha:** A rota estática `10.0.0.0/8 via 10.132.64.200 dev ens160` foi perdida na tabela de rotas do kernel Linux. Como consequência, qualquer requisição para servidores da Intraer (`www.cendoc.intraer` em `10.32.88.1` ou `www.icea.intraer` em `10.132.8.80`) era roteada pelo gateway padrão (`161.24.238.200 dev ens192`), sofrendo timeout.
* **Por que a rota não subiu no boot:** No arquivo `/etc/network/interfaces`, a rota estava configurada incorretamente sem a diretiva `up`:
  ```text
  # ERRADO (ignorado pelo ifupdown do Debian):
  ip route add 10.0.0.0/8 via 10.132.64.200 dev ens160
  ```

### Causa Raiz B: IP do Upstream FastCGI Desatualizado no Nginx
* **A Falha:** O container `nginx` estava ativo continuamente há mais de 30 dias. Na inicialização, o Nginx resolveu o nome `php` para o IP interno Docker `192.168.16.4`.
* **Desalinhamento:** Em 03/08/2026, o container `php` foi reiniciado e recebeu o IP `192.168.16.6`. O IP `192.168.16.4` foi então atribuído ao container `queue`.
* **Impacto:** Como o arquivo `docker/nginx/default.conf` usava `fastcgi_pass php:9000;` estaticamente sem resolver dinâmico, o Nginx manteve em memória o IP antigo e continuou encaminhando o tráfego HTTP para o container `queue` (onde a porta 9000 está fechada), gerando `connect() failed (111: Connection refused) while connecting to upstream` e consequente erro `502 Bad Gateway`.

---

## 3. Soluções Implementadas

### 3.1. Restauração e Persistência da Rota da Intraer
1. A rota estática foi adicionada imediatamente ao kernel do host:
   ```bash
   ip route add 10.0.0.0/8 via 10.132.64.200 dev ens160
   ```
2. O arquivo `/etc/network/interfaces` foi corrigido para garantir persistência após reinicializações:
   ```text
   auto ens160
   iface ens160 inet static
   address 10.132.64.125/23
   up ip route add 10.0.0.0/8 via 10.132.64.200 dev ens160
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
* Cruza o conteúdo com o efetivo ativo.
* Suprime e-mails por padrão (`--enviar-emails` opcional), permitindo auditoria prévia pelo operador.

---

## 4. Plano de Recuperação Executado

1. **Varredura Retroativa (29/07/2026 a 03/09/2026):**
   * Todos os 26 dias úteis do período foram auditados.
   * Todos os boletins publicados (do BCA nº 132 ao 152) foram baixados e processados.
2. **Identificação de Ocorrências:**
   * Foram encontradas **22 ocorrências** envolvendo **17 militares** do efetivo em 7 datas diferentes.
3. **Notificações:**
   * Todas as 22 notificações individuais foram disparadas via fila para os respectivos militares através do servidor SMTP da FAB (`smtp.fab.mil.br`).
   * As ocorrências foram marcadas como `enviado_em` no banco de dados.
   * O compilado geral das ocorrências foi consolidado para ciência da SAD do GAC-PAC.
4. **Boletim do Dia (04/09/2026 - BCA nº 153):**
   * Processado normalmente com 3 ocorrências e e-mails enviados.

---

## 5. Lições Aprendidas e Prevenção

1. **Configuração de Rede Debian:** Comandos de rota estática em `/etc/network/interfaces` exigem obrigatoriamente a palavra-chave `up` para serem executados durante a ativação da interface.
2. **Resolução de Upstream no Nginx:** Nunca utilizar nomes de serviço Docker de forma estática em diretivas `fastcgi_pass` sem `resolver` e variável, sob risco de congelar IPs no cache de inicialização do Nginx.
3. **Monitoramento Ativo:** O monitoramento contínuo de jobs falhos na tabela `failed_jobs` permite a identificação precoce de falhas de conectividade de rede antes que afetem semanas de publicações.

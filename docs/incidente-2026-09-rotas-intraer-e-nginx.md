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
  * `<IF_EXTERNA>` (`<IP_EXTERNO>/24`): Interface externa/DMZ com gateway padrão `<GATEWAY_EXTERNO>`.
  * `<IF_INTERNA>` (`<IP_DA_VM>/23`): Interface interna da Intraer com gateway `<GATEWAY_INTRAER>`.
* **A Falha:** A rota estática `<REDE_INTRAER> via <GATEWAY_INTRAER> dev <IF_INTERNA>` foi perdida na tabela de rotas do kernel Linux. Como consequência, qualquer requisição para servidores da Intraer (`<CENDOC>` em `<IP_CENDOC>` ou `<ICEA>` em `<IP_ICEA>`) era roteada pelo gateway padrão (`<GATEWAY_EXTERNO> dev <IF_EXTERNA>`), sofrendo timeout.
* **Por que a rota não subiu no boot:** No arquivo `/etc/network/interfaces`, a rota estava configurada incorretamente sem a diretiva `up`:
  ```text
  # ERRADO (ignorado pelo ifupdown do Debian):
  ip route add <REDE_INTRAER> via <GATEWAY_INTRAER> dev <IF_INTERNA>
  ```

### Causa Raiz B: IP do Upstream FastCGI Desatualizado no Nginx
* **A Falha:** O container `nginx` estava ativo continuamente há mais de 30 dias. Na inicialização, o Nginx resolveu o nome `php` para o IP interno Docker `<IP_DOCKER_A>`.
* **Desalinhamento:** Em 03/08/2026, o container `php` foi reiniciado e recebeu o IP `<IP_DOCKER_B>`. O IP `<IP_DOCKER_A>` foi então atribuído ao container `queue`.
* **Impacto:** Como o arquivo `docker/nginx/default.conf` usava `fastcgi_pass php:9000;` estaticamente sem resolver dinâmico, o Nginx manteve em memória o IP antigo e continuou encaminhando o tráfego HTTP para o container `queue` (onde a porta 9000 está fechada), gerando `connect() failed (111: Connection refused) while connecting to upstream` e consequente erro `502 Bad Gateway`.

---

## 3. Soluções Implementadas

### 3.1. Restauração e Persistência da Rota da Intraer
1. A rota estática foi adicionada imediatamente ao kernel do host:
   ```bash
   ip route add <REDE_INTRAER> via <GATEWAY_INTRAER> dev <IF_INTERNA>
   ```
2. O arquivo `/etc/network/interfaces` foi corrigido para garantir persistência após reinicializações:
   ```text

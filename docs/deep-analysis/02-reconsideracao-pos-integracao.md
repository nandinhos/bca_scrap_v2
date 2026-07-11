# Reconsideração após integração dos releases v1.0.1–v1.0.3

Data: 2026-07-11

## Escopo aplicado

Esta revisão considera uma única OM por instalação: VM, Docker, banco, Redis, volumes, firewall, administrador e SAD próprios. O BCA é ostensivo e o PDF local atende somente à intranet da própria OM. Não são necessários controles de segregação entre OMs, microserviços, banco central ou armazenamento compartilhado.

## Correções confirmadas

| Item | Situação | Evidência |
|---|---|---|
| Seeders de exemplos em produção | Corrigido | `EfetivoSeeder` e `PalavraChaveSeeder` retornam em `APP_ENV=production`; `AdminDevSeeder` já era restrito a local/testing. |
| Guarda da reanálise em produção | Corrigido | `bca:reanalisar` exige confirmação explícita ou `--force`, e `--ate` usa a data atual. |
| Link absoluto e guarda de envio | Parcial | O serviço passou a montar URL absoluta e consulta ocorrência já enviada; falta cobrir os caminhos assíncronos. |
| SAD configurável por OM | Parcial | `BCA_SAD_EMAIL` é lido pelo `config/bca.php`, mas o instalador ainda impede a configuração vazia e possui falhas abaixo. |

## Bloqueadores mínimos restantes

| Prioridade | Item | Por que é necessário |
|---|---|---|
| P0-1 | Corrigir e testar a instalação limpa | Com `set -u`, a leitura de `$MAIL_MAILER` pode abortar a instalação. O instalador grava `BCA_BASE_URL=` e `BCA_ICEA_URL=`, anulando os defaults de `config/bca.php` e quebrando a busca. Também deve instalar dependências/assets de um clone limpo. |
| P0-2 | Restringir a uma OM | O banco ainda aceita e a interface expõe múltiplas unidades. Bloquear a segunda OM torna o código coerente com a implantação decidida e evita um uso não suportado. |
| P0-3 | Isolar o harness de testes | A rota documentada de Pest direto pode herdar variáveis operacionais. Banco, fila e e-mail de teste devem ser impostos, não apenas presumidos. |
| P0-4 | Tornar reanálise/CSV recuperáveis | A reanálise apaga ocorrências antes do resultado substituto. Com `--redownload`, a supressão de e-mail não acompanha os jobs Redis e pode reenviar notificações. |
| P0-5 | Validar a ingestão BCA/PDF | Mesmo em rede fechada, a fonte HTTP, redirects, limite de bytes e assinatura PDF devem ser verificados antes do parser. É controle simples de integridade, não uma arquitetura nova. |
| P0-6 | Concluir confiabilidade de jobs e autorização interna | Notificações precisam de chave de deduplicação/estado de envio e as prévias/envios sensíveis precisam autorizar o objeto, não apenas a tela. |

## Controles que não entram no P0

- Separação de cache, SAD, palavras-chave e BCA por OM: já existe separação física por instalação.
- URL privada do PDF apenas por conter BCA ostensivo: pode permanecer acessível na intranet se não houver dados derivados/PII no URL, índice público ou exposição à Internet. A política da OM pode endurecer isso depois.
- MFA, SSO, cofre central de segredos, SIEM, Kubernetes, microserviços e object storage: adotar somente por requisito institucional ou gatilho de escala, não como pré-requisito técnico desta distribuição.
- Remover Redis: avaliar após medir a carga. Manter a composição atual é aceitável se a operação local já a suporta.

## Condição objetiva para piloto controlado

Concluir os seis itens P0, executar instalação limpa em VM equivalente, rodar a suíte com banco de teste isolado, simular download/análise/notificação sem duplicidade e restaurar um backup de PostgreSQL e volumes. TLS, regras de firewall e política de credenciais devem seguir a norma local da OM.

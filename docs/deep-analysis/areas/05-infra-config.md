# 05 — Infraestrutura, instalação e configuração

## Resumo

O Compose sobe no workspace, mas a distribuição não é segura nem reproduzível para outras unidades.

## Implementado

- Containers PHP rodam com usuário não-root.
- PostgreSQL/Redis não expõem portas por padrão; Nginx, queue e scheduler iniciam no Compose.

## Lacunas

| Título | Severidade | Detalhe | Arquivos |
|---|---|---|---|
| Backup/restore | high | Sem RPO/RTO, cópia externa, criptografia ou restore testado. | `docker-compose.yml:83` |
| Monitoramento | high | Health não mede fila, scheduler, Redis, SMTP ou storage. | `app/Http/Controllers/HealthController.php:15` |
| Gestão de segredos | high | Sem cofre, rotação ou permissões restritivas de dotenv. | `install.sh:211` |

## Falhas

### ✅ Confirmada — instalador e transporte são inseguros

**critical.** Fluxo recomendado executa `main` remoto, clona branch mutável, publica HTTP e imprime senha administrativa. Evidência: `README.md:33`, `install.sh:36`, `install.sh:337`, `docker-compose.yml:4`.

### ✅ Confirmada — clone limpo não instala

**high.** O instalador chama migration sem `composer install`/build; `vendor` e `public/build` não são rastreados. Evidência: `install.sh:261`, `install.sh:299`, `docker/php/Dockerfile:42`, `.gitignore:18`.

### ⚪ Não revalidada nesta rodada — Redis e produção

**high.** Redis não possui auth/persistência; serviços principais não têm restart policy; health/metrics são públicos. Evidência: `docker-compose.yml:94`, `routes/web.php:14`.

# ✅ Checklist Completo de Migração

## 📋 Fase 0: Pré-Projeto (Semana 0) ⚠️ OBRIGATÓRIO

### Validações de Infraestrutura com TI
- [ ] Docker permitido no servidor de produção
- [ ] PostgreSQL 16 aprovado pela política de TI
- [ ] Redis aprovado (ou alternativa Memcached definida e documentada)
- [ ] Recursos do servidor verificados (≥4GB RAM, ≥20GB disco livre)
- [ ] Firewall: porta 8080 liberada internamente
- [ ] PHP 8.3+ disponível ou aprovado para instalação no servidor

### Conectividade e APIs Externas
- [ ] Acesso ao CENDOC validado a partir do servidor de produção
- [ ] Acesso ao ICEA validado a partir do servidor de produção
- [ ] Limite de requests das APIs externas investigado e documentado

### Backup e Baseline de Dados
- [ ] Backup completo do MySQL atual criado e validado (restauração testada)
- [ ] Script de migração MySQL→PostgreSQL testado em ambiente isolado
- [ ] Baseline documentado: quantidade de registros em efetivos, palavras_chaves, bcas
- [ ] PDFs existentes inventariados (quantidade e tamanho total)
- [ ] Backup armazenado em local seguro (fora do servidor de produção)

### Ambiente e Planejamento
- [ ] Repositório Git criado e configurado
- [ ] Ambiente de staging disponível (separado de dev e prod)
- [ ] [Plano de rollback](../ROLLBACK_PLAN.md) criado, revisado e aprovado
- [ ] Contatos de emergência documentados (dev, TI, responsável banco)

---

## 📋 Fase 1: Preparação (Semana 1)

### Setup Inicial
- [ ] Criar projeto Laravel 12
- [ ] Instalar Livewire 4
- [ ] Instalar Tailwind CSS 4
- [ ] Configurar Alpine.js
- [ ] Instalar Horizon
- [ ] Instalar pacotes Spatie

### Docker
- [ ] Criar docker-compose.yml
- [ ] Dockerfile PHP configurado
- [ ] Nginx configurado
- [ ] PostgreSQL rodando
- [ ] pgAdmin acessível
- [ ] Redis funcionando
- [ ] Volumes persistentes

### Banco de Dados
- [ ] Migration: efetivos
- [ ] Migration: bcas
- [ ] Migration: bca_emails
- [ ] Migration: palavras_chaves
- [ ] Migration: bca_ocorrencias
- [ ] Migration: bca_execucoes
- [ ] Migration: jobs table
- [ ] Índices criados
- [ ] Full-text search configurado

---

## 📋 Fase 2: Backend (Semanas 2-3)

### Models
- [ ] Model Efetivo + relationships
- [ ] Model Bca + relationships
- [ ] Model BcaEmail
- [ ] Model PalavraChave
- [ ] Model BcaOcorrencia
- [ ] Model BcaExecucao
- [ ] Factories criadas
- [ ] Seeders criados

### Services
- [ ] BcaDownloadService
- [ ] BcaProcessingService
- [ ] EfetivoAnalysisService
- [ ] CendocApiService
- [ ] Testes unitários services (80%+)

### Jobs
- [ ] BaixarBcaJob
- [ ] ProcessarBcaJob
- [ ] AnalisarEfetivoJob
- [ ] EnviarEmailNotificacaoJob
- [ ] Testes de jobs

### Commands
- [ ] BuscaBcaAutomaticaCommand
- [ ] LimparBcasAntigosCommand
- [ ] ReenviarEmailsFalhosCommand
- [ ] Testes de commands

### Events & Listeners
- [ ] MilitarEncontradoEvent
- [ ] BcaProcessadoEvent
- [ ] Listeners configurados

---

## 📋 Fase 3: Frontend (Semanas 4-5)

### Componentes Livewire - Busca
- [ ] BuscaBca
- [ ] ResultadoBusca
- [ ] PalavrasChaveSelector
- [ ] Views Blade
- [ ] Testes de componentes (meta: ≥60% cobertura — Livewire é intrinsecamente complexo de testar)

### Componentes Livewire - Efetivo
- [ ] ListagemEfetivo
- [ ] FormularioEfetivo
- [ ] ExclusaoMassa
- [ ] Views Blade
- [ ] Testes de componentes

### Componentes Livewire - Palavras-chave
- [ ] ListagemPalavras
- [ ] GestorPalavras
- [ ] Views Blade
- [ ] Testes de componentes

### Layout e UI
- [ ] Layout principal (app.blade.php)
- [ ] Componentes UI (button, badge, modal)
- [ ] Tailwind configurado
- [ ] Responsividade testada
- [ ] Navegação funcionando

### Assets
- [ ] app.css compilado
- [ ] app.js compilado
- [ ] Flatpickr integrado
- [ ] Alpine.js funcionando
- [ ] Build production OK

---

## 📋 Fase 4: Otimização (Semana 6)

### Cache
- [ ] Redis configurado
- [ ] Cache em Services
- [ ] Cache de queries
- [ ] Cache de views
- [ ] Cache de config/routes

### Performance - Database
- [ ] Índices otimizados
- [ ] Full-text search testado
- [ ] N+1 queries resolvidos
- [ ] Lazy loading implementado
- [ ] Eager loading onde necessário

### Performance - Busca
- [ ] Busca paralela implementada
- [ ] Timeout configurado
- [ ] Retry logic implementado
- [ ] Benchmarks < 3s

### Performance - Análise
- [ ] PostgreSQL FTS funcionando
- [ ] Snippets otimizados
- [ ] Benchmarks < 1s

### Testes de Performance
- [ ] Load testing
- [ ] Stress testing
- [ ] Benchmarks documentados
- [ ] Metas atingidas

---

## 📋 Fase 5: Deploy (Semana 7)

### Migração de Dados
- [ ] Backup MySQL criado
- [ ] Script migração testado
- [ ] Dados efetivos migrados
- [ ] Dados palavras-chave migrados
- [ ] Dados emails migrados (opcional)
- [ ] PDFs copiados
- [ ] Validação dados OK

### Staging
- [ ] Ambiente staging configurado
- [ ] Deploy em staging
- [ ] Testes smoke OK
- [ ] Testes integração OK
- [ ] UAT concluído
- [ ] Bugs corrigidos

### Produção
- [ ] Variáveis .env configuradas
- [ ] Secrets configurados
- [ ] Backup produção atual
- [ ] Deploy produção
- [ ] Migrations rodadas
- [ ] Seeders rodados (se necessário)
- [ ] Cache criado
- [ ] Horizon iniciado
- [ ] Scheduler rodando

### Pós-Deploy
- [ ] Smoke tests OK
- [ ] Monitoramento ativo
- [ ] Logs sendo coletados
- [ ] Métricas baseline capturadas
- [ ] Alertas configurados

---

## 📋 Documentação e Treinamento

### Documentação
- [ ] README atualizado
- [ ] Docs técnicas completas
- [ ] API documentada (se houver)
- [ ] Diagramas atualizados
- [ ] Runbook criado

### Treinamento
- [ ] Equipe treinada
- [ ] Manual do usuário criado
- [ ] FAQ documentado
- [ ] Vídeos tutoriais (opcional)

---

## 📋 Qualidade e Segurança

### Testes
- [ ] Testes unitários Services/Jobs (≥80% cobertura — lógica de negócio crítica)
- [ ] Testes unitários Livewire Components (≥60% cobertura)
- [ ] Testes de integração com banco real
- [ ] Testes E2E/smoke (flows principais com Laravel Dusk ou similar)
- [ ] Todos os testes passando (zero falhas)

### Segurança
- [ ] CSRF protection ativo
- [ ] XSS prevention verificado
- [ ] SQL injection prevenido (Eloquent)
- [ ] Rate limiting configurado
- [ ] Secrets não no repositório
- [ ] HTTPS configurado (produção)

### Code Quality
- [ ] PHPStan sem erros
- [ ] Pint formatação OK
- [ ] Code review feito
- [ ] Debt técnico documentado

---

## 📋 Monitoramento e Manutenção

### Monitoramento
- [ ] Logs centralizados
- [ ] Métricas de performance
- [ ] Alertas configurados
- [ ] Dashboard de saúde

### Backup e Recovery
- [ ] Backup automático configurado
- [ ] Restore testado
- [ ] RTO/RPO definidos
- [ ] Plano de disaster recovery

### Rollback
- [ ] Plano de rollback documentado
- [ ] Rollback testado
- [ ] Backup pré-deploy OK

---

## 🎯 Critérios de Aceitação

### Performance
- [ ] Busca BCA < 3s (atual: 5-15s)
- [ ] Análise efetivo < 1s (atual: 3-5s)
- [ ] Extração PDF < 0.5s (atual: 2s)
- [ ] Interface reativa (sem page reload)

### Funcionalidade
- [ ] Busca automática funcionando
- [ ] Emails enviados corretamente
- [ ] CRUD efetivo OK
- [ ] CRUD palavras-chave OK
- [ ] Todas features sistema antigo

### Qualidade
- [ ] ≥80% cobertura testes (Services e Jobs — negócio crítico)
- [ ] ≥60% cobertura testes (Livewire Components — UI reativa)
- [ ] 0 erros PHPStan level 5
- [ ] 0 bugs críticos
- [ ] Documentação completa

### UX
- [ ] Interface responsiva
- [ ] Loading states claros
- [ ] Mensagens de erro úteis
- [ ] Navegação intuitiva

---

## ✅ Sign-off Final

### Aprovações Necessárias

**Técnico**:
- [ ] Lead Developer
- [ ] DevOps
- [ ] QA

**Negócio**:
- [ ] Product Owner
- [ ] Usuários chave (UAT)
- [ ] Gestão GAC-PAC

**Segurança**:
- [ ] Security review
- [ ] Compliance check

---

## 📊 Métricas de Sucesso (Primeiros 30 dias)

- [ ] 99.5%+ uptime
- [ ] <2 bugs críticos
- [ ] Performance metas atingidas
- [ ] 4.5/5 satisfação usuários
- [ ] 0 incidentes segurança

---

**Data de início**: ___/___/______
**Data prevista conclusão**: ___/___/______
**Data real conclusão**: ___/___/______

**Responsável**: _______________________
**Aprovado por**: _______________________

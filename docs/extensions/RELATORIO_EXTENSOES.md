# Documentação Técnica: Extensões Gemini CLI

Este documento detalha as extensões instaladas no ambiente, suas funcionalidades, comandos e aplicações ideais para otimizar o ciclo de desenvolvimento.

---

## Índice

1. [Superpowers](#1-superpowers)
2. [Conductor](#2-conductor)
3. [Gemini CLI Security](#3-gemini-cli-security)
4. [Context7](#4-context7)
5. [Code Review](#5-code-review)

---

## 1. Superpowers
**O que é:** Uma suíte avançada de habilidades (skills) que expande as capacidades cognitivas e operacionais do agente Gemini CLI, introduzindo metodologias de engenharia rigorosas.

*   **Como funciona:** Funciona através do comando `activate_skill`. Quando uma tarefa específica é identificada (ex: um bug ou uma nova feature), o agente ativa o protocolo correspondente.
*   **Habilidades Principais:**
    *   **TDD (Test-Driven Development):** Força a criação de testes antes da implementação.
    *   **Brainstorming:** Protocolo de exploração de requisitos antes de qualquer linha de código.
    *   **Systematic Debugging:** Abordagem metódica para identificar causas raiz de erros.
    *   **Using Git Worktrees:** Permite trabalhar em múltiplas branches de forma isolada sem poluir o diretório principal.
*   **Melhor aplicação:** Desenvolvimento de novas funcionalidades complexas, refatoração de sistemas legados e situações onde o rigor técnico é prioritário à velocidade.

## 2. Conductor
**O que é:** Uma extensão de orquestração de projetos focada na gestão de planos de implementação ("tracks") e documentação de arquitetura.

*   **Como funciona:** Gerencia uma estrutura de arquivos em `conductor/` que define o produto, stack tecnológica e fluxo de trabalho. Utiliza um protocolo de resolução de arquivos para manter a consistência entre a definição do produto e a implementação técnica.
*   **Componentes:**
    *   `tracks.md`: Registro de todas as frentes de trabalho ativas.
    *   `plan.md`: Plano de execução específico para uma track.
    *   `spec.md`: Especificação técnica detalhada.
*   **Melhor aplicação:** Gestão de projetos de médio e longo prazo, onde é necessário manter o alinhamento entre os requisitos de negócio (Product Definition) e o código.

## 3. Gemini CLI Security
**O que é:** Um framework de auditoria de segurança (SAST - Static Application Security Testing) integrado diretamente ao ciclo de desenvolvimento.

*   **Como funciona:** Atua como um engenheiro de segurança sênior. Utiliza o comando `/security:analyze` para realizar varreduras automatizadas ou revisões manuais guiadas por heurísticas de segurança modernas.
*   **Vulnerabilidades Cobertas:**
    *   Injeção (SQL, XSS, Command Injection).
    *   Segredos "hardcoded" (API Keys, senhas).
    *   Quebra de controle de acesso (IDOR).
    *   Violações de privacidade (Vazamento de PII em logs ou APIs de terceiros).
*   **Melhor aplicação:** Antes de cada merge em produção, durante a integração de APIs de terceiros e para conformidade com LGPD/GDPR.

## 4. Context7
**O que é:** Um oráculo de documentação e exemplos de código atualizados em tempo real.

*   **Como funciona:** Resolve IDs de bibliotecas e frameworks (ex: `/vercel/next.js`) e permite consultas diretas à documentação mais recente, superando a data de corte do treinamento do modelo de IA.
*   **Ferramentas:**
    *   `resolve-library-id`: Encontra o identificador correto da biblioteca.
    *   `query-docs`: Recupera snippets de código e referências de API oficiais.
*   **Melhor aplicação:** Implementação de tecnologias novas ou versões recentes de bibliotecas onde a sintaxe pode ter mudado.

## 5. Code Review
**O que é:** Uma ferramenta especializada na análise crítica de alterações de código e Pull Requests.

*   **Como funciona:** Fornece comandos específicos para revisão:
    *   `/code-review`: Analisa as alterações atuais no diretório de trabalho.
    *   `/pr-review`: Analisa um Pull Request específico integrando contexto do repositório e variáveis de ambiente.
*   **Foco da Análise:** Manutenibilidade, aderência a padrões de projeto, eficiência algorítmica e legibilidade.
*   **Melhor aplicação:** Garantia de qualidade (QA) contínua e auxílio na preparação de código para submissão em repositórios colaborativos.

---

*Este documento foi gerado automaticamente pelo Gemini CLI para o projeto `bca_scrap_v2`.*

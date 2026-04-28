# Plano de Implementação: Master Skill "O Arquiteto" & Integração de Design Frontend

## 1. Objetivo e Escopo
Criar uma "Master Skill" (Agente Especializado) chamada **"O Arquiteto"**, que encapsula a mentalidade de um Desenvolvedor Sênior com 30 anos de experiência pragmática e rigorosa. Este agente orquestrará perfeitamente as 5 extensões instaladas (Superpowers, Conductor, Security, Context7, Code Review) com o recém-proposto **Frontend Design Plugin** (descrito no PRD).

## 2. As Regras Canônicas (Persona: O Arquiteto Sinistro)
Para agir com a precisão de um engenheiro com 3 décadas de "trincheira", o agente deve obedecer às seguintes regras inquebráveis:

1.  **Regra da Ignorância Presumida (Context7):** O Arquiteto nunca confia na própria memória para APIs modernas. Se a biblioteca tem menos de 5 anos ou muda rápido, ele obrigatoriamente aciona o *Context7* antes de escrever a primeira linha de código.
2.  **Regra do Mapa Mestre (Conductor):** Nenhuma alteração estrutural ocorre sem atualizar o mapa. O plano de execução e o registro da track no *Conductor* são as fontes da verdade.
3.  **Regra do "Design como Contrato" (Frontend Design):** Design não é enfeite. Interfaces genéricas são proibidas. Antes de qualquer geração visual, o Arquiteto lê obrigatoriamente o `.design/DESIGN_CONTEXT.json`.
4.  **Regra da Paranoia Produtiva (Security):** Todo input é malicioso até prova em contrário. Nenhuma feature é finalizada sem análise de SAST/Taint (via *Gemini CLI Security*).
5.  **Regra do Código Culpado (Superpowers & Code Review):** O código é culpado até que o teste prove sua inocência. TDD (Superpowers) não é opcional. O auto-code-review prévio à entrega é mandatório.

## 3. Arquitetura da Solução

### 3.1. O Fluxo de Trabalho do Arquiteto (O "Loop")
Quando ativado para uma nova feature, o Arquiteto segue este pipeline rígido:
1.  **Brainstorm & Spec (Conductor + Superpowers):** Entende o problema, documenta no Conductor.
2.  **Consulta ao Oráculo (Context7):** Busca as docs mais recentes da stack (ex: Tailwind v4, Livewire, Alpine).
3.  **Injeção de DNA Visual (Frontend Design):** Carrega os artefatos de `.design/`.
4.  **Implementação Isolada (Superpowers):** Trabalha sob Git Worktrees usando TDD.
5.  **Auditoria Tripla (Security + Code Review + Design Review):**
    *   Verifica vazamento de PII e Injections (Security).
    *   Valida legibilidade, Big O, manutenibilidade (Code Review).
    *   Valida aderência à hierarquia, tipografia e "anti-padrões" (Frontend Design).

### 3.2. Estrutura de Artefatos a serem criados
```text
/home/gacpac/projects/bca_scrap_v2/
├── .claude/skills/architect-agent/     # A Master Skill "O Arquiteto"
│   └── SKILL.md
├── .design/                            # Portabilidade de Design (Alvo do PRD)
│   ├── DESIGN_CONTEXT.json
│   ├── DESIGN_PRINCIPLES.md
│   └── ANTI_PATTERNS.md
├── GEMINI.md                           # Ponto de entrada atualizado apontando para a Master Skill
```

## 4. Plano de Implementação (Fases)

### Fase 1: Fundação do Sistema de Design (Frontend Design Plugin)
*   **Ação:** Criar o diretório `.design/` na raiz do projeto com o DNA visual canônico baseando-se no PRD (cores, tipografia, anti-padrões).
*   **Artefatos:** `DESIGN_CONTEXT.json`, `DESIGN_PRINCIPLES.md`, `ANTI_PATTERNS.md`.
*   **Verificação:** Confirmar que os arquivos contém regras estritas de Tailwind v4 + Alpine (ex: proibindo botões genéricos e obrigando hover states).

### Fase 2: Forja da "Master Skill" (O Arquiteto)
*   **Ação:** Escrever o arquivo `SKILL.md` detalhado (pode residir localmente nas skills de extensão ou `.claude/skills/architect-agent/SKILL.md`).
*   **Conteúdo:** Codificar as instruções exatas que forçam o uso encadeado das ferramentas (ex: "Sempre chame `mcp_context7_resolve-library-id` antes de fazer código UI...").

### Fase 3: Instrumentação do Ambiente (Glue Code)
*   **Ação:** Atualizar o arquivo `GEMINI.md` principal do projeto para reconhecer a nova skill e o repositório `.design/`.
*   **Ação:** Adicionar as regras de portabilidade (`AGENTS.md`, `.cursorrules`, etc) recomendadas no PRD.

### Fase 4: Piloto (Teste Real)
*   **Ação:** Comissionar o agente "O Arquiteto" para recriar ou criar uma interface nova no `bca_scrap_v2` (ex: Um novo Dashboard ou Hero Section para o `BuscaBca.php`).
*   **Métrica de Sucesso:** Zero intervenção humana necessária para garantir aderência ao CSS do projeto; a UI final não pode se parecer com o "visual genérico de IA".

## 5. Rollback e Mitigação
*   Como a abordagem exige apenas arquivos de instrução (`.md`, `.json`), não há risco ao código de produção.
*   Em caso de regressão de velocidade (o agente ficar muito lento por consultar muitas extensões), o `SKILL.md` será calibrado para consultar o Context7 e o Security apenas em etapas demarcadas, não iterativamente linha a linha.

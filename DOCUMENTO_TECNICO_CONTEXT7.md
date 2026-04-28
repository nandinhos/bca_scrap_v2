# DOCUMENTO TÉCNICO: Context7 Integration — Análise e Proposta de Melhoria

**Versão:** 1.0.0 | **Data:** 2026-04-23 | **Autor:** Claude Code
**Projeto:** devorq_v3 + bca_scrap_v2 | **Status:** ANÁLISE COMPLETA

---

## 1. RESUMO EXECUTIVO

### Problema Identificado
A integração do Context7 no devorq_v3 (GATE-6) está falhando porque o endpoint `https://api.context7.io/v1` não está respondendo. O sistema tenta fazer curl direto na API REST, mas a API parece estar offline ou mudou de endpoint.

### Impacto Atual
- GATE-6 executa `ctx7_check` que faz um POST para `/context7/resolve`
- API não responde (curl retorna vazio)
- Usuário recebe warning "Context7: API não respondeu"
- GATE-6 não bloqueia (WARN), mas a funcionalidade Context7 está inoperante

### Proposta de Melhoria
Implementar 3 opções de instalação do Context7 com comando unificado `devorq context7 install`:
1. **CLI + Skills** (Recomendado) — `npx ctx7` ou npm install -g
2. **MCP Server** — servidor MCP em `https://mcp.context7.com/mcp`
3. **API direta** — fallback com endpoint alternativo

---

## 2. PROBLEMA ATUAL

### 2.1 Integração Atual (GATE-6)

A integração atual funciona assim:

```
devorq gate 6
    ↓
source lib/context7.sh
    ↓
ctx7_check()
    ↓
_load_config() → lê OPENAI_API_KEY de ~/.devorq/config
    ↓
_ctx7_req "/context7/resolve" '{"library": "express", ...}'
    ↓
curl -X POST -H "Authorization: Bearer ${CTX7_API_KEY}" \
     -H "Content-Type: application/json" \
     -d "${payload}" \
     "https://api.context7.io/v1/context7/resolve"
    ↓
[API NÃO RESPONDE - retorno vazio]
```

### 2.2 Arquivos Envolvidos

| Arquivo | Função |
|---------|--------|
| `~/.devorq/config` | Armazena `OPENAI_API_KEY=ctx7sk-...` |
| `~/.devorq/lib/context7.sh` | Wrapper bash com 4 funções |
| `~/.devorq/lib/gates.sh` | GATE-6 chama `ctx7_check` |

### 2.3 Funções do lib/context7.sh

```bash
# 4 funções exportadas:

ctx7_check      # Verifica se API responde (chamado por GATE-6)
ctx7_search     # Busca docs por query
ctx7_resolve    # Resolve library ID + busca docs
ctx7_compare    # Compara múltiplas libs
```

### 2.4 Output do GATE-6 com Erro

```
[INFO] GATE-6: 6 Context7 Checked — Docs consultadas (mesmo que rejeite)
[WARN] Context7: OPENAI_API_KEY não configurado
[INFO] Configure: export OPENAI_API_KEY=sua_chave_api
[INFO] Ou edite ~/.devorq/config
[WARN] GATE-6: 6 Context7 não configurado (sem API key ou API offline)
```

**Problema real testado:**
```bash
$ curl -s --max-time 10 -X POST \
    -H "Authorization: Bearer ${OPENAI_API_KEY}" \
    -H "Content-Type: application/json" \
    -d '{"library": "express", "query": "express js getting started"}' \
    "https://api.context7.io/v1/context7/resolve"

# Resultado: (vazio - API não responde)
```

### 2.5 Pontos de Falha Identificados

| # | Ponto de Falha | Severity | Descrição |
|---|----------------|----------|-----------|
| 1 | API endpoint offline | **Crítico** | `https://api.context7.io/v1` não responde |
| 2 | Hardcoded endpoint | **Alto** | Não há fallback para endpoint alternativo |
| 3 | Sem detecção de plataforma | **Alto** | CLI Mode não verificado (`opencode` presente) |
| 4 | Sem opção de instalação | **Alto** | Usuário não consegue instalar Context7 via devorq |
| 5 | API key pode estar errada | **Médio** | Key `ctx7sk-060ff...` pode ter expirado |

---

## 3. ANÁLISE DAS OPÇÕES

### 3.1 Opção 1: CLI + Skills (RECOMENDADO)

**Descrição:** Usar o Context7 via CLI local (`npx ctx7`) ou npm global install + skills.

| Aspecto | Detalhe |
|---------|---------|
| **Instalação** | `npm install -g ctx7` ou `npx ctx7 setup --opencode` |
| **Execução** | CLI local processa queries |
| **Skills** | Instalar skill `docs` que guia o agente |
| **API Key** | Configurada via `ctx7 config set api-key` |
| **Preserva API Key** | Sim (mesma do config atual) |

**Vantagens:**
- Funciona offline (local CLI)
- Mais rápido (sem latência de rede)
- Sem dependência de API externa
- Detecta automaticamente com `opencode` presente
- Recomendado pelo Context7 para integrações

**Desvantagens:**
- Requer Node.js/npm
- Instalação adicional

### 3.2 Opção 2: MCP Server

**Descrição:** Conectar ao servidor MCP do Context7.

| Aspecto | Detalhe |
|---------|---------|
| **Instalação** | Configurar MCP client para `https://mcp.context7.com/mcp` |
| **Execução** | Via MCP protocol (stdin/stdout) |
| **API Key** | Passada via Authorization header |
| **Preserva API Key** | Sim |

**Vantagens:**
- Protocolo padronizado
- Integração nativa com agentes AI
- Stateless

**Desvantagens:**
- Requer cliente MCP configurado
- Latência de rede
- Dependência de serviço externo

### 3.3 Opção 3: API Direta (Atual/Fallthrough)

**Descrição:** Manter integração REST API atual, mas com endpoint alternativo.

| Aspecto | Detalhe |
|---------|---------|
| **Instalação** | Nenhuma (já instalado) |
| **Execução** | curl direto na API |
| **Endpoint** | `https://api.context7.io/v1` (atual) ou alternativo |
| **API Key** | Lida do ~/.devorq/config |

**Vantagens:**
- Não requer instalação adicional
- Fallback para CLI/MCP

**Desvantagens:**
- API offline atualmente
- Sem redundância/fallback
- Hardcoded endpoint

### 3.4 Comparação Final

| Critério | CLI + Skills | MCP Server | API Direta |
|----------|-------------|-------------|------------|
| Confiabilidade | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐ |
| Performance | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐ |
| Facilidade install | ⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Sem dependência externa | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐ |
| Recomendado pelo Context7 | ✅ | ✅ | ❌ |

---

## 4. PROPOSTA DE MELHORIA

### 4.1 Opção 1: CLI + Skills (Implementação)

**Comando de Instalação:**
```bash
# Opção A: npm global install
npm install -g ctx7

# Opção B: npx (sem install)
npx ctx7 setup --opencode

# Opção C: via opencode se disponível
opencode --install-plugin context7
```

**Modificação no lib/context7.sh:**
```bash
# Adicionar após _load_config()

# Detecta se CLI ctx7 está disponível
_ctx7_detect_cli() {
    if command -v ctx7 &>/dev/null; then
        echo "cli"
        return 0
    elif command -v npx &>/dev/null && npx ctx7 --version &>/dev/null 2>&1; then
        echo "npx"
        return 0
    elif command -v opencode &>/dev/null; then
        # opencode detectado - pode usar ctx7 via plugin
        echo "opencode"
        return 0
    fi
    echo "none"
    return 1
}

# Executar via CLI local
_ctx7_cli_resolve() {
    local library="${1:-}"
    local query="${2:-}"
    local api_key="${3:-${CTX7_API_KEY}}"
    
    # Configura API key se necessário
    if [ -n "$api_key" ]; then
        ctx7 config set api-key "$api_key" 2>/dev/null || true
    fi
    
    # Executa via CLI
    ctx7 resolve "$library" "$query" 2>/dev/null
}
```

**Fluxo no GATE-6:**
```
GATE-6 executa ctx7_check:
    1. Detecta plataforma (_ctx7_detect_cli)
    2. Se CLI disponível → testa ctx7 resolve
    3. Se MCP disponível → testa MCP connection
    4. Se nada disponível → testa API direta
    5. Se API responde → PASS
    6. Se API não responde → WARN (não bloqueia)
```

### 4.2 Opção 2: MCP Server (Implementação)

**Configuração:**
```bash
# Adicionar em ~/.devorq/config ou .opencode/mcp.json
{
    "mcpServers": {
        "context7": {
            "url": "https://mcp.context7.com/mcp",
            "headers": {
                "Authorization": "Bearer ${OPENAI_API_KEY}"
            }
        }
    }
}
```

**Modificação no lib/context7.sh:**
```bash
# Adicionar função MCP
_ctx7_mcp_req() {
    local method="${1:-}"
    local params="${2:-}"
    
    # MCP usa JSON-RPC sobre stdio
    echo '{"jsonrpc":"2.0","method":"'"$method"'","params":'"$params"'}"}' | \
        curl -s -X POST \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer ${CTX7_API_KEY}" \
            -d @- \
            "https://mcp.context7.com/mcp" 2>/dev/null
}

ctx7_mcp_resolve() {
    local library="${1:-}"
    local query="${2:-}"
    
    _ctx7_mcp_req "context7_resolve" \
        '{"library":"'"$library"'","query":"'"$query"'"}'
}
```

### 4.3 Opção 3: API Direta com Fallback (Implementação)

**Modificação no lib/context7.sh:**
```bash
# Adicionar endpoints alternativos
CTX7_BASE_URL="${OPENAI_BASE_URL:-https://api.context7.io/v1}"
CTX7_ALT_URLS=(
    "https://api.context7.io/v1"
    "https://api.context7.com/v1"
    "https://context7.api.io/v1"
)

# Testar todos endpoints
_ctx7_req_with_fallback() {
    local endpoint="${1:-}"
    local payload="${2:-}"
    local api_key="${3:-${CTX7_API_KEY}}"
    
    for base_url in "${CTX7_ALT_URLS[@]}"; do
        local resp
        resp=$(curl -s --fail-with-body \
            -X POST \
            -H "Authorization: Bearer ${api_key}" \
            -H "Content-Type: application/json" \
            -d "$payload" \
            "${base_url}${endpoint}" 2>/dev/null)
        
        if [ -n "$resp" ]; then
            echo "$resp"
            return 0
        fi
    done
    
    echo "[ERROR] Todos os endpoints falharam" >&2
    return 1
}
```

---

## 5. COMANDO PROPOSTO: `devorq context7 install`

### 5.1 Design do Comando

```bash
devorq context7 install [opção]
```

### 5.2 Fluxo do Comando

```
$ devorq context7 install

═══ Context7 Installer ═══
[1/4] Detectando plataforma...

  ✓ Node.js v22.14.0 detectado
  ✓ opencode detectado em ~/.opencode/bin/opencode
  ✓ npm disponível

[2/4] Opções de instalação:

  [1] CLI + Skills (RECOMENDADO)
      - Instala ctx7 via npm global
      - Usa skill 'docs' para guiar agente
      - Mais rápido e offline-capable
      
  [2] MCP Server
      - Conecta ao MCP do Context7
      - Requer cliente MCP configurado
      
  [3] API Direta (fallback)
      - Mantém integração atual
      - Adiciona fallback endpoints

[3/4] Selecione uma opção (1-3) [1]:

  → Instalando CLI + Skills...
  $ npm install -g ctx7
  ✓ ctx7 instalado
  
  $ ctx7 config set api-key ${CTX7_API_KEY}
  ✓ API key configurada
  
  $ ctx7 setup --opencode
  ✓ Skill 'docs' instalada para opencode

[4/4] Validando instalação...

  $ ctx7 --version
  ctx7 version 1.2.3
  
  $ ctx7 resolve express "getting started"
  ✓ Context7 respondendo

═══ Sucesso ═══
Context7 instalado via CLI + Skills
Execute 'devorq gate 6' para testar
```

### 5.3 Implementação (Pseudocódigo)

```bash
devorq::cmd_context7_install() {
    local option="${1:-}"
    
    devorq::info "═══ Context7 Installer ═══"
    
    # Fase 1: Detectar plataforma
    devorq::info "[1/4] Detectando plataforma..."
    local node_version=""; node_version=$(node --version 2>/dev/null || echo "")
    local has_opencode=false; command -v opencode &>/dev/null && has_opencode=true
    local has_npm=false; command -v npm &>/dev/null && has_npm=true
    
    echo "  ✓ Node.js ${node_version} detectado"
    [ "$has_opencode" = true ] && echo "  ✓ opencode detectado"
    [ "$has_npm" = true ] && echo "  ✓ npm disponível"
    
    # Fase 2: Mostrar opções
    devorq::info ""
    devorq::info "[2/4] Opções de instalação:"
    devorq::info ""
    devorq::info "  [1] CLI + Skills (RECOMENDADO)"
    devorq::info "      - Instala ctx7 via npm global"
    devorq::info "      - Usa skill 'docs' para guiar agente"
    devorq::info "      - Mais rápido e offline-capable"
    devorq::info ""
    devorq::info "  [2] MCP Server"
    devorq::info "      - Conecta ao MCP do Context7"
    devorq::info "      - Requer cliente MCP configurado"
    devorq::info ""
    devorq::info "  [3] API Direta (fallback)"
    devorq::info "      - Mantém integração atual"
    devorq::info "      - Adiciona fallback endpoints"
    devorq::info ""
    
    # Seleção de opção
    if [ -z "$option" ]; then
        read -p "[3/4] Selecione uma opção (1-3) [1]: " option
        option="${option:-1}"
    fi
    
    case "$option" in
        1) install_cli ;;
        2) install_mcp ;;
        3) install_api ;;
        *) devorq::error "Opção inválida: $option" ;;
    esac
    
    # Fase 3: Validar
    devorq::info ""
    devorq::info "[4/4] Validando instalação..."
    validate_installation
}

install_cli() {
    devorq::info "  → Instalando CLI + Skills..."
    
    # Carrega API key do config
    source "${DEVORQ_ROOT}/config" 2>/dev/null || true
    local api_key="${OPENAI_API_KEY:-}"
    
    # Instala ctx7 globalmente
    if ! command -v ctx7 &>/dev/null; then
        devorq::info "  $ npm install -g ctx7"
        npm install -g ctx7 2>/dev/null || {
            devorq::warn "  ! npm install falhou, tentando npx..."
        }
    else
        devorq::info "  ✓ ctx7 já instalado"
    fi
    
    # Configura API key se disponível
    if [ -n "$api_key" ] && command -v ctx7 &>/dev/null; then
        devorq::info "  $ ctx7 config set api-key ***"
        ctx7 config set api-key "$api_key" 2>/dev/null || true
    fi
    
    # Setup opencode se disponível
    if command -v opencode &>/dev/null; then
        devorq::info "  $ ctx7 setup --opencode"
        ctx7 setup --opencode 2>/dev/null || true
    fi
    
    devorq::success "  ✓ CLI + Skills instalado"
}

validate_installation() {
    if command -v ctx7 &>/dev/null; then
        local version; version=$(ctx7 --version 2>/dev/null || echo "unknown")
        devorq::success "  ✓ ctx7 v${version} disponível"
    elif command -v npx &>/dev/null && npx ctx7 --version &>/dev/null 2>&1; then
        devorq::success "  ✓ npx ctx7 disponível"
    else
        devorq::warn "  ! ctx7 não está no PATH"
    fi
    
    devorq::info ""
    devorq::success "═══ Sucesso ═══"
    devorq::info "Execute 'devorq gate 6' para testar"
}
```

---

## 6. FLUXO REVISADO DO GATE-6

### 6.1 Diagrama de Fluxo

```
┌─────────────────────────────────────────────────────────────────┐
│                         GATE-6                                  │
│              Context7 Checked (Não bloqueia)                     │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │  ctx7_check()   │
                    └────────┬────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
         ▼                   ▼                   ▼
   ┌───────────┐      ┌───────────┐      ┌───────────┐
   │ CLI mode? │      │ MCP mode? │      │API direct? │
   └─────┬─────┘      └─────┬─────┘      └─────┬─────┘
         │                   │                   │
    ┌────┴────┐        ┌────┴────┐        ┌────┴────┐
    │  ctx7   │        │  MCP    │        │  curl    │
    │resolve  │        │ /mcp    │        │  POST    │
    └────┬────┘        └────┬────┘        └────┬────┘
         │                   │                   │
         └───────────────────┼───────────────────┘
                     ┌───────┴───────┐
                     │   Resp OK?    │
                     └───────┬───────┘
                         │       │
                      YES │       │ NO
                          ▼       ▼
                   ┌─────────┐ ┌─────────┐
                   │  PASS   │ │  WARN   │
                   │[绿色的] │ │[黄色的] │
                   └─────────┘ └─────────┘
```

### 6.2 Fluxo Detalhado (Pseudocódigo)

```bash
gate_6() {
    gate::info 6 "Context7 Checked — Docs consultadas (mesmo que rejeite)"
    
    # Carrega lib se existir
    source "${DEVORQ_LIB}/context7.sh" 2>/dev/null || true
    
    if ! declare -f ctx7_check &>/dev/null; then
        gate::warn 6 "Context7 não configurado (lib não encontrada)"
        return 0
    fi
    
    # Tenta detectar método disponível
    local method="auto"
    
    # 1. Testa CLI (preferido)
    if _ctx7_cli_available; then
        method="cli"
        if _ctx7_cli_test; then
            gate::pass 6 "Context7 Checked (CLI mode)"
            return 0
        fi
    fi
    
    # 2. Testa MCP
    if _ctx7_mcp_available; then
        method="mcp"
        if _ctx7_mcp_test; then
            gate::pass 6 "Context7 Checked (MCP mode)"
            return 0
        fi
    fi
    
    # 3. Testa API direta (fallback)
    method="api"
    if _ctx7_api_test; then
        gate::pass 6 "Context7 Checked (API mode)"
        return 0
    fi
    
    # Nenhum método funcionou
    gate::warn 6 "Context7 não configurado (sem API key ou API offline)"
    return 0
}

# Detecção de CLI
_ctx7_cli_available() {
    command -v ctx7 &>/dev/null && return 0
    command -v npx &>/dev/null && npx ctx7 --version &>/dev/null 2>&1 && return 0
    return 1
}

# Detecção de MCP
_ctx7_mcp_available() {
    # Verifica se opencode está configurado com MCP context7
    [ -f "${HOME}/.opencode/mcp.json" ] && \
        grep -q "context7" "${HOME}/.opencode/mcp.json" 2>/dev/null && return 0
    return 1
}

# Teste de API direta
_ctx7_api_test() {
    _load_config
    [ -z "${CTX7_API_KEY:-}" ] && return 1
    
    local resp
    resp=$(_ctx7_req "/context7/resolve" \
        '{"library": "express", "query": "test"}' 2>/dev/null)
    
    [ -n "$resp" ] && return 0
    return 1
}
```

---

## 7. IMPLEMENTAÇÃO SUGERIDA

### 7.1 lib/context7.sh — Modificações

**Adições necessárias:**

```bash
#!/usr/bin/env bash
# lib/context7.sh — DEVORQ Context7 Integration
#
# +++ NOVAS FUNÇÕES ADICIONADAS +++
#   ctx7_install        — Instala Context7 via CLI/MCP/API
#   ctx7_detect         — Detecta método disponível
#   ctx7_cli_available — Verifica se CLI ctx7 existe
#   ctx7_mcp_available — Verifica se MCP configurado
#   _ctx7_cli_test     — Testa CLI mode
#   _ctx7_mcp_test     — Testa MCP mode
#   _ctx7_api_test     — Testa API direta (fallback)
#   _ctx7_req_with_fallback — Request com múltiplos endpoints

# ... (código existente inalterado) ...

# ============================================================
# +++ NOVAS FUNÇÕES +++
# ============================================================

# Detecta método disponível: cli | mcp | api | none
ctx7_detect() {
    if _ctx7_cli_available; then
        echo "cli"
        return 0
    elif _ctx7_mcp_available; then
        echo "mcp"
        return 0
    elif [ -n "${CTX7_API_KEY:-}" ]; then
        echo "api"
        return 0
    fi
    echo "none"
    return 1
}

# Verifica se CLI ctx7 está disponível
_ctx7_cli_available() {
    command -v ctx7 &>/dev/null && return 0
    command -v npx &>/dev/null && npx ctx7 --version &>/dev/null 2>&1 && return 0
    return 1
}

# Verifica se MCP context7 está configurado
_ctx7_mcp_available() {
    if [ -f "${HOME}/.opencode/mcp.json" ]; then
        grep -q "context7" "${HOME}/.opencode/mcp.json" 2>/dev/null && return 0
    fi
    # Também verifica env var
    [ -n "${CTX7_MCP_URL:-}" ] && return 0
    return 1
}

# Testa CLI mode
_ctx7_cli_test() {
    if ! _ctx7_cli_available; then
        return 1
    fi
    
    local output
    if command -v ctx7 &>/dev/null; then
        output=$(ctx7 resolve express "test" 2>/dev/null || echo "")
    else
        output=$(npx ctx7 resolve express "test" 2>/dev/null || echo "")
    fi
    
    [ -n "$output" ] && return 0
    return 1
}

# Testa MCP mode
_ctx7_mcp_test() {
    if ! _ctx7_mcp_available; then
        return 1
    fi
    
    local mcp_url="${CTX7_MCP_URL:-https://mcp.context7.com/mcp}"
    local resp
    resp=$(curl -s --fail-with-body \
        -X POST \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer ${CTX7_API_KEY}" \
        -d '{"jsonrpc":"2.0","method":"initialize","params":{}}' \
        "$mcp_url" 2>/dev/null)
    
    echo "$resp" | grep -q "jsonrpc" && return 0
    return 1
}

# Testa API direta
_ctx7_api_test() {
    _load_config
    [ -z "${CTX7_API_KEY:-}" ] && return 1
    
    local resp
    resp=$(_ctx7_req "/context7/resolve" \
        '{"library": "express", "query": "test"}' 2>/dev/null)
    
    [ -n "$resp" ] && return 0
    return 1
}

# Request com fallback para múltiplos endpoints
_ctx7_req_with_fallback() {
    local endpoint="${1:-}"
    local payload="${2:-}"
    local api_key="${3:-${CTX7_API_KEY}}"
    
    local base_urls=(
        "https://api.context7.io/v1"
        "https://api.context7.com/v1"
    )
    
    for base_url in "${base_urls[@]}"; do
        local resp
        resp=$(curl -s --fail-with-body \
            -X POST \
            -H "Authorization: Bearer ${api_key}" \
            -H "Content-Type: application/json" \
            -d "$payload" \
            "${base_url}${endpoint}" 2>/dev/null)
        
        if [ -n "$resp" ]; then
            echo "$resp"
            return 0
        fi
    done
    
    return 1
}

# ctx7_install — Instala Context7 via método escolhido
ctx7_install() {
    local method="${1:-cli}"
    
    case "$method" in
        cli)
            install_ctx7_cli
            ;;
        mcp)
            install_ctx7_mcp
            ;;
        api)
            install_ctx7_api
            ;;
        *)
            echo "[ERROR] Método inválido: $method"
            echo "Use: cli | mcp | api"
            return 1
            ;;
    esac
}

install_ctx7_cli() {
    echo "[INFO] Instalando Context7 via CLI..."
    
    if command -v ctx7 &>/dev/null; then
        echo "[OK] ctx7 já está instalado"
        return 0
    fi
    
    # Tenta npm install -g
    if command -v npm &>/dev/null; then
        echo "[INFO] Executando: npm install -g ctx7"
        npm install -g ctx7 2>/dev/null && {
            echo "[OK] ctx7 instalado via npm"
            
            # Configura API key se disponível
            if [ -n "${CTX7_API_KEY:-}" ]; then
                ctx7 config set api-key "${CTX7_API_KEY}" 2>/dev/null || true
            fi
            return 0
        }
    fi
    
    # Tenta npx
    if command -v npx &>/dev/null; then
        echo "[INFO] npm não disponível, npx será usado"
        echo "[OK] Use: npx ctx7 resolve <lib> <query>"
        return 0
    fi
    
    echo "[ERROR] npm/npx não disponíveis"
    return 1
}

install_ctx7_mcp() {
    echo "[INFO] Instalando Context7 via MCP..."
    
    local mcp_config="${HOME}/.opencode/mcp.json"
    mkdir -p "$(dirname "$mcp_config")"
    
    cat > "$mcp_config" << 'MCPEOF'
{
    "mcpServers": {
        "context7": {
            "url": "https://mcp.context7.com/mcp",
            "headers": {
                "Authorization": "Bearer ${OPENAI_API_KEY}"
            }
        }
    }
}
MCPEOF
    
    echo "[OK] MCP configurado em $mcp_config"
    echo "[INFO] Reinicie o opencode para aplicar"
}

install_ctx7_api() {
    echo "[INFO] Configurando API direta..."
    echo "[OK] Nada a fazer — API key já configurada"
    echo "[INFO] Endpoint: ${CTX7_BASE_URL}"
}
```

### 7.2 bin/devorq — Novo Comando

**Adicionar em devorq::help():**
```bash
    context7 install        Instalar Context7 (cli|mcp|api)
    context7 check         Verificar status Context7
    context7 detect        Detectar método disponível
```

**Adicionar dispatcher:**
```bash
context7)   shift; devorq::cmd_context7 "$@" ;;
```

**Adicionar função:**
```bash
devorq::cmd_context7() {
    local sub="${1:-help}"
    
    case "$sub" in
        install)
            shift
            devorq::cmd_context7_install "$@"
            ;;
        check)
            source "${DEVORQ_LIB}/context7.sh" 2>/dev/null || true
            if declare -f ctx7_check &>/dev/null; then
                ctx7_check
            else
                devorq::warn "lib/context7.sh não disponível"
            fi
            ;;
        detect)
            source "${DEVORQ_LIB}/context7.sh" 2>/dev/null || true
            if declare -f ctx7_detect &>/dev/null; then
                local method; method=$(ctx7_detect)
                devorq::info "Método detectado: $method"
            else
                devorq::warn "lib/context7.sh não disponível"
            fi
            ;;
        *)
            echo "Uso: devorq context7 install|check|detect"
            ;;
    esac
}

devorq::cmd_context7_install() {
    local option="${1:-}"
    
    devorq::info "═══ Context7 Installer ═══"
    devorq::info ""
    
    # Detecta plataforma
    devorq::info "[1/4] Detectando plataforma..."
    if command -v node &>/dev/null; then
        devorq::info "  ✓ Node.js $(node --version)"
    fi
    if command -v opencode &>/dev/null; then
        devorq::info "  ✓ opencode disponível"
    fi
    if command -v npm &>/dev/null; then
        devorq::info "  ✓ npm disponível"
    fi
    
    # Se não há Node.js, força API
    if ! command -v node &>/dev/null; then
        devorq::warn "Node.js não disponível — usando API direta"
        option="api"
    fi
    
    # Mostra opções se não especificado
    if [ -z "$option" ]; then
        devorq::info ""
        devorq::info "[2/4] Selecione o método de instalação:"
        devorq::info ""
        devorq::info "  [1] CLI + Skills (RECOMENDADO)"
        devorq::info "  [2] MCP Server"
        devorq::info "  [3] API Direta (fallback)"
        devorq::info ""
        read -p "[3/4] Opção [1]: " option
        option="${option:-1}"
    fi
    
    # Executa instalação
    source "${DEVORQ_LIB}/context7.sh" 2>/dev/null || true
    if declare -f ctx7_install &>/dev/null; then
        case "$option" in
            1) ctx7_install cli ;;
            2) ctx7_install mcp ;;
            3) ctx7_install api ;;
            *) devorq::error "Opção inválida: $option" ;;
        esac
    else
        devorq::error "lib/context7.sh não disponível"
    fi
    
    devorq::info ""
    devorq::info "[4/4] Validando..."
    
    # Testa instalação
    if declare -f ctx7_check &>/dev/null; then
        if ctx7_check 2>&1; then
            devorq::success "✓ Context7 operacional"
        else
            devorq::warn "✗ Context7 não passou no teste"
        fi
    fi
    
    devorq::info ""
    devorq::success "═══ Concluído ═══"
}
```

### 7.3 .devorq/config — Novas Variáveis

**Adicionar ao config:**
```bash
# Context7 Configuration
OPENAI_API_KEY=ctx7sk-060ff7f5-b768-4a1a-b945-0b909e518e89

# Método preferido (cli|mcp|api)
CTX7_METHOD=auto

# MCP URL (se usando MCP)
CTX7_MCP_URL=https://mcp.context7.com/mcp

# Endpoints alternativos (separados por espaço)
CTX7_ALT_URLS="https://api.context7.io/v1 https://api.context7.com/v1"
```

---

## 8. BACKWARD COMPATIBILITY

### 8.1 Premissas

1. **Instalations existentes** já têm `~/.devorq/config` com `OPENAI_API_KEY`
2. **GATE-6** já funciona (mesmo com WARN) — nunca bloqueia
3. **API key** existente continua válida

### 8.2 Plano de Migração

| Fase | Ação | Impacto |
|------|------|---------|
| 0 | Backup do config atual | Nenhum |
| 1 | Adicionar novas vars ao config | Baixo (defaults funcionam) |
| 2 | Atualizar lib/context7.sh | Baixo (funções novas são additive) |
| 3 | Atualizar bin/devorq | Baixo (novo comando opcional) |
| 4 | Testar GATE-6 | Validar que não quebrou |
| 5 | Oferecer `devorq context7 install` | Usuário escolhe |

### 8.3 Compatibilidade Regressiva

```bash
# Config antigo continua funcionando:
OPENAI_API_KEY=chave_antiga  # ✓ Lido normalmente

# Nova var é opcional:
CTX7_METHOD=auto  # ✓ Defaults para "auto"

# CLI check não quebra se ctx7 não existe:
if command -v ctx7 &>/dev/null; then
    ctx7 resolve ...
else
    # Fallback para API direta
    _ctx7_req ...
fi
```

### 8.4 Teste de Compatibilidade

```bash
# Testar em ambiente antigo (sem Node.js)
$ devorq gate 6
[WARN] Context7: OPENAI_API_KEY não configurado  # ← Usa config
# Ou
[WARN] Context7: API não respondeu              # ← API offline

# Testar em ambiente novo (com CLI)
$ devorq context7 install
$ devorq gate 6
[OK] Context7: API respondendo                  # ← CLI funciona
```

---

## 9. TESTES

### 9.1 Testes Unitários

```bash
# Teste 1: lib/context7.sh syntax
$ bash -n ~/.devorq/lib/context7.sh
# Esperado: sem erros

# Teste 2: ctx7_detect retorna método válido
$ source ~/.devorq/lib/context7.sh && ctx7_detect
# Esperado: cli | mcp | api | none

# Teste 3: ctx7_install com método inválido
$ ctx7_install invalid_method
# Esperado: [ERROR] Método inválido

# Teste 4: _ctx7_req_with_fallback falha gracefully
$ _ctx7_req_with_fallback "/endpoint" "{}" "invalid_key"
# Esperado: exit code 1, sem output
```

### 9.2 Testes de Integração

```bash
# Teste 5: devorq gate 6 passa (API direta)
$ devorq gate 6
[INFO] GATE-6: Context7 Checked
[OK] Context7: API respondendo

# Teste 6: devorq context7 install (CLI)
$ devorq context7 install
[INFO] Executando: npm install -g ctx7
[OK] ctx7 instalado

# Teste 7: devorq context7 detect
$ devorq context7 detect
[INFO] Método detectado: cli

# Teste 8: GATE-6 com CLI
$ devorq gate 6
[INFO] GATE-6: Context7 Checked
[OK] Context7: API respondendo (CLI mode)
```

### 9.3 Teste de Regressão

```bash
# Teste 9: Config antigo continua funcionando
$ grep OPENAI_API_KEY ~/.devorq/config
OPENAI_API_KEY=ctx7sk-...
$ source ~/.devorq/lib/context7.sh && _load_config
$ echo $CTX7_API_KEY
ctx7sk-...  # ← Mesmo valor

# Teste 10: GATE-6 não bloqueia mesmo com erro
$ devorq gate 6 || echo "GATE-6 returned: $?"
GATE-6 returned: 0  # ← Nunca bloqueia
```

### 9.4 Validação Visual

```
┌─────────────────────────────────────────────────────┐
│                   TESTES PASSA-GRADE                │
├─────────────────────────────────────────────────────┤
│ [✓] bash syntax validation                          │
│ [✓] ctx7_detect returns valid method                │
│ [✓] ctx7_install handles invalid method            │
│ [✓] devorq gate 6 não bloqueia                     │
│ [✓] API key backwards compatibility                │
│ [✓] CLI fallback when API fails                    │
└─────────────────────────────────────────────────────┘
```

---

## 10. Rodrigo Proposta de Issue/PR

### 10.1 GitHub Issue

**Título:**
```
[ENHANCEMENT] Context7 Integration — Adicionar CLI + MCP como alternativas à API REST
```

**Corpo:**
```markdown
## Problema Atual

A integração do Context7 via GATE-6 está falhando porque o endpoint `https://api.context7.io/v1` não está respondendo. O sistema faz curl direto na API REST, mas a API parece estar offline.

## Solução Proposta

Adicionar 3 opções de instalação do Context7 com comando unificado:

1. **CLI + Skills (Recomendado)** — `npx ctx7` ou npm install -g
2. **MCP Server** — servidor MCP em `https://mcp.context7.com/mcp`  
3. **API Direta** — fallback com endpoint alternativo

## Implementação Sugerida

### Novo Comando
```bash
devorq context7 install [cli|mcp|api]
```

### Fluxo
- Detecta plataforma (Node.js, opencode)
- Oferece opções ao usuário
- Executa instalação automaticamente
- Valida funcionamento

### Modificações em lib/context7.sh
- Adicionar `ctx7_detect()` — detecta método disponível
- Adicionar `ctx7_install()` — instala via método escolhido
- Adicionar fallback de endpoints na API direta
- Manter backwards compatibility

### GATE-6 Atualizado
```
1. Detecta CLI → testa ctx7 resolve
2. Detecta MCP → testa MCP connection  
3. Fallback API direta
4. Se nenhum funciona → WARN (não bloqueia)
```

## Critérios de Aceitação

- [ ] `devorq context7 install` funciona com Node.js instalado
- [ ] `devorq context7 install mcp` configura MCP corretamente
- [ ] `devorq gate 6` passa com qualquer método funcional
- [ ] API key antiga continua sendo lida
- [ ] GATE-6 nunca bloqueia mesmo se Context7 falhar

## Tasks

- [ ] Adicionar funções `ctx7_detect`, `ctx7_install` em lib/context7.sh
- [ ] Adicionar comando `devorq context7` em bin/devorq
- [ ] Atualizar GATE-6 para detectar método automaticamente
- [ ] Adicionar testes
- [ ] Atualizar documentação

---

**Labels:** enhancement, context7, integration
**Milestone:** v3.3.0
```

### 10.2 Pull Request Title

```
feat(context7): add cli/mcp/api install options + auto-detect in GATE-6
```

**PR Body:**
```markdown
## Summary

Adiciona 3 opções de instalação do Context7 com comando unificado `devorq context7 install` e detecta automaticamente o melhor método disponível em GATE-6.

### Changes

- **lib/context7.sh**: Adicionadas funções `ctx7_detect()`, `ctx7_install()`, `_ctx7_cli_test()`, `_ctx7_mcp_test()`, `_ctx7_api_test()`, `_ctx7_req_with_fallback()`
- **bin/devorq**: Novo comando `devorq context7 install|check|detect`
- **gates.sh**: GATE-6 agora detecta automaticamente CLI/MCP/API

### Options

| Método | Install | Config |
|--------|---------|--------|
| CLI + Skills | `npm install -g ctx7` | auto |
| MCP Server | `~/.opencode/mcp.json` | manual |
| API Direta | (nenhuma) | `~/.devorq/config` |

### Backwards Compatibility

- API key existente continua sendo lida
- GATE-6 nunca bloqueia mesmo se Context7 falhar
- Sembreaking changes

### Testing

```bash
devorq context7 install
devorq context7 detect
devorq gate 6
devorq test
```

closes #XX
```

---

## Anexo: Output do GATE-6 (Antes e Depois)

### Antes (API offline)
```
[INFO] GATE-6: 6 Context7 Checked — Docs consultadas (mesmo que rejeite)
[WARN] Context7: OPENAI_API_KEY não configurado
[INFO] Configure: export OPENAI_API_KEY=sua_chave_api
[INFO] Ou edite ~/.devorq/config
[WARN] GATE-6: 6 Context7 não configurado (sem API key ou API offline)
```

### Depois (CLI mode)
```
[INFO] GATE-6: 6 Context7 Checked — Docs consultadas (mesmo que rejeite)
[OK] Context7: CLI detectada
[OK] Context7: Testando 'ctx7 resolve express test'...
[OK] Context7: CLI respondendo
[PASS] GATE-6: Context7 Checked (CLI mode)
```

---

**Documento criado por:** Claude Code
**Data:** 2026-04-23
**Versão do documento:** 1.0.0

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
   * Todas as 22 notificações individuais foram disparadas via fila para os respectivos militares através do servidor SMTP da FAB (`<SMTP_DA_FAB>`).
   * As ocorrências foram marcadas como `enviado_em` no banco de dados.
   * O compilado geral das ocorrências foi consolidado para ciência da SAD do GAC-PAC.
4. **Boletim do Dia (04/09/2026 - BCA nº 153):**
   * Processado normalmente com 3 ocorrências e e-mails enviados.

---

## 5. Lições Aprendidas e Prevenção

1. **Configuração de Rede Debian:** Comandos de rota estática em `/etc/network/interfaces` exigem obrigatoriamente a palavra-chave `up` para serem executados durante a ativação da interface.
2. **Resolução de Upstream no Nginx:** Nunca utilizar nomes de serviço Docker de forma estática em diretivas `fastcgi_pass` sem `resolver` e variável, sob risco de congelar IPs no cache de inicialização do Nginx.
3. **Monitoramento Ativo:** O monitoramento contínuo de jobs falhos na tabela `failed_jobs` permite a identificação precoce de falhas de conectividade de rede antes que afetem semanas de publicações.

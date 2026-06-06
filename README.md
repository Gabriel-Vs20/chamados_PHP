# Sistema de Chamados - Deploy no InfinityFree

## Passo a passo (15-20 min)

### 1. Criar conta no InfinityFree
- Acesse https://infinityfree.com e cadastre-se (sem cartao)
- Em "Create Account", escolha um subdominio gratuito (ex: `chamados-cop.infinityfreeapp.com`)

### 2. Criar o banco MySQL
- No painel: **MySQL Databases**
- Clique em **Create Database**, escolha um nome (ex: `chamados`)
- Anote os 4 valores que aparecem:
  - **Hostname** (ex: `sql105.infinityfree.com`)
  - **Database name** (ex: `if0_12345678_chamados`)
  - **Username** (ex: `if0_12345678`)
  - **Password** (a que voce definiu)

### 3. Importar o schema
- No painel, clique em **phpMyAdmin** ao lado do banco
- Aba **Importar** -> selecione o arquivo `schema.sql` -> **Executar**

### 4. Configurar a conexao no codigo
- Abra `db.php` e preencha as 4 constantes no topo com os valores do passo 2

### 5. Subir os arquivos
- No painel: **File Manager** (ou via FTP no FileZilla)
- Entre na pasta `htdocs/`
- Faca upload de TODOS os arquivos `.php`, `.css` e `.sql` para `htdocs/`
- (O `schema.sql` ja foi importado, pode ate excluir do `htdocs/`)

### 6. Ativar SSL
- No painel: **Free SSL Certificates**
- Selecione o subdominio e clique em **Issue Certificate** (leva 5-15 min)
- Depois ative **Force HTTPS**

### 7. Acessar
- Abra `https://seusubdominio.infinityfreeapp.com`
- Pronto, ja funciona

## Premissas de sistemas distribuidos atendidas

1. **Cliente-servidor** - browser ↔ servidor PHP (HTTP/HTTPS)
2. **Heterogeneidade** - PHP, MySQL e BrasilAPI em hosts distintos
3. **Transparencia de localizacao** - acesso via DNS (sem IPs fixos)
4. **Comunicacao via rede** - PDO/TCP entre PHP-MySQL, REST entre PHP-BrasilAPI
5. **Integracao distribuida** - consumo de API publica externa (BrasilAPI feriados) para calculo de SLA
6. **Concorrencia** - varias sessoes/usuarios simultaneos atendidos pelo Apache do InfinityFree
7. **Tolerancia a falhas** - se a BrasilAPI cair, o cache local de feriados continua respondendo
8. **Escalabilidade** - infra do InfinityFree usa cluster compartilhado com balanceamento

## Arquivos do projeto
- `db.php` - conexao PDO MySQL + helpers
- `feriados.php` - integracao com BrasilAPI + cache de 24h
- `teams.php` - notificacao Microsoft Teams (Workflows webhook + Adaptive Card)
- `helpers.php` - icones SVG, tempo relativo, classe de SLA, iniciais
- `index.php` - painel/lista de chamados com filtro e stats
- `novo.php` - criar chamado (calcula SLA via API)
- `detalhe.php` - visualizar, atualizar status (dispara Teams), comentar, excluir
- `header.php` / `footer.php` - layout
- `style.css` - design system laranja gradiente, Sora, mobile-first
- `schema.sql` - DDL MySQL

## Configurar notificacao Teams (opcional)

Quando o status de um chamado muda, o sistema envia um Adaptive Card para um canal do Teams.

### Passo 1 - Criar o Workflow no Teams
1. No canal desejado, clique nos `...` ao lado do nome -> **Workflows**
2. Procure o template **"Post to a channel when a webhook request is received"**
3. Da um nome (ex: "Chamados COP"), clique **Next** -> escolha equipe/canal -> **Add workflow**
4. Copie a **URL gerada** (algo como `https://prod-XX.westus.logic.azure.com:443/workflows/...`)

### Passo 2 - Configurar no codigo
Em `db.php`, preencha:
```php
const APP_URL = 'https://seudominio.infinityfreeapp.com';
const TEAMS_WEBHOOK_URL = 'https://prod-XX.westus.logic.azure.com:443/...';
```

### Passo 3 - Testar
Altere o status de qualquer chamado pelo painel. Se a URL estiver correta, em poucos segundos o canal do Teams recebe um card com:
- ID e titulo do chamado
- Transicao "De X -> Para Y" com destaque
- Solicitante, responsavel, prioridade, horario
- Botao "Abrir chamado" linkando para o sistema

### Observacoes
- Office 365 Connectors classicos foram descontinuados em mai/2026. Este projeto ja usa o **formato novo** (Adaptive Cards via Workflows).
- Se o webhook falhar, o sistema continua funcionando normalmente (erro registrado em log, nao bloqueia a UI).
- Notificacao so dispara quando o status REALMENTE muda (clicar salvar sem alterar nao gera ruido).

# Guia de Instalação - AVP Controle de Estudos

Este documento fornece instruções passo a passo para instalar e configurar o aplicativo AVP Controle de Estudos em seu servidor.

## Pré-requisitos

- **PHP 7.4 ou superior** (com suporte a mysqli)
- **MySQL 5.7 ou superior**
- **Servidor Web** (Apache com mod_rewrite ou Nginx)
- **Acesso SSH** ou **cPanel/Hosting Control Panel**

## Passo 1: Preparar o Servidor

### 1.1 Verificar versão do PHP

```bash
php -v
```

### 1.2 Verificar se mysqli está habilitado

```bash
php -m | grep mysqli
```

Se não aparecer, você precisa habilitar a extensão mysqli no php.ini:

```ini
extension=mysqli
```

### 1.3 Verificar conexão com MySQL

```bash
mysql -u root -p
```

## Passo 2: Fazer Upload dos Arquivos

1. Faça download de todos os arquivos do projeto
2. Extraia o arquivo ZIP
3. Faça upload para seu servidor (pasta public_html ou www)

Estrutura esperada:
```
seu-dominio.com/
├── config/
│   ├── config.php
│   ├── database.php
│   └── schema.sql
├── public/
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── .htaccess
│   ├── api/
│   │   ├── subjects.php
│   │   ├── lessons.php
│   │   ├── reviews.php
│   │   └── performance.php
│   └── assets/
│       └── js/
│           └── app.js
└── README.md
```

## Passo 3: Criar Banco de Dados

### 3.1 Via cPanel/Hosting Control Panel

1. Acesse o painel de controle do seu hosting
2. Procure por "MySQL Databases" ou "Banco de Dados"
3. Crie um novo banco de dados chamado `avp_controle_estudos`
4. Crie um usuário MySQL com senha forte
5. Atribua todos os privilégios do banco ao usuário

### 3.2 Via SSH/Terminal

```bash
mysql -u root -p

CREATE DATABASE avp_controle_estudos;
CREATE USER 'avp_user'@'localhost' IDENTIFIED BY 'sua_senha_forte';
GRANT ALL PRIVILEGES ON avp_controle_estudos.* TO 'avp_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3.3 Importar Schema

```bash
mysql -u avp_user -p avp_controle_estudos < config/schema.sql
```

Ou via phpMyAdmin:
1. Acesse phpMyAdmin
2. Selecione o banco `avp_controle_estudos`
3. Clique em "Importar"
4. Selecione o arquivo `config/schema.sql`
5. Clique em "Executar"

## Passo 4: Configurar Credenciais

### 4.1 Editar config/database.php

Abra o arquivo `config/database.php` e atualize:

```php
define('DB_HOST', 'localhost');      // Geralmente localhost
define('DB_USER', 'avp_user');       // Usuário criado
define('DB_PASS', 'sua_senha_forte'); // Senha do usuário
define('DB_NAME', 'avp_controle_estudos'); // Nome do banco
```

### 4.2 Editar config/config.php

Atualize a URL base:

```php
define('BASE_URL', 'https://seu-dominio.com');
```

## Passo 5: Configurar Permissões

### Via SSH

```bash
# Dar permissão de leitura/escrita ao diretório
chmod -R 755 /caminho/para/seu-dominio.com
chmod 644 /caminho/para/seu-dominio.com/config/database.php
chmod 644 /caminho/para/seu-dominio.com/config/config.php
```

### Via FTP

1. Clique direito na pasta do projeto
2. Propriedades → Permissões
3. Defina como 755 para pastas e 644 para arquivos

## Passo 6: Configurar Servidor Web

### Para Apache

Certifique-se de que `mod_rewrite` está habilitado:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

O arquivo `.htaccess` já está configurado.

### Para Nginx

Adicione ao bloco `server`:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastname;
    include fastcgi_params;
}
```

## Passo 7: Testar Instalação

1. Abra seu navegador
2. Acesse `https://seu-dominio.com/login.php`
3. Você deve ver a página de login

### Se receber erro de conexão com banco de dados:

- Verifique as credenciais em `config/database.php`
- Certifique-se de que o MySQL está rodando
- Verifique se o usuário tem permissões no banco

### Se receber erro 404:

- Verifique se `mod_rewrite` está habilitado (Apache)
- Verifique se `.htaccess` está no diretório `public/`
- Verifique permissões do arquivo `.htaccess`

## Passo 8: Primeiro Acesso

1. Clique em "Cadastre-se aqui"
2. Preencha os dados (nome, email, senha)
3. Clique em "Cadastrar"
4. Faça login com suas credenciais
5. Comece a usar o aplicativo!

## Troubleshooting

### Erro: "Erro ao conectar ao banco de dados"

**Solução:**
- Verifique se MySQL está rodando
- Confirme credenciais em `config/database.php`
- Teste conexão via SSH: `mysql -u avp_user -p avp_controle_estudos`

### Erro: "Acesso negado"

**Solução:**
- Verifique permissões de arquivo (755 para pastas, 644 para arquivos)
- Verifique se o servidor web tem permissão de leitura/escrita

### Erro: "Página não encontrada (404)"

**Solução:**
- Verifique se `mod_rewrite` está habilitado
- Verifique se `.htaccess` existe em `public/`
- Teste acessando diretamente: `seu-dominio.com/public/login.php`

### Erro: "Sessão não persiste"

**Solução:**
- Verifique se cookies estão habilitados no navegador
- Verifique permissões do diretório de sessões do PHP
- Teste em outro navegador

### Erro: "mysqli não está habilitado"

**Solução:**
- Edite `php.ini`
- Procure por `;extension=mysqli`
- Remova o ponto-e-vírgula: `extension=mysqli`
- Reinicie o servidor web

## Segurança

### Recomendações Importantes:

1. **Use HTTPS** - Sempre use certificado SSL/TLS
2. **Senhas Fortes** - Use senhas complexas para MySQL
3. **Backup Regular** - Faça backup do banco de dados regularmente
4. **Atualizações** - Mantenha PHP e MySQL atualizados
5. **Proteção de Arquivos** - Não deixe arquivos de configuração acessíveis

### Backup do Banco de Dados

```bash
# Fazer backup
mysqldump -u avp_user -p avp_controle_estudos > backup.sql

# Restaurar backup
mysql -u avp_user -p avp_controle_estudos < backup.sql
```

## Suporte

Se encontrar problemas:

1. Verifique os logs do servidor web
2. Verifique os logs do MySQL
3. Teste as credenciais manualmente
4. Verifique permissões de arquivo/pasta

## Próximos Passos

Após a instalação bem-sucedida:

1. Crie sua conta de usuário
2. Adicione suas disciplinas
3. Registre suas aulas concluídas
4. Acompanhe suas revisões
5. Monitore seu desempenho

Boa sorte em seus estudos! 📚

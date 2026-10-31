# Skedd - Agenda Estudantil Online (PHP & MySQL)

[![GitHub](https://img.shields.io/badge/GitHub-gustavo7771%2FSkedd--Agenda--estudantil--online-blue?logo=github)](https://github.com/gustavo7771/Skedd-Agenda-estudantil-online)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4%20%7C%208.0%2B-777BB4?logo=php)](https://www.php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B%20%7C%20MariaDB-4479A1?logo=mysql)](https://www.mysql.com)

O **Skedd** é uma solução completa de agenda escolar digital moderna e responsiva voltada para alunos, representantes de turma, professores e gestores escolares.

---

## 🌟 Recursos Principais

- **Backend em PHP Integrado em Todas as Páginas**:
  - `index.php`: Autenticação e cadastro com proteção de sessão e CSRF.
  - `agenda.php`: Interface interativa FullCalendar para agendamento e visualização de avaliações e provas.
  - `admin.php`: Painel administrativo completo para controle de turmas, eventos, usuários, disciplinas, questões administrativas e auditoria.
  - `logout.php`: Encerramento seguro de sessão com invalidação de tokens persistentes.
  - `api.php`: API RESTful para sincronização instantânea com o banco de dados MySQL.

- **Banco de Dados Relacional MySQL**:
  - `provas`: Registro de títulos, datas, cores, descrições, turmas e professores autores.
  - `usuarios`: Controle de acesso para alunos, representantes, professores e administradores.
  - `turmas`: Códigos e apelidos de turmas (ex: `1001` - 1° tec.info).
  - `disciplinas`: Cadastro de disciplinas com carga horária e vinculação com turmas e professores.
  - `questoes_administrativas`: Chamados internos, comunicados, avisos e resoluções da equipe de gestão.
  - `logs`: Auditoria de ações críticas com data, ação, detalhes e endereço IP.

- **Criptografia e Segurança de Alta Qualidade**:
  - **Hash de Senhas**: `Argon2id` (com detecção automática e fallback seguro para `Bcrypt` custo 12) através da classe `Crypto`.
  - **Criptografia Simétrica**: `AES-256-GCM` (OpenSSL) para dados confidenciais com autenticação de integridade (IV único + auth tag).
  - **Prepared Statements (PDO)**: 100% das consultas utilizam parâmetros vinculados preparados, eliminando o risco de Injeção SQL.
  - **Proteção XSS e CSRF**: Sanitização de entradas e saídas, com tokens de sessão criptograficamente seguros gerados via CSPRNG (`random_bytes`).
  - **Sessões Blindadas**: Cookies com propriedades `HttpOnly` e `SameSite=Lax`.

- **Opção Direta para o Repositório GitHub & Licenças**:
  - Integrada diretamente no menu de configurações (⚙) e no rodapé das páginas.
  - Repositório Oficial: [https://github.com/gustavo7771/Skedd-Agenda-estudantil-online](https://github.com/gustavo7771/Skedd-Agenda-estudantil-online)
  - Licença: **MIT License** (disponível no arquivo [LICENSE](LICENSE) e via modal no sistema).

---

## 🚀 Como Executar o Projeto

### Pré-requisitos
- Servidor Web com PHP (PHP 7.4 ou superior, Apache, Nginx, XAMPP, Laragon ou Docker).
- Servidor MySQL ou MariaDB.

### Configuração do Banco de Dados
1. Crie o banco de dados ou importe o arquivo [`database.sql`](database.sql) no seu MySQL/phpMyAdmin:
   ```sql
   CREATE DATABASE skedd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Caso prefira, a classe [`db.php`](db.php) conta com **auto-provisionamento automático**: ao iniciar a aplicação com as credenciais do MySQL configuradas no `config.php`, o sistema cria o banco e as tabelas com os dados iniciais automaticamente se ainda não existirem!

3. Ajuste as credenciais no arquivo [`config.php`](config.php) (ou via variáveis de ambiente):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'skedd');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

---

## 🔑 Credenciais Padrão para Testes

- **Administrador (Painel Admin)**:
  - Código de Acesso Master: `admin123`
- **Professor (Demonstração)**:
  - E-mail: `gustavofaverobonetti@gmail.com`
  - Senha: `admin123` ou `123456`
- **Aluno**:
  - E-mail: `015@gmail.com`
  - Senha: `123456`
- **Chave de Validação Institucional** (para cadastro de professores e representantes):
  - Chave: `SKEDE123`

---

## 📜 Licença

Distribuído sob a licença MIT. Consulte o arquivo [LICENSE](LICENSE) para obter mais informações.


-- ==============================================================
-- SKEDD - AGENDA ESTUDANTIL ONLINE
-- Schema MySQL Completo com Suporte a Criptografia e Auditoria
-- Repositório: https://github.com/gustavo7771/Skedd-Agenda-estudantil-online
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `skedd` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `skedd`;

-- -------------------------------------------------------------
-- 1. TABELA DE TURMAS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `turmas` (
    `codigo` VARCHAR(50) NOT NULL,
    `apelido` VARCHAR(100) NOT NULL,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. TABELA DE USUÁRIOS (Criptografia Segura Argon2id/Bcrypt)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` VARCHAR(50) NOT NULL,
    `matricula` VARCHAR(50) NOT NULL,
    `nome` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `senha` VARCHAR(255) NOT NULL,
    `nivel` ENUM('aluno', 'representante', 'professor', 'admin') NOT NULL DEFAULT 'aluno',
    `turma` VARCHAR(50) DEFAULT 'Docente',
    `turmas_prof` TEXT NULL,
    `cargo` VARCHAR(100) NULL,
    `lembrar_token` VARCHAR(255) NULL,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    UNIQUE KEY `uk_matricula` (`matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. TABELA DE DISCIPLINAS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `disciplinas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(50) NULL,
    `nome` VARCHAR(150) NOT NULL,
    `descricao` TEXT NULL,
    `carga_horaria` INT DEFAULT 0,
    `turma_codigo` VARCHAR(50) NULL,
    `professor_id` VARCHAR(50) NULL,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_disciplina_turma` (`turma_codigo`),
    INDEX `idx_disciplina_professor` (`professor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. TABELA DE PROVAS E AVALIAÇÕES
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `provas` (
    `id` VARCHAR(50) NOT NULL,
    `disciplina_id` INT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `start` DATE NOT NULL,
    `color` VARCHAR(30) NOT NULL DEFAULT '#168fff',
    `turma` VARCHAR(50) NOT NULL,
    `criador_id` VARCHAR(50) NULL,
    `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `nome_admin` VARCHAR(150) NULL,
    `all_day` TINYINT(1) NOT NULL DEFAULT 1,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_prova_turma` (`turma`),
    INDEX `idx_prova_data` (`start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. TABELA DE QUESTÕES E COMUNICADOS ADMINISTRATIVOS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `questoes_administrativas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(200) NOT NULL,
    `descricao` TEXT NOT NULL,
    `tipo` VARCHAR(50) NOT NULL DEFAULT 'comunicado',
    `prioridade` ENUM('baixa', 'media', 'alta', 'urgente') NOT NULL DEFAULT 'media',
    `status` ENUM('aberta', 'em_analise', 'resolvida', 'arquivada') NOT NULL DEFAULT 'aberta',
    `autor_nome` VARCHAR(150) NOT NULL,
    `autor_id` VARCHAR(50) NULL,
    `turma_alvo` VARCHAR(50) NOT NULL DEFAULT 'todas',
    `resposta` TEXT NULL,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_questoes_status` (`status`),
    INDEX `idx_questoes_turma` (`turma_alvo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 6. TABELA DE LOGS DO SISTEMA E AUDITORIA
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `data` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `acao` VARCHAR(120) NOT NULL,
    `detalhes` TEXT NOT NULL,
    `usuario_id` VARCHAR(50) NULL,
    `ip` VARCHAR(50) NULL,
    INDEX `idx_logs_data` (`data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- INSERÇÃO DE DADOS INICIAIS (SEED)
-- -------------------------------------------------------------

-- Turmas
INSERT INTO `turmas` (`codigo`, `apelido`) VALUES
('1001', '1° tec.info'),
('2001', '2° tec.info'),
('3001', '3° tec.info'),
('21321', '1° tec.juridico'),
('272342', '2° tec.juridico'),
('1231313', '3° tec.juridico')
ON DUPLICATE KEY UPDATE `apelido` = VALUES(`apelido`);

-- Usuários pré-cadastrados (Hashes de senha Bcrypt preservados com total compatibilidade)
INSERT INTO `usuarios` (`id`, `matricula`, `nome`, `email`, `senha`, `nivel`, `turma`, `turmas_prof`) VALUES
('6a15db2fb0d32', '20251PAL0030016tt', 'Gustavo Favero Bonetti', 'gfaverobonetti@gmail.com', '$2y$10$WnVFOqxIWc3CJArzUvr5ieyKK63XAS6Po4KAclnWdr0fIr7nt0h3S', 'aluno', '1001', NULL),
('6a1823cfd0534', '20251PAL00030', 'Gustavo Favero Bonetti', 'gustavofaverobonetti@gmail.com', '$2y$10$teIbFCBptf2u3pF4z4nhRuckIfjpTZqjTdCeX28ZjYN6qX0UQxiDe', 'professor', 'Docente', '["1001","2001","3001","21321","272342","1231313"]'),
('6a1ed62011617', '4', 'David Lucas', 'dlamaral2010@gmail.com', '$2y$10$XQYUWTKQ5.Mp.N0k3rPN.u/Fgcu7XorBLsEinwgdZVucWMoG0CuAu', 'professor', 'Docente', '["1001","2001","3001","21321","272342","1231313"]'),
('6a1edcefdb3e0', 'a2123', 'Aluno 015', '015@gmail.com', '$2y$10$AhCH17cmaRbplSDnwzDQdeS4K33Tu1n/X/hPyhlAc4JKRuMBpQpNe', 'aluno', '1001', NULL),
('6a29611e9cb86', '12341', 'Aluno 02', '02@gmail.com', '$2y$10$zvdMyV3cEzXXexmhqQ/yv.v4kO4uhb/zDYocmgp0ya5Q2ZD7oYl/i', 'aluno', '1001', NULL),
('6a3805a44f77b', '2222', 'Gustavo SCCP', 'sccpgustavosccp89@gmail.com', '$2y$10$UKjCgMzWRhCuNOH7ONYxfOmlGRSEQ7G4r3TZOblPfFiQmFgwDx2/S', 'professor', 'Docente/Admin', '["1001","2001","3001","21321","272342","1231313"]'),
('6a39741627e75', '91u27131', 'Estudante 123', '123321@gmail.com', '$2y$10$DLOqPZ2xZgBhmMykiAr0juhYryJt/.BKsy5UInt/xnB5h92VI4.qq', 'aluno', '1001', NULL)
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `turma` = VALUES(`turma`), `turmas_prof` = VALUES(`turmas_prof`);

-- Disciplinas
INSERT INTO `disciplinas` (`codigo`, `nome`, `descricao`, `carga_horaria`, `turma_codigo`, `professor_id`) VALUES
('WEB1', 'Desenvolvimento Web', 'HTML5, CSS3, JavaScript moderno e conexões com APIs PHP/MySQL.', 80, '1001', '6a1823cfd0534'),
('BD1', 'Banco de Dados Relacional', 'Modelagem, normalização e consultas SQL estruturadas.', 60, '1001', '6a1ed62011617'),
('REDES1', 'Arquitetura e Redes', 'Protocolos TCP/IP, roteamento e segurança de computadores.', 60, '2001', '6a1823cfd0534'),
('DIR1', 'Direito Constitucional', 'Fundamentos dos direitos fundamentais e cidadania.', 80, '21321', '6a3805a44f77b')
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`);

-- Provas de Exemplo
INSERT INTO `provas` (`id`, `title`, `description`, `start`, `color`, `turma`, `criador_id`, `is_admin`, `nome_admin`, `all_day`) VALUES
('p_exemplo_1', 'Avaliação de Desenvolvimento Web (1° tec.info)', 'Conteúdo: HTML, CSS, JavaScript e manipulação do DOM. Trazer notebook carregado.', CURDATE(), '#168fff', '1001', '6a1823cfd0534', 0, '', 1),
('p_exemplo_2', 'Prova de Banco de Dados (1° tec.info)', 'Capítulos 3 e 4. Modelagem relacional e normalização.', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '#ff9500', '1001', '6a1ed62011617', 0, '', 1),
('p_exemplo_3', 'Simulado Institucional (Todas as Turmas)', 'Simulado interdisciplinar para todos os cursos técnicos.', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '#e02424', 'todas', 'admin_panel', 1, 'Coordenação Geral', 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Questões Administrativas
INSERT INTO `questoes_administrativas` (`titulo`, `descricao`, `tipo`, `prioridade`, `status`, `autor_nome`, `autor_id`, `turma_alvo`, `resposta`) VALUES
('Calendário de Avaliações do Bimestre', 'Definição do prazo final para lançamento de provas no calendário do Skedd.', 'aviso', 'alta', 'resolvida', 'Coordenação Pedagógica', 'admin_panel', 'todas', 'Todas as datas foram inseridas no sistema.'),
('Manutenção da Rede de Laboratório', 'Verificação dos cabos e acesso à rede Wi-Fi das turmas de informática.', 'manutencao', 'media', 'aberta', 'Coordenação de TI', 'admin_panel', '1001', 'Agendado para o fim de semana.')
ON DUPLICATE KEY UPDATE `titulo` = VALUES(`titulo`);

-- Logs Iniciais
INSERT INTO `logs` (`data`, `acao`, `detalhes`, `usuario_id`, `ip`) VALUES
(NOW(), 'Inicialização do Sistema', 'Banco de dados MySQL estruturado com sucesso no Skedd.', 'sistema', '127.0.0.1');


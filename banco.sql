-- ============================================================
--  ElitePLAY - Banco de Dados de Autenticação
--  Criado em: 2026
-- ============================================================

CREATE DATABASE IF NOT EXISTS eliteplay
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE eliteplay;

-- ------------------------------------------------------------
-- Tabela de usuários autorizados
-- Emails em lowercase para evitar duplicatas case-insensitive
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email       VARCHAR(255) NOT NULL,
    ativo       TINYINT(1)   NOT NULL DEFAULT 1,         -- 0 = bloqueado pelo admin
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Tabela de sessões ativas
-- Nunca armazenamos o token em texto puro — apenas o hash SHA-256
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessoes (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id   INT UNSIGNED    NOT NULL,
    token_hash   CHAR(64)        NOT NULL,               -- SHA-256 hex do token real
    ip           VARCHAR(45)     NOT NULL,               -- Suporta IPv6
    user_agent   VARCHAR(512)    NOT NULL DEFAULT '',
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at   DATETIME        NOT NULL,               -- Sessão com TTL
    revogada     TINYINT(1)      NOT NULL DEFAULT 0,     -- Logout/invalidação forçada

    PRIMARY KEY (id),
    UNIQUE KEY uq_token_hash (token_hash),
    KEY idx_usuario_id (usuario_id),
    KEY idx_expires (expires_at),

    CONSTRAINT fk_sessoes_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Tabela de rate-limiting de tentativas de login
-- email_hash = SHA-256 do email (nunca email puro)
-- Permite bloquear por e-mail E por IP independentemente
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tentativas_login (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    identificador  VARCHAR(64)  NOT NULL,                -- SHA-256 do IP ou do email
    tipo           ENUM('ip','email') NOT NULL,
    tentativas     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    bloqueado_ate  DATETIME     NULL DEFAULT NULL,       -- NULL = não bloqueado
    primeira_em    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultima_em      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_ident_tipo (identificador, tipo),
    KEY idx_bloqueado (bloqueado_ate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Tabela de log de acessos (auditoria)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS log_acessos (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id  INT UNSIGNED    NULL DEFAULT NULL,       -- NULL = tentativa inválida
    ip          VARCHAR(45)     NOT NULL,
    user_agent  VARCHAR(512)    NOT NULL DEFAULT '',
    sucesso     TINYINT(1)      NOT NULL DEFAULT 0,
    motivo      VARCHAR(100)    NOT NULL DEFAULT '',     -- Ex: 'ok', 'email_invalido', 'bloqueado'
    criado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_usuario_id (usuario_id),
    KEY idx_ip (ip),
    KEY idx_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Procedure de limpeza (chamar via cron ou manualmente)
-- Remove sessões expiradas e tentativas antigas
-- ------------------------------------------------------------
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS limpar_registros_antigos()
BEGIN
    -- Remove sessões expiradas há mais de 7 dias
    DELETE FROM sessoes
    WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY);

    -- Reseta tentativas mais antigas que 1 hora (janela de bloqueio)
    DELETE FROM tentativas_login
    WHERE ultima_em < DATE_SUB(NOW(), INTERVAL 1 HOUR)
      AND (bloqueado_ate IS NULL OR bloqueado_ate < NOW());

    -- Remove logs com mais de 90 dias
    DELETE FROM log_acessos
    WHERE criado_em < DATE_SUB(NOW(), INTERVAL 90 DAY);
END$$
DELIMITER ;


-- ------------------------------------------------------------
-- Usuário administrador inicial
-- ------------------------------------------------------------
INSERT INTO usuarios (email, ativo) VALUES ('murilo.wurf@gmail.com', 1);


-- ------------------------------------------------------------
-- Adicionar coluna is_admin na tabela usuarios
-- ------------------------------------------------------------
ALTER TABLE usuarios
    ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER ativo;

-- Define o primeiro usuário como admin
UPDATE usuarios SET is_admin = 1 WHERE email = 'murilo.wurf@gmail.com';

-- Adicionar coluna dias_acesso na tabela usuarios
-- NULL = acesso sem limite, número = dias de acesso restantes
ALTER TABLE usuarios 
    ADD COLUMN dias_acesso INT UNSIGNED NULL DEFAULT NULL AFTER is_admin;

-- Adicionar coluna data_expiracao na tabela sessoes para controle fino
ALTER TABLE sessoes
    ADD COLUMN user_expires_at DATETIME NULL DEFAULT NULL AFTER expires_at;


-- ------------------------------------------------------------
-- Override manual de canais por jogo (painel admin)
-- Permite que o admin corrija/adicione canais para cada jogo
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jogos_canais_override (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    jogo_id     VARCHAR(120) NOT NULL,          -- ID único na API (ex: "12345")
    jogo_titulo VARCHAR(300) NOT NULL DEFAULT '', -- Título do jogo (referência)
    jogo_data   DATE         NOT NULL,           -- Data do jogo (para limpeza automática)
    canais      JSON         NOT NULL,           -- [{"name":"ESPN 1 HD"},{"name":"PREMIERE 3"}]
    editado_por INT UNSIGNED NOT NULL,           -- usuario_id do admin que editou
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_jogo_data (jogo_id, jogo_data),
    KEY idx_data (jogo_data),

    CONSTRAINT fk_override_editor
        FOREIGN KEY (editado_por) REFERENCES usuarios(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

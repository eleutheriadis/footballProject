-- =============================================================
-- Football Championship Statistics Management System
-- Σύστημα Διαχείρισης Στατιστικών Πρωταθλημάτων Ποδοσφαίρου
-- Database: football_stats   |   Engine: InnoDB   |   Charset: utf8mb4
-- =============================================================

USE football_stats;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS match_events;
DROP TABLE IF EXISTS substitutions;
DROP TABLE IF EXISTS lineups;
DROP TABLE IF EXISTS matches;
DROP TABLE IF EXISTS matchdays;
DROP TABLE IF EXISTS championship_teams;
DROP TABLE IF EXISTS championships;
DROP TABLE IF EXISTS players;
DROP TABLE IF EXISTS teams;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- USERS
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    email           VARCHAR(100) UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('admin','stat_keeper','fan') NOT NULL DEFAULT 'fan',
    full_name       VARCHAR(100),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role)
) ENGINE=InnoDB;

-- TEAMS
CREATE TABLE teams (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    city        VARCHAR(100) NOT NULL,
    logo_path   VARCHAR(255),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- PLAYERS
CREATE TABLE players (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id     INT UNSIGNED NOT NULL,
    name        VARCHAR(100) NOT NULL,
    position    ENUM('GK','DEF','MID','FWD') NOT NULL,
    jersey_no   TINYINT UNSIGNED,
    photo_path  VARCHAR(255),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_players_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    UNIQUE KEY uk_team_jersey (team_id, jersey_no),
    INDEX idx_players_team (team_id),
    INDEX idx_players_position (position)
) ENGINE=InnoDB;

-- CHAMPIONSHIPS
CREATE TABLE championships (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(150) NOT NULL,
    season        VARCHAR(20)  NOT NULL,
    status        ENUM('draft','active','finished') NOT NULL DEFAULT 'draft',
    double_round  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_name_season (name, season),
    INDEX idx_champ_status (status)
) ENGINE=InnoDB;

-- CHAMPIONSHIP ↔ TEAMS
CREATE TABLE championship_teams (
    championship_id INT UNSIGNED NOT NULL,
    team_id         INT UNSIGNED NOT NULL,
    PRIMARY KEY (championship_id, team_id),
    CONSTRAINT fk_ct_champ FOREIGN KEY (championship_id) REFERENCES championships(id) ON DELETE CASCADE,
    CONSTRAINT fk_ct_team  FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    INDEX idx_ct_team (team_id)
) ENGINE=InnoDB;

-- MATCHDAYS
CREATE TABLE matchdays (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id  INT UNSIGNED NOT NULL,
    number           SMALLINT UNSIGNED NOT NULL,
    CONSTRAINT fk_md_champ FOREIGN KEY (championship_id) REFERENCES championships(id) ON DELETE CASCADE,
    UNIQUE KEY uk_champ_md (championship_id, number)
) ENGINE=InnoDB;

-- MATCHES
CREATE TABLE matches (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    matchday_id     INT UNSIGNED NOT NULL,
    home_team_id    INT UNSIGNED NOT NULL,
    away_team_id    INT UNSIGNED NOT NULL,
    scheduled_at    DATETIME,
    status          ENUM('scheduled','live','finished') NOT NULL DEFAULT 'scheduled',
    home_score      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    away_score      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    stat_keeper_id  INT UNSIGNED,
    started_at      DATETIME,
    finished_at     DATETIME,
    CONSTRAINT fk_matches_md   FOREIGN KEY (matchday_id) REFERENCES matchdays(id) ON DELETE CASCADE,
    CONSTRAINT fk_matches_home FOREIGN KEY (home_team_id) REFERENCES teams(id),
    CONSTRAINT fk_matches_away FOREIGN KEY (away_team_id) REFERENCES teams(id),
    CONSTRAINT fk_matches_sk   FOREIGN KEY (stat_keeper_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_teams_diff CHECK (home_team_id <> away_team_id),
    INDEX idx_matches_md     (matchday_id),
    INDEX idx_matches_status (status),
    INDEX idx_matches_sched  (scheduled_at)
) ENGINE=InnoDB;

-- LINEUPS
CREATE TABLE lineups (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id    INT UNSIGNED NOT NULL,
    team_id     INT UNSIGNED NOT NULL,
    player_id   INT UNSIGNED NOT NULL,
    is_starter  TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_lineups_match  FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_lineups_team   FOREIGN KEY (team_id) REFERENCES teams(id),
    CONSTRAINT fk_lineups_player FOREIGN KEY (player_id) REFERENCES players(id),
    UNIQUE KEY uk_match_player (match_id, player_id),
    INDEX idx_lineups_match_team (match_id, team_id)
) ENGINE=InnoDB;

-- SUBSTITUTIONS
CREATE TABLE substitutions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id        INT UNSIGNED NOT NULL,
    team_id         INT UNSIGNED NOT NULL,
    minute          TINYINT UNSIGNED NOT NULL,
    player_in_id    INT UNSIGNED NOT NULL,
    player_out_id   INT UNSIGNED NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_subs_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_subs_team  FOREIGN KEY (team_id) REFERENCES teams(id),
    CONSTRAINT fk_subs_in    FOREIGN KEY (player_in_id) REFERENCES players(id),
    CONSTRAINT fk_subs_out   FOREIGN KEY (player_out_id) REFERENCES players(id),
    CONSTRAINT chk_subs_diff CHECK (player_in_id <> player_out_id),
    INDEX idx_subs_match (match_id)
) ENGINE=InnoDB;

-- MATCH_EVENTS  (generic table for all R2 events)
CREATE TABLE match_events (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id        INT UNSIGNED NOT NULL,
    team_id         INT UNSIGNED NOT NULL,
    player_id       INT UNSIGNED,
    minute          TINYINT UNSIGNED NOT NULL,
    event_type      ENUM('shot','tackle','pass','cross','assist',
                         'mistake','foul','corner','card') NOT NULL,
    event_subtype   VARCHAR(30),
    outcome         VARCHAR(30),
    created_at      TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3),
    CONSTRAINT fk_ev_match  FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_ev_team   FOREIGN KEY (team_id) REFERENCES teams(id),
    CONSTRAINT fk_ev_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE SET NULL,
    INDEX idx_ev_match_time  (match_id, created_at),
    INDEX idx_ev_match_type  (match_id, event_type),
    INDEX idx_ev_player_type (player_id, event_type)
) ENGINE=InnoDB;

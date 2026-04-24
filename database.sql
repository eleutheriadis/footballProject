-- =============================================
-- Σύστημα Διαχείρισης Στατιστικών Ποδοσφαίρου
-- Database Schema
-- =============================================

CREATE DATABASE IF NOT EXISTS football_championship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE football_championship;

-- Πίνακας Ομάδων
CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Πίνακας Παικτών
CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    position ENUM('Τερματοφύλακας','Αμυντικός','Μέσος','Επιθετικός') NOT NULL,
    team_id INT NOT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

-- Πίνακας Πρωταθλημάτων
CREATE TABLE IF NOT EXISTS championships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    season VARCHAR(20) NOT NULL,
    status ENUM('draft','drawn','in_progress','completed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Σχέση Πρωταθλήματος - Ομάδων
CREATE TABLE IF NOT EXISTS championship_teams (
    championship_id INT NOT NULL,
    team_id INT NOT NULL,
    PRIMARY KEY (championship_id, team_id),
    FOREIGN KEY (championship_id) REFERENCES championships(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

-- Πίνακας Αγωνιστικών
CREATE TABLE IF NOT EXISTS matchdays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    championship_id INT NOT NULL,
    round_number INT NOT NULL,
    FOREIGN KEY (championship_id) REFERENCES championships(id) ON DELETE CASCADE
);

-- Πίνακας Αγώνων
CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matchday_id INT NOT NULL,
    home_team_id INT NOT NULL,
    away_team_id INT NOT NULL,
    home_score INT DEFAULT NULL,
    away_score INT DEFAULT NULL,
    status ENUM('scheduled','in_progress','completed') DEFAULT 'scheduled',
    FOREIGN KEY (matchday_id) REFERENCES matchdays(id) ON DELETE CASCADE,
    FOREIGN KEY (home_team_id) REFERENCES teams(id),
    FOREIGN KEY (away_team_id) REFERENCES teams(id)
);

-- Φάκελος για uploads
-- Δημιουργείται χειροκίνητα: /uploads/logos/ και /uploads/photos/

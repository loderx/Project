CREATE TABLE IF NOT EXISTS studios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(140) NOT NULL,
    logo_path VARCHAR(255) NULL,
    subscription_end DATETIME NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studio_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    theme ENUM('light', 'dark') NOT NULL DEFAULT 'light',
    active_until DATETIME NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (studio_id) REFERENCES studios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studio_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190),
    phone VARCHAR(60),
    notes TEXT,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (studio_id) REFERENCES studios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS service_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studio_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    FOREIGN KEY (studio_id) REFERENCES studios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studio_id INT NOT NULL,
    user_id INT NOT NULL,
    client_id INT NULL,
    title VARCHAR(150) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    location VARCHAR(190),
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    price DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (studio_id) REFERENCES studios(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS finances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studio_id INT NOT NULL,
    user_id INT NOT NULL,
    kind ENUM('income', 'expense') NOT NULL,
    category VARCHAR(120) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    entry_date DATE NOT NULL,
    description TEXT,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (studio_id) REFERENCES studios(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studio_id INT NOT NULL,
    client_id INT NULL,
    title VARCHAR(160) NOT NULL,
    body TEXT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    valid_until DATE,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (studio_id) REFERENCES studios(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- Homeplate Platform — MySQL Schema
-- Stack: PHP 8+, MySQL 8+
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS admin_logs, reports, favorites, notifications, messages,
    reviews, payments, order_items, orders, meals, meal_categories,
    cook_profiles, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ─── Users ────────────────────────────────────────────────────
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120)        NOT NULL,
    email         VARCHAR(191)        NOT NULL UNIQUE,
    password_hash VARCHAR(255)        NOT NULL,
    phone         VARCHAR(20),
    avatar        VARCHAR(255),
    role          ENUM('customer','cook','admin') NOT NULL DEFAULT 'customer',
    status        ENUM('active','inactive','banned') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role   (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Cook Profiles ────────────────────────────────────────────
CREATE TABLE cook_profiles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL UNIQUE,
    bio             TEXT,
    specialty       VARCHAR(255),
    id_document     VARCHAR(255),           -- uploaded ID file path
    verification    ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    verified_at     TIMESTAMP,
    verified_by     INT UNSIGNED,           -- admin user_id
    rating_avg      DECIMAL(3,2)  NOT NULL DEFAULT 0.00,
    rating_count    INT UNSIGNED  NOT NULL DEFAULT 0,
    total_earnings  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Meal Categories ──────────────────────────────────────────
CREATE TABLE meal_categories (
    id    TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(80) NOT NULL UNIQUE,
    icon  VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO meal_categories (name, icon) VALUES
    ('Breakfast',  'bi-sunrise'),
    ('Lunch',      'bi-sun'),
    ('Dinner',     'bi-moon'),
    ('Snacks',     'bi-egg-fried'),
    ('Desserts',   'bi-cake'),
    ('Beverages',  'bi-cup-straw'),
    ('Healthy',    'bi-heart'),
    ('Vegetarian', 'bi-leaf');

-- ─── Meals ────────────────────────────────────────────────────
CREATE TABLE meals (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cook_id       INT UNSIGNED NOT NULL,       -- references users.id (role=cook)
    category_id   TINYINT UNSIGNED,
    title         VARCHAR(200)        NOT NULL,
    description   TEXT,
    ingredients   TEXT,
    allergy_info  TEXT,
    price         DECIMAL(8,2)        NOT NULL,
    image         VARCHAR(255),
    is_available  TINYINT(1)          NOT NULL DEFAULT 1,
    is_deleted    TINYINT(1)          NOT NULL DEFAULT 0,
    rating_avg    DECIMAL(3,2)        NOT NULL DEFAULT 0.00,
    rating_count  INT UNSIGNED        NOT NULL DEFAULT 0,
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cook_id)     REFERENCES users(id)            ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES meal_categories(id)  ON DELETE SET NULL,
    INDEX idx_available  (is_available, is_deleted),
    INDEX idx_cook       (cook_id),
    FULLTEXT idx_search  (title, description, ingredients)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Orders ───────────────────────────────────────────────────
CREATE TABLE orders (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT UNSIGNED NOT NULL,
    cook_id         INT UNSIGNED NOT NULL,
    status          ENUM('pending','accepted','preparing','ready','out_for_delivery',
                         'delivered','cancelled') NOT NULL DEFAULT 'pending',
    delivery_address TEXT         NOT NULL,
    subtotal        DECIMAL(8,2) NOT NULL,
    delivery_fee    DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    total           DECIMAL(8,2) NOT NULL,
    notes           TEXT,
    cancelled_at    TIMESTAMP,
    cancel_reason   VARCHAR(255),
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (cook_id)     REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_customer (customer_id),
    INDEX idx_cook     (cook_id),
    INDEX idx_status   (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Order Items ──────────────────────────────────────────────
CREATE TABLE order_items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED    NOT NULL,
    meal_id    INT UNSIGNED    NOT NULL,
    quantity   TINYINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(8,2)   NOT NULL,   -- snapshot price at order time
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (meal_id)  REFERENCES meals(id)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Payments ─────────────────────────────────────────────────
CREATE TABLE payments (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id         INT UNSIGNED    NOT NULL UNIQUE,
    gateway          ENUM('stripe','paypal','cash') NOT NULL DEFAULT 'stripe',
    gateway_tx_id    VARCHAR(255),
    amount           DECIMAL(8,2)    NOT NULL,
    status           ENUM('pending','paid','refunded','failed') NOT NULL DEFAULT 'pending',
    paid_at          TIMESTAMP,
    refunded_at      TIMESTAMP,
    created_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Reviews ──────────────────────────────────────────────────
CREATE TABLE reviews (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL UNIQUE,   -- one review per order
    meal_id     INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    rating      TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id)    REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (meal_id)     REFERENCES meals(id)  ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id)  ON DELETE CASCADE,
    INDEX idx_meal (meal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Messages ─────────────────────────────────────────────────
CREATE TABLE messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id   INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    body        TEXT         NOT NULL,
    is_read     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conversation (sender_id, receiver_id),
    INDEX idx_unread       (receiver_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Notifications ────────────────────────────────────────────
CREATE TABLE notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    type       VARCHAR(60)  NOT NULL,   -- 'order_status','new_message','cook_verified', etc.
    title      VARCHAR(255) NOT NULL,
    body       TEXT,
    link       VARCHAR(255),
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Favorites ────────────────────────────────────────────────
CREATE TABLE favorites (
    user_id    INT UNSIGNED NOT NULL,
    meal_id    INT UNSIGNED NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, meal_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (meal_id) REFERENCES meals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Reports ──────────────────────────────────────────────────
CREATE TABLE reports (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT UNSIGNED NOT NULL,
    target_type ENUM('meal','user','review') NOT NULL,
    target_id   INT UNSIGNED NOT NULL,
    reason      VARCHAR(255) NOT NULL,
    details     TEXT,
    status      ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
    resolved_by INT UNSIGNED,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Admin Logs ───────────────────────────────────────────────
CREATE TABLE admin_logs (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id   INT UNSIGNED NOT NULL,
    action     VARCHAR(100) NOT NULL,
    target     VARCHAR(100),
    details    TEXT,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Default Admin Account ────────────────────────────────────
-- Password: Admin@1234  (change immediately after setup)
INSERT INTO users (name, email, password_hash, role) VALUES
    ('Admin', 'admin@homeplate.com',
     '$2y$12$VIc2ioUNEgC9X1c4X1Z9.O/X1vKNkDP6NUJLgpWqY5uEZRd8vNx4e',
     'admin');

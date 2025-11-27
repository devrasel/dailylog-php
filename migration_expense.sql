-- Add Expense table to existing database
USE dailylogdb;

CREATE TABLE IF NOT EXISTS Expense (
    id VARCHAR(191) PRIMARY KEY,
    date DATETIME NOT NULL,
    amount DOUBLE NOT NULL,
    category VARCHAR(191) NOT NULL,
    title VARCHAR(191) NOT NULL,
    description TEXT,
    paymentMethod VARCHAR(191) DEFAULT 'Cash',
    userId VARCHAR(191) NOT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES User(id) ON DELETE CASCADE,
    INDEX idx_user_date (userId, date),
    INDEX idx_category (category)
);

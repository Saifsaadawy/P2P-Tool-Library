-- ══════════════════════════════════════════════════════════════════
--  Tool Library — Complete Database Schema
--  Run this file once in phpMyAdmin → SQL tab
-- ══════════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS tool_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tool_library;

-- ── Member ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Member (
    MemberID       INT AUTO_INCREMENT PRIMARY KEY,
    Fname          VARCHAR(50)  NOT NULL,
    Lname          VARCHAR(50)  NOT NULL,
    Email          VARCHAR(100) NOT NULL UNIQUE,
    Password       VARCHAR(255) NOT NULL,
    Phone          VARCHAR(20),
    Balance        DECIMAL(10,2) DEFAULT 0,
    City           VARCHAR(50),
    Street         VARCHAR(100),
    MembershipTier ENUM('basic','silver','gold') DEFAULT 'basic',
    TrustScore     INT DEFAULT 50,
    Verified       TINYINT(1) DEFAULT 0,
    Status         ENUM('active','suspended','rejected') DEFAULT 'active',
    CreatedAt      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Librarian ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Librarian (
    LibrarianID INT AUTO_INCREMENT PRIMARY KEY,
    Fname       VARCHAR(50)  NOT NULL,
    Lname       VARCHAR(50)  NOT NULL,
    Email       VARCHAR(100) NOT NULL UNIQUE,
    Password    VARCHAR(255) NOT NULL,
    Phone       VARCHAR(20),
    Status      ENUM('active','inactive') DEFAULT 'active'
);

-- ── MaintenanceStaff ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS MaintenanceStaff (
    StaffID  INT AUTO_INCREMENT PRIMARY KEY,
    Fname    VARCHAR(50)  NOT NULL,
    Lname    VARCHAR(50)  NOT NULL,
    Email    VARCHAR(100) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Phone    VARCHAR(20),
    Status   ENUM('active','inactive') DEFAULT 'active'
);

-- ── Tool ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Tool (
    ToolID          INT AUTO_INCREMENT PRIMARY KEY,
    MemberID        INT NOT NULL,
    Name            VARCHAR(100) NOT NULL,
    Description     TEXT,
    Category        VARCHAR(50),
    DailyRate       DECIMAL(10,2) NOT NULL,
    UsageHour       INT DEFAULT 0,
    ToolCondition   ENUM('new','good','fair','poor') DEFAULT 'good',
    Availability    TINYINT(1) DEFAULT 1,
    CurrentStatus   ENUM('available','reserved','maintenance','pending') DEFAULT 'pending',
    SecurityDeposit DECIMAL(10,2) DEFAULT 0,
    SafetyExpiry    DATE,
    FOREIGN KEY (MemberID) REFERENCES Member(MemberID) ON DELETE CASCADE
);

-- ── Tool_URL ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Tool_URL (
    ToolID   INT NOT NULL,
    MediaURL VARCHAR(255) NOT NULL,
    PRIMARY KEY (ToolID, MediaURL),
    FOREIGN KEY (ToolID) REFERENCES Tool(ToolID) ON DELETE CASCADE
);

-- ── Reservation ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Reservation (
    ReservationID INT AUTO_INCREMENT PRIMARY KEY,
    MemberID      INT NOT NULL,
    ToolID        INT NOT NULL,
    LibrarianID   INT,
    StartDate     DATE NOT NULL,
    EndDate       DATE NOT NULL,
    PickupDate    DATE,
    ReturnDate    DATE,
    CheckedInAt   DATETIME,
    CheckedOutAt  DATETIME,
    TotalCost     DECIMAL(10,2) DEFAULT 0,
    Status        ENUM('pending','approved','cancelled','completed') DEFAULT 'pending',
    QR_Token      VARCHAR(255),
    FOREIGN KEY (MemberID)    REFERENCES Member(MemberID),
    FOREIGN KEY (ToolID)      REFERENCES Tool(ToolID),
    FOREIGN KEY (LibrarianID) REFERENCES Librarian(LibrarianID)
);

-- ── Payment ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Payment (
    PaymentID     INT AUTO_INCREMENT PRIMARY KEY,
    ReservationID INT NOT NULL,
    Amount        DECIMAL(10,2) NOT NULL,
    Status        ENUM('completed','pending','refunded','penalty') DEFAULT 'pending',
    CreatedAt     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ReservationID) REFERENCES Reservation(ReservationID)
);

-- ── DamageReport ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS DamageReport (
    ReportID      INT AUTO_INCREMENT PRIMARY KEY,
    ReservationID INT NOT NULL,
    Description   TEXT,
    Severity      ENUM('low','medium','high') DEFAULT 'low',
    FOREIGN KEY (ReservationID) REFERENCES Reservation(ReservationID)
);

-- ── Damage_URL ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Damage_URL (
    DamageID INT NOT NULL,
    ImageURL VARCHAR(255) NOT NULL,
    PRIMARY KEY (DamageID, ImageURL),
    FOREIGN KEY (DamageID) REFERENCES DamageReport(ReportID) ON DELETE CASCADE
);

-- ── MaintenanceRecord ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS MaintenanceRecord (
    RecordID    INT AUTO_INCREMENT PRIMARY KEY,
    ToolID      INT NOT NULL,
    StaffID     INT NOT NULL,
    LibrarianID INT,
    Date        DATE NOT NULL,
    Description TEXT,
    Cost        DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (ToolID)      REFERENCES Tool(ToolID),
    FOREIGN KEY (StaffID)     REFERENCES MaintenanceStaff(StaffID),
    FOREIGN KEY (LibrarianID) REFERENCES Librarian(LibrarianID)
);

-- ── Maintenance_URL ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Maintenance_URL (
    MaintenanceID INT NOT NULL,
    ImageURL      VARCHAR(255) NOT NULL,
    PRIMARY KEY (MaintenanceID, ImageURL),
    FOREIGN KEY (MaintenanceID) REFERENCES MaintenanceRecord(RecordID) ON DELETE CASCADE
);

-- ── Damage_Maintenance ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Damage_Maintenance (
    DamageID      INT NOT NULL,
    MaintenanceID INT NOT NULL,
    PRIMARY KEY (DamageID, MaintenanceID),
    FOREIGN KEY (DamageID)      REFERENCES DamageReport(ReportID),
    FOREIGN KEY (MaintenanceID) REFERENCES MaintenanceRecord(RecordID)
);

-- ── Message (Chat between Borrower & Lender per Reservation) ──────
CREATE TABLE IF NOT EXISTS Message (
    MessageID     INT AUTO_INCREMENT PRIMARY KEY,
    ReservationID INT NOT NULL,
    SenderID      INT NOT NULL,
    SenderRole    ENUM('member','librarian') NOT NULL,
    Body          TEXT NOT NULL,
    IsRead        TINYINT(1) DEFAULT 0,
    CreatedAt     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ReservationID) REFERENCES Reservation(ReservationID) ON DELETE CASCADE
);

-- ── KYC Documents (identity verification uploads) ─────────────────
CREATE TABLE IF NOT EXISTS KYC (
    KYCID      INT AUTO_INCREMENT PRIMARY KEY,
    MemberID   INT NOT NULL UNIQUE,
    DocType    VARCHAR(50),
    DocURL     VARCHAR(255),
    Status     ENUM('pending','approved','rejected') DEFAULT 'pending',
    UploadedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (MemberID) REFERENCES Member(MemberID) ON DELETE CASCADE
);

-- ══════════════════════════════════════════════════════════════════
--  Seed Data — Default Librarian & Maintenance Staff
--  Password for both: admin123
-- ══════════════════════════════════════════════════════════════════

INSERT IGNORE INTO Librarian (Fname, Lname, Email, Password, Phone, Status)
VALUES (
    'Admin', 'Librarian',
    'librarian@toollibrary.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uivHd7WHW',
    '01000000000',
    'active'
);

INSERT IGNORE INTO MaintenanceStaff (Fname, Lname, Email, Password, Phone, Status)
VALUES (
    'Tech', 'Staff',
    'maintenance@toollibrary.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uivHd7WHW',
    '01000000001',
    'active'
);

-- ══════════════════════════════════════════════════════════════════
--  Indexes for performance
-- ══════════════════════════════════════════════════════════════════

CREATE INDEX IF NOT EXISTS idx_reservation_member ON Reservation(MemberID);
CREATE INDEX IF NOT EXISTS idx_reservation_tool   ON Reservation(ToolID);
CREATE INDEX IF NOT EXISTS idx_reservation_status ON Reservation(Status);
CREATE INDEX IF NOT EXISTS idx_tool_status        ON Tool(CurrentStatus);
CREATE INDEX IF NOT EXISTS idx_message_reservation ON Message(ReservationID);
CREATE INDEX IF NOT EXISTS idx_payment_reservation ON Payment(ReservationID);
-- =========================================================
-- VISRS DATABASE
-- Vehicle Information Search & Retrieval System
-- =========================================================

CREATE DATABASE IF NOT EXISTS visrs;

USE visrs;


-- =========================================================
-- 1. USERS
-- =========================================================

CREATE TABLE IF NOT EXISTS users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    Email VARCHAR(150) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Role ENUM('Admin', 'Police', 'Seller', 'User')
        NOT NULL DEFAULT 'User',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================================
-- 2. OWNERS
-- =========================================================

CREATE TABLE IF NOT EXISTS owners (
    OwnerID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    Address VARCHAR(255),
    Phone VARCHAR(30),
    Email VARCHAR(150),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================================
-- 3. VEHICLES
-- =========================================================

CREATE TABLE IF NOT EXISTS vehicles (
    VehicleID INT AUTO_INCREMENT PRIMARY KEY,
    PlateNumber VARCHAR(20) NOT NULL UNIQUE,
    VIN VARCHAR(50) NOT NULL UNIQUE,
    OwnerID INT NOT NULL,
    Make VARCHAR(100) NOT NULL,
    Model VARCHAR(100) NOT NULL,
    VehicleYear YEAR NOT NULL,
    Color VARCHAR(50),
    VehicleType VARCHAR(50),
    EngineNumber VARCHAR(100),
    RegistrationDate DATE,
    Status ENUM(
        'Active',
        'Inactive',
        'Stolen',
        'Sold'
    ) NOT NULL DEFAULT 'Active',

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_vehicle_owner
        FOREIGN KEY (OwnerID)
        REFERENCES owners(OwnerID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- =========================================================
-- 4. OWNERSHIP HISTORY
-- =========================================================

CREATE TABLE IF NOT EXISTS ownership_history (
    OwnershipID INT AUTO_INCREMENT PRIMARY KEY,

    VehicleID INT NOT NULL,
    OwnerID INT NOT NULL,

    StartDate DATE NOT NULL,
    EndDate DATE,

    TransferReason VARCHAR(255),

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_history_vehicle
        FOREIGN KEY (VehicleID)
        REFERENCES vehicles(VehicleID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_history_owner
        FOREIGN KEY (OwnerID)
        REFERENCES owners(OwnerID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- =========================================================
-- 5. INSURANCE
-- =========================================================

CREATE TABLE IF NOT EXISTS insurance (
    InsuranceID INT AUTO_INCREMENT PRIMARY KEY,

    VehicleID INT NOT NULL,

    ProviderName VARCHAR(150) NOT NULL,
    PolicyNumber VARCHAR(100) NOT NULL,
    CoverageType VARCHAR(100),

    StartDate DATE,
    ExpiryDate DATE,

    Status ENUM(
        'Active',
        'Expired',
        'Cancelled'
    ) NOT NULL DEFAULT 'Active',

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_insurance_vehicle
        FOREIGN KEY (VehicleID)
        REFERENCES vehicles(VehicleID)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


-- =========================================================
-- 6. ACCIDENTS
-- =========================================================

CREATE TABLE IF NOT EXISTS accidents (
    AccidentID INT AUTO_INCREMENT PRIMARY KEY,

    VehicleID INT NOT NULL,

    AccidentDate DATE NOT NULL,
    Location VARCHAR(255),
    Description TEXT,

    DamageLevel ENUM(
        'Minor',
        'Moderate',
        'Major',
        'Severe'
    ) NOT NULL DEFAULT 'Minor',

    ReportNumber VARCHAR(100),

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_accident_vehicle
        FOREIGN KEY (VehicleID)
        REFERENCES vehicles(VehicleID)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


-- =========================================================
-- 7. AUDIT LOGS
-- =========================================================

CREATE TABLE IF NOT EXISTS audit_logs (
    LogID INT AUTO_INCREMENT PRIMARY KEY,

    UserID INT,

    Action VARCHAR(255) NOT NULL,
    TableAffected VARCHAR(100),
    RecordID INT,

    IPAddress VARCHAR(45),

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_user
        FOREIGN KEY (UserID)
        REFERENCES users(UserID)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);


-- =========================================================
-- INDEXES
-- =========================================================

CREATE INDEX idx_vehicle_owner
    ON vehicles(OwnerID);

CREATE INDEX idx_vehicle_plate
    ON vehicles(PlateNumber);

CREATE INDEX idx_vehicle_vin
    ON vehicles(VIN);

CREATE INDEX idx_vehicle_status
    ON vehicles(Status);

CREATE INDEX idx_history_vehicle
    ON ownership_history(VehicleID);

CREATE INDEX idx_history_owner
    ON ownership_history(OwnerID);

CREATE INDEX idx_insurance_vehicle
    ON insurance(VehicleID);

CREATE INDEX idx_insurance_status
    ON insurance(Status);

CREATE INDEX idx_accident_vehicle
    ON accidents(VehicleID);

CREATE INDEX idx_accident_date
    ON accidents(AccidentDate);

CREATE INDEX idx_audit_user
    ON audit_logs(UserID);

CREATE INDEX idx_audit_created
    ON audit_logs(CreatedAt);


-- =========================================================
-- END OF VISRS DATABASE
-- =========================================================
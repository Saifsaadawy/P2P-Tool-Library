USE tool_library;

-- Members
INSERT INTO Member (Fname, Lname, Email, Password, Phone, Balance, City, MembershipTier, TrustScore, Verified, Status) VALUES
('Ahmed',   'Hassan',  'ahmed@mail.com',  '$2y$10$examplehash1', '01001000001', 200, 'Cairo',  'gold',  85, 1, 'active'),
('Sara',    'Ali',     'sara@mail.com',   '$2y$10$examplehash2', '01001000002', 100, 'Giza',   'silver',70, 1, 'active'),
('Mohamed', 'Khaled',  'moh@mail.com',    '$2y$10$examplehash3', '01001000003', 50,  'Cairo',  'basic', 50, 0, 'active');

-- Librarian
INSERT INTO Librarian (Fname, Lname, Email, Password, Phone) VALUES
('Nour', 'Ibrahim', 'librarian@toollibrary.com', '$2y$10$examplehash4', '01001000010');

-- Maintenance Staff
INSERT INTO MaintenanceStaff (Fname, Lname, Email, Password, Phone) VALUES
('Karim', 'Mostafa', 'maintenance@toollibrary.com', '$2y$10$examplehash5', '01001000020');

-- Tools
INSERT INTO Tool (MemberID, Name, Description, Category, DailyRate, Condition, Availability, CurrentStatus, SecurityDeposit) VALUES
(1, 'Power Drill',    'Bosch 18V cordless drill',       'Power Tools', 15.00, 'good', 1, 'available', 50),
(1, 'Circular Saw',   'Makita 7-inch circular saw',     'Power Tools', 20.00, 'good', 1, 'available', 80),
(2, 'Ladder 3m',      'Aluminum 3-meter ladder',        'Ladders',     10.00, 'fair', 1, 'available', 30),
(2, 'Pressure Washer','Karcher K4 pressure washer',     'Cleaning',    25.00, 'new',  1, 'available', 100),
(3, 'Angle Grinder',  '125mm angle grinder',            'Power Tools', 12.00, 'good', 1, 'available', 40);

-- Reservations
INSERT INTO Reservation (MemberID, ToolID, LibrarianID, StartDate, EndDate, PickupDate, TotalCost, Status, QR_Token) VALUES
(2, 1, 1, '2026-05-01', '2026-05-03', '2026-05-01', 45.00,  'completed', UUID()),
(3, 3, 1, '2026-05-05', '2026-05-07', '2026-05-05', 30.00,  'approved',  UUID()),
(2, 4, NULL, '2026-05-10', '2026-05-12', NULL,       75.00,  'pending',   UUID());

-- Payments
INSERT INTO Payment (ReservationID, Amount, Status) VALUES
(1, 45.00, 'completed'),
(2, 30.00, 'completed');

-- Damage Report
INSERT INTO DamageReport (ReservationID, Description, Severity) VALUES
(1, 'Small scratch on drill body', 'low');

-- Maintenance Record
INSERT INTO MaintenanceRecord (ToolID, StaffID, LibrarianID, Date, Description, Cost) VALUES
(1, 1, 1, '2026-05-04', 'Cleaned and checked drill battery', 20.00);

-- Link damage to maintenance
INSERT INTO Damage_Maintenance (DamageID, MaintenanceID) VALUES (1, 1);

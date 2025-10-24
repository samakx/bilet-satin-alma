<?php
// Database initialization and schema creation

function initializeDatabase($db) {
    
    // Users table
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        name TEXT NOT NULL,
        phone TEXT,
        role TEXT DEFAULT 'user',
        company_id INTEGER,
        balance REAL DEFAULT 1000.00,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id)
    )");
    
    // Companies table
    $db->exec("CREATE TABLE IF NOT EXISTS companies (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        logo TEXT,
        phone TEXT,
        email TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Trips table
    $db->exec("CREATE TABLE IF NOT EXISTS trips (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        company_id INTEGER NOT NULL,
        departure_city TEXT NOT NULL,
        arrival_city TEXT NOT NULL,
        departure_date DATE NOT NULL,
        departure_time TIME NOT NULL,
        arrival_time TIME NOT NULL,
        price REAL NOT NULL,
        total_seats INTEGER DEFAULT 45,
        available_seats INTEGER DEFAULT 45,
        bus_plate TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id)
    )");
    
    // Tickets table
    $db->exec("CREATE TABLE IF NOT EXISTS tickets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        trip_id INTEGER NOT NULL,
        seat_number INTEGER NOT NULL,
        price REAL NOT NULL,
        status TEXT DEFAULT 'active',
        coupon_code TEXT,
        discount_amount REAL DEFAULT 0,
        booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (trip_id) REFERENCES trips(id)
    )");
    
    // Coupons table
    $db->exec("CREATE TABLE IF NOT EXISTS coupons (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT UNIQUE NOT NULL,
        discount_percentage INTEGER NOT NULL,
        company_id INTEGER,
        usage_limit INTEGER DEFAULT 100,
        used_count INTEGER DEFAULT 0,
        expiry_date DATE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id)
    )");
    
    // Create indexes for better performance
    $db->exec("CREATE INDEX IF NOT EXISTS idx_trips_date ON trips(departure_date)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_trips_cities ON trips(departure_city, arrival_city)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tickets_user ON tickets(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tickets_trip ON tickets(trip_id)");
}

function seedDatabase($db) {
    // Check if data already exists
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetchArray();
    
    if ($row['count'] > 0) {
        return; // Database already seeded
    }
    
    // Insert admin user
    $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
    $db->exec("INSERT INTO users (email, password, name, role, balance) 
               VALUES ('admin@platform.com', '$adminPassword', 'System Admin', 'admin', 10000)");
    
    // Insert sample companies
    $db->exec("INSERT INTO companies (name, phone, email) VALUES 
        ('Metro Turizm', '0850 222 34 55', 'info@metroturizm.com'),
        ('Pamukkale Turizm', '0850 333 35 11', 'info@pamukkale.com.tr'),
        ('Kamil Koç', '0850 245 00 70', 'info@kamilkoc.com.tr'),
        ('Ulusoy', '0850 811 18 88', 'info@ulusoy.com.tr')");
    
    // Insert company admins
    $companyAdminPass = password_hash('firma123', PASSWORD_BCRYPT);
    $db->exec("INSERT INTO users (email, password, name, role, company_id, balance) VALUES 
        ('metro@turizm.com', '$companyAdminPass', 'Metro Yetkili', 'company_admin', 1, 5000),
        ('pamukkale@turizm.com', '$companyAdminPass', 'Pamukkale Yetkili', 'company_admin', 2, 5000),
        ('kamil@koc.com', '$companyAdminPass', 'Kamil Koç Yetkili', 'company_admin', 3, 5000),
        ('ulusoy@turizm.com', '$companyAdminPass', 'Ulusoy Yetkili', 'company_admin', 4, 5000)");
    
    // Insert sample users
    $userPassword = password_hash('user123', PASSWORD_BCRYPT);
    $db->exec("INSERT INTO users (email, password, name, phone, balance) VALUES 
        ('ahmet@email.com', '$userPassword', 'Ahmet Yılmaz', '5551234567', 2500),
        ('ayse@email.com', '$userPassword', 'Ayşe Demir', '5559876543', 3000),
        ('mehmet@email.com', '$userPassword', 'Mehmet Kaya', '5556547890', 1800)");
    
    // Insert sample trips
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $dayAfter = date('Y-m-d', strtotime('+2 days'));
    
    $db->exec("INSERT INTO trips (company_id, departure_city, arrival_city, departure_date, departure_time, arrival_time, price, total_seats, available_seats, bus_plate) VALUES 
        (1, 'Istanbul', 'Ankara', '$today', '09:00', '14:30', 350.00, 45, 45, '34 ABC 123'),
        (1, 'Istanbul', 'Ankara', '$today', '15:00', '20:30', 380.00, 45, 42, '34 XYZ 456'),
        (1, 'Istanbul', 'Izmir', '$tomorrow', '10:00', '18:00', 420.00, 45, 45, '34 DEF 789'),
        (2, 'Ankara', 'Antalya', '$tomorrow', '08:00', '16:00', 450.00, 45, 40, '06 PAM 001'),
        (2, 'Istanbul', 'Trabzon', '$dayAfter', '20:00', '08:00', 550.00, 45, 45, '34 PAM 002'),
        (3, 'Izmir', 'Ankara', '$today', '11:00', '18:30', 400.00, 45, 38, '35 KK 123'),
        (3, 'Istanbul', 'Bursa', '$tomorrow', '07:00', '09:30', 180.00, 45, 45, '34 KK 456'),
        (4, 'Ankara', 'Istanbul', '$today', '13:00', '18:30', 360.00, 45, 35, '06 ULS 789'),
        (4, 'Istanbul', 'Adana', '$dayAfter', '21:00', '09:00', 480.00, 45, 45, '34 ULS 012')");
    
    // Insert some sold tickets
    $db->exec("INSERT INTO tickets (user_id, trip_id, seat_number, price, status) VALUES 
        (6, 2, 1, 380.00, 'active'),
        (6, 2, 2, 380.00, 'active'),
        (6, 2, 3, 380.00, 'active'),
        (7, 4, 5, 450.00, 'active'),
        (7, 4, 6, 450.00, 'active'),
        (8, 6, 10, 400.00, 'active'),
        (8, 8, 15, 360.00, 'active')");
    
    // Update available seats
    $db->exec("UPDATE trips SET available_seats = 42 WHERE id = 2");
    $db->exec("UPDATE trips SET available_seats = 43 WHERE id = 4");
    $db->exec("UPDATE trips SET available_seats = 44 WHERE id = 6");
    $db->exec("UPDATE trips SET available_seats = 44 WHERE id = 8");
    
    // Insert sample coupons
    $expiryDate = date('Y-m-d', strtotime('+30 days'));
    $db->exec("INSERT INTO coupons (code, discount_percentage, company_id, usage_limit, expiry_date) VALUES 
        ('YILBASI2025', 20, NULL, 100, '$expiryDate'),
        ('METRO10', 10, 1, 50, '$expiryDate'),
        ('PAMUKKALE15', 15, 2, 50, '$expiryDate'),
        ('KAMPANYA25', 25, NULL, 200, '$expiryDate')");
}
?>
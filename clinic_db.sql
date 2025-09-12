
-- Patients master table
CREATE TABLE IF NOT EXISTS patients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  sex ENUM('Male', 'Female', 'Other') DEFAULT NULL,
  birthdate DATE DEFAULT NULL,
  contact VARCHAR(50) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Check-ups / outpatient visits
CREATE TABLE IF NOT EXISTS checkups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  visit_date DATE NOT NULL,
  complaint TEXT,
  diagnosis TEXT,
  treatment TEXT,
  height_cm DECIMAL(5,2) DEFAULT NULL,
  weight_kg DECIMAL(5,2) DEFAULT NULL,
  temp_c DECIMAL(4,1) DEFAULT NULL,
  pulse INT DEFAULT NULL,
  resp_rate INT DEFAULT NULL,
  spo2 INT DEFAULT NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Blood pressure monitoring
CREATE TABLE IF NOT EXISTS bp_readings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  reading_time DATETIME NOT NULL,
  systolic INT NOT NULL,
  diastolic INT NOT NULL,
  pulse INT DEFAULT NULL,
  position VARCHAR(50) DEFAULT NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- In-patient admissions
CREATE TABLE IF NOT EXISTS inpatient_admissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  admit_date DATETIME NOT NULL,
  ward VARCHAR(100) DEFAULT NULL,
  bed VARCHAR(50) DEFAULT NULL,
  physician VARCHAR(150) DEFAULT NULL,
  diagnosis TEXT,
  notes TEXT,
  discharge_date DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Helpful indexes
CREATE INDEX idx_patients_name ON patients(last_name, first_name);
CREATE INDEX idx_checkups_patient_date ON checkups(patient_id, visit_date);
CREATE INDEX idx_bp_patient_time ON bp_readings(patient_id, reading_time);
CREATE INDEX idx_inpatient_patient_admit ON inpatient_admissions(patient_id, admit_date);

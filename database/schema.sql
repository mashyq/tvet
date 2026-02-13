CREATE DATABASE IF NOT EXISTS college_arms;
USE college_arms;

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    hod_name VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE programmes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    duration_years INT NOT NULL DEFAULT 4,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_programme_department FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','registrar','lecturer') NOT NULL,
    department_id INT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_department FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matric_no VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    gender ENUM('Male','Female') NOT NULL,
    email VARCHAR(120) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    programme_id INT NOT NULL,
    level VARCHAR(20) NOT NULL,
    admission_year YEAR NOT NULL,
    status ENUM('Active','Graduated','Suspended') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_programme FOREIGN KEY (programme_id) REFERENCES programmes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(160) NOT NULL,
    credit_unit INT NOT NULL,
    semester ENUM('First','Second') NOT NULL,
    level VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_course_department FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE lecturer_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT NOT NULL,
    course_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_lecturer_course (lecturer_id, course_id),
    CONSTRAINT fk_lc_lecturer FOREIGN KEY (lecturer_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_lc_course FOREIGN KEY (course_id) REFERENCES courses(id)
        ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    session_year VARCHAR(9) NOT NULL,
    semester ENUM('First','Second') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enrollment (student_id, course_id, session_year, semester),
    CONSTRAINT fk_enrollment_student FOREIGN KEY (student_id) REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_enrollment_course FOREIGN KEY (course_id) REFERENCES courses(id)
        ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL UNIQUE,
    score DECIMAL(5,2) NOT NULL,
    grade CHAR(2) NOT NULL,
    grade_point DECIMAL(3,2) NOT NULL,
    remark VARCHAR(20) NOT NULL,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_grade_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_grade_user FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE student_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    session_year VARCHAR(9) NOT NULL,
    description VARCHAR(160) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('Unpaid','Part Paid','Paid') NOT NULL DEFAULT 'Unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fee_student FOREIGN KEY (student_id) REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE fee_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_fee_id INT NOT NULL,
    amount_paid DECIMAL(12,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_ref VARCHAR(80) DEFAULT NULL,
    recorded_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_fee FOREIGN KEY (student_fee_id) REFERENCES student_fees(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_payment_user FOREIGN KEY (recorded_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

-- Default admin login: admin@college.local / admin123
INSERT INTO users (full_name, email, password_hash, role) VALUES
('System Administrator', 'admin@college.local', '$2y$10$qEblFu6uD4D/BtDACJjfauCQXUV9.8qiQU438hIm7EMhW.F9w0PBy', 'admin');

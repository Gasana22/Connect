-- =====================================================================
-- Let's Go Medical - Medical Tourism Platform
-- Database Schema + Seed Data
-- Import this file via phpMyAdmin (XAMPP) or:
--   mysql -u root -p < schema.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS medical_tourism CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE medical_tourism;

-- ---------------------------------------------------------------------
-- Admin users
-- ---------------------------------------------------------------------
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','editor') NOT NULL DEFAULT 'editor',
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    last_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default login: admin@letsgomedical.test / admin123
INSERT INTO admin_users (name, email, password, role) VALUES
('Site Administrator', 'admin@letsgomedical.test', '$2y$12$yFzRYvO7tk/ARMc6guLDhuYyAljAQeKM9U6CnpJFN6jthkXSyMxT2', 'super_admin');

-- ---------------------------------------------------------------------
-- Destinations (countries)
-- ---------------------------------------------------------------------
CREATE TABLE destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    summary VARCHAR(255) NULL,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    avg_savings VARCHAR(50) NULL,
    status ENUM('published','draft') NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO destinations (name, slug, summary, description, image, avg_savings) VALUES
('Turkey', 'turkey', 'World-leading hub for hair transplants, dental & cosmetic surgery.', 'Turkey combines JCI-accredited hospitals, internationally trained surgeons and modern facilities in Istanbul and Antalya, making it one of the most visited medical tourism destinations in the world.', 'turkey.jpg', 'Up to 70%'),
('Thailand', 'thailand', 'Renowned for cosmetic surgery, dental work and wellness retreats.', 'Thailand offers award-winning hospitals such as Bumrungrad International, combining five-star hospitality with advanced medical technology across Bangkok and Phuket.', 'thailand.jpg', 'Up to 65%'),
('India', 'india', 'Advanced cardiac, orthopedic and transplant care at a fraction of the cost.', 'India is home to internationally accredited super-specialty hospitals in Delhi, Mumbai and Chennai offering world-class cardiac, orthopedic and cancer treatment.', 'india.jpg', 'Up to 75%'),
('Mexico', 'mexico', 'Convenient, affordable dental and bariatric care close to the US border.', 'Mexico is a top choice for North American patients thanks to its proximity, English-speaking staff and modern clinics in Cancun, Tijuana and Guadalajara.', 'mexico.jpg', 'Up to 60%'),
('South Korea', 'south-korea', 'The global capital of plastic surgery and dermatology.', 'South Korea leads the world in cosmetic and reconstructive surgery, with Seoul home to hundreds of specialist clinics using the latest techniques.', 'south-korea.jpg', 'Up to 50%'),
('Malaysia', 'malaysia', 'Affordable, high-quality healthcare with excellent English proficiency.', 'Malaysia offers JCI-accredited hospitals in Kuala Lumpur and Penang, known for cardiology, fertility and health screening packages.', 'malaysia.jpg', 'Up to 65%');

-- ---------------------------------------------------------------------
-- Homepage hero slider images (managed from Admin > Home Page)
-- ---------------------------------------------------------------------
CREATE TABLE hero_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('published','draft') NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Treatment categories (managed from Admin > Categories)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO categories (name) VALUES
('Dental'), ('Cosmetic'), ('Orthopedic'), ('Cardiac'), ('Fertility'), ('Bariatric'), ('Ophthalmology');

-- ---------------------------------------------------------------------
-- Treatments
-- ---------------------------------------------------------------------
CREATE TABLE treatments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    category VARCHAR(80) NOT NULL,
    summary VARCHAR(255) NULL,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    min_price DECIMAL(10,2) NULL,
    max_price DECIMAL(10,2) NULL,
    avg_duration VARCHAR(50) NULL,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    show_price TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('published','draft') NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO treatments (name, slug, category, summary, description, image, min_price, max_price, avg_duration, featured) VALUES
('Dental Implants', 'dental-implants', 'Dental', 'Full-mouth restoration and single implants with premium materials.', 'Dental implants performed by prosthodontic specialists using leading implant brands, including full-mouth all-on-4 and all-on-6 procedures.', 'dental-implants.jpg', 600, 2500, '5-7 days', 1),
('Hair Transplant (FUE)', 'hair-transplant-fue', 'Cosmetic', 'Natural-looking results using follicular unit extraction.', 'FUE hair transplant performed by specialist clinics with graft counts tailored to each patient, minimal scarring and fast recovery.', 'hair-transplant.jpg', 1500, 4000, '3-4 days', 1),
('Rhinoplasty', 'rhinoplasty', 'Cosmetic', 'Functional and cosmetic nose reshaping surgery.', 'Rhinoplasty combining aesthetic reshaping with functional airway correction, performed by board-certified plastic surgeons.', 'rhinoplasty.jpg', 2000, 5500, '7-10 days', 1),
('Knee Replacement', 'knee-replacement', 'Orthopedic', 'Total and partial knee replacement using advanced implants.', 'Total knee replacement using globally recognized implant systems with dedicated physiotherapy programs included.', 'knee-replacement.jpg', 5000, 9500, '10-14 days', 1),
('Heart Bypass Surgery (CABG)', 'heart-bypass-cabg', 'Cardiac', 'Coronary artery bypass grafting with world-class cardiac teams.', 'CABG surgery performed in accredited cardiac centers with 24/7 ICU support and comprehensive pre/post-operative cardiology care.', 'cardiac-surgery.jpg', 7000, 15000, '14-21 days', 1),
('IVF Treatment', 'ivf-treatment', 'Fertility', 'High success-rate IVF cycles with experienced fertility specialists.', 'IVF treatment including ICSI, embryo freezing and genetic screening options at leading fertility centers.', 'ivf.jpg', 3000, 6500, '2-3 weeks', 0),
('Gastric Sleeve Surgery', 'gastric-sleeve-surgery', 'Bariatric', 'Laparoscopic weight-loss surgery with long-term support programs.', 'Laparoscopic sleeve gastrectomy performed by bariatric specialists, including nutritional counseling and follow-up care.', 'gastric-sleeve.jpg', 3500, 7000, '5-7 days', 1),
('Dental Veneers', 'dental-veneers', 'Dental', 'Porcelain and E-max veneers for a complete smile makeover.', 'Smile makeover using premium porcelain or E-max veneers designed with digital smile design technology.', 'veneers.jpg', 250, 600, '4-6 days', 0),
('Hip Replacement', 'hip-replacement', 'Orthopedic', 'Minimally invasive total hip replacement surgery.', 'Total hip replacement using minimally invasive anterior approach techniques to speed up recovery.', 'hip-replacement.jpg', 6000, 11000, '10-14 days', 0),
('LASIK Eye Surgery', 'lasik-eye-surgery', 'Ophthalmology', 'Bladeless LASIK for vision correction in under an hour.', 'Bladeless, all-laser LASIK vision correction performed by experienced ophthalmic surgeons.', 'lasik.jpg', 800, 2200, '2-3 days', 0);

-- ---------------------------------------------------------------------
-- Hospitals / clinics
-- ---------------------------------------------------------------------
CREATE TABLE hospitals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL UNIQUE,
    city VARCHAR(100) NULL,
    summary VARCHAR(255) NULL,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    accreditations VARCHAR(255) NULL,
    established_year INT NULL,
    bed_count INT NULL,
    status ENUM('published','draft') NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hospital_destination FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO hospitals (destination_id, name, slug, city, summary, description, image, accreditations, established_year, bed_count) VALUES
(1, 'Istanbul Wellness Hospital', 'istanbul-wellness-hospital', 'Istanbul', 'JCI-accredited hospital specializing in cosmetic and dental care.', 'A modern multi-specialty hospital in Istanbul offering cosmetic surgery, dental care and hair restoration with an international patient department.', 'hospital-istanbul.jpg', 'JCI, ISO 9001', 2005, 180),
(2, 'Bumrungrad Excellence Center', 'bumrungrad-excellence-center', 'Bangkok', 'One of Asia''s largest private hospitals with 55+ specialties.', 'A world-renowned hospital treating over a million patients a year, offering advanced cardiac, orthopedic and cosmetic care.', 'hospital-bangkok.jpg', 'JCI, HA Thailand', 1980, 580),
(3, 'Apollo Advanced Care', 'apollo-advanced-care', 'New Delhi', 'Leading super-specialty hospital for cardiac and transplant care.', 'A super-specialty hospital renowned for cardiac surgery, organ transplants and oncology with cutting-edge robotic surgery units.', 'hospital-delhi.jpg', 'JCI, NABH', 1983, 710),
(4, 'Cancun Dental & Surgery Institute', 'cancun-dental-surgery-institute', 'Cancun', 'Trusted by North American patients for dental and bariatric surgery.', 'A modern surgical facility minutes from Cancun airport, specializing in dental restoration and bariatric procedures.', 'hospital-cancun.jpg', 'NABH International, ISO', 2010, 60),
(5, 'Seoul Aesthetic Medical Center', 'seoul-aesthetic-medical-center', 'Seoul', 'Premier cosmetic surgery center in Gangnam.', 'Located in Gangnam, this center specializes in facial contouring, rhinoplasty and skin treatments using the latest technology.', 'hospital-seoul.jpg', 'KOIHA Certified', 2008, 45),
(6, 'KL Heart & Fertility Institute', 'kl-heart-fertility-institute', 'Kuala Lumpur', 'Specialist center for cardiology and fertility treatments.', 'A specialist institute combining a cardiac catheterization lab with an award-winning IVF fertility unit.', 'hospital-kl.jpg', 'JCI, MSQH', 1998, 210);

-- ---------------------------------------------------------------------
-- Doctors
-- ---------------------------------------------------------------------
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL UNIQUE,
    specialty VARCHAR(120) NULL,
    bio TEXT NULL,
    photo VARCHAR(255) NULL,
    experience_years INT NULL,
    status ENUM('published','draft') NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doctor_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO doctors (hospital_id, name, slug, specialty, bio, photo, experience_years) VALUES
(1, 'Dr. Emre Yildiz', 'dr-emre-yildiz', 'Cosmetic & Hair Restoration Surgeon', 'Dr. Yildiz has performed over 6,000 hair transplant and cosmetic procedures and trains surgeons internationally.', 'doctor-1.jpg', 15),
(2, 'Dr. Sirinya Chaiyasit', 'dr-sirinya-chaiyasit', 'Plastic & Reconstructive Surgeon', 'Board-certified plastic surgeon with fellowship training in Korea and the United States.', 'doctor-2.jpg', 12),
(3, 'Dr. Raghav Mehta', 'dr-raghav-mehta', 'Cardiothoracic Surgeon', 'Senior cardiothoracic surgeon specializing in minimally invasive bypass surgery, with over 4,000 successful operations.', 'doctor-3.jpg', 20),
(4, 'Dr. Luis Fernandez', 'dr-luis-fernandez', 'Bariatric & General Surgeon', 'Specialist in laparoscopic bariatric surgery with a strong track record of long-term patient success.', 'doctor-4.jpg', 10),
(5, 'Dr. Ji-ho Park', 'dr-ji-ho-park', 'Facial Plastic Surgeon', 'Renowned facial plastic surgeon known for natural-looking rhinoplasty and facial contouring results.', 'doctor-5.jpg', 14),
(6, 'Dr. Aisyah Rahman', 'dr-aisyah-rahman', 'Fertility Specialist (IVF)', 'Fertility consultant with a strong record of high IVF success rates and personalized treatment plans.', 'doctor-6.jpg', 11);

-- ---------------------------------------------------------------------
-- Packages
-- ---------------------------------------------------------------------
CREATE TABLE packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    treatment_id INT NOT NULL,
    hospital_id INT NOT NULL,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    summary VARCHAR(255) NULL,
    description TEXT NULL,
    includes TEXT NULL,
    excludes TEXT NULL,
    image VARCHAR(255) NULL,
    price DECIMAL(10,2) NOT NULL,
    duration VARCHAR(50) NULL,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    show_price TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('published','draft') NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_package_treatment FOREIGN KEY (treatment_id) REFERENCES treatments(id) ON DELETE CASCADE,
    CONSTRAINT fk_package_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO packages (treatment_id, hospital_id, title, slug, summary, description, includes, excludes, image, price, duration, featured) VALUES
(2, 1, 'Complete FUE Hair Transplant Package - Istanbul', 'complete-fue-hair-transplant-istanbul', 'All-inclusive hair transplant with hotel stay and VIP transfer.', 'A full-service hair restoration journey combining a premium FUE procedure with a comfortable stay in Istanbul.', '5-star hotel (4 nights)\nVIP airport transfers\nSurgery + PRP session\nMedication kit\nAftercare follow-up', 'International flights\nMeals outside of hotel breakfast\nTravel insurance\nAdditional PRP sessions beyond the first', 'package-hair.jpg', 1899.00, '4 days / 3 nights', 1),
(1, 1, 'All-on-4 Dental Implants Package', 'all-on-4-dental-implants-package', 'Full-arch restoration with premium implant brand.', 'Complete smile restoration using the All-on-4 technique, including all surgical and prosthetic stages.', 'Consultation & 3D scan\nImplant surgery (upper or lower arch)\nTemporary + final prosthesis\nHotel (5 nights)\nAirport transfers', 'International flights\nOpposite-arch treatment (if needed)\nTravel insurance\nMeals outside of hotel breakfast', 'package-dental.jpg', 5200.00, '6 days / 5 nights', 1),
(5, 3, 'Cardiac Bypass Surgery Package - Delhi', 'cardiac-bypass-surgery-package-delhi', 'Comprehensive CABG package with top cardiac surgeons.', 'A complete cardiac care journey including surgery, ICU stay and rehabilitation planning.', 'Pre-op cardiac work-up\nCABG surgery\nICU + ward stay (10 nights)\nCardiac rehab consultation\nAirport pickup', 'International flights\nExtended ICU stay beyond 3 nights\nTravel insurance\nCompanion accommodation', 'package-cardiac.jpg', 9800.00, '14 days', 1),
(7, 4, 'Gastric Sleeve Weight Loss Package', 'gastric-sleeve-weight-loss-package', 'Laparoscopic sleeve surgery with post-op nutrition plan.', 'A supportive bariatric surgery package designed for a safe, comfortable recovery close to Cancun beaches.', 'Pre-op labs & consultation\nLaparoscopic sleeve gastrectomy\nHospital stay (2 nights)\nHotel recovery stay (3 nights)\n12-month nutrition plan', 'International flights\nTravel insurance\nMeals outside of hotel breakfast\nSupplements after the first month', 'package-bariatric.jpg', 4500.00, '5 days / 4 nights', 1),
(3, 5, 'Rhinoplasty & Recovery Retreat - Seoul', 'rhinoplasty-recovery-retreat-seoul', 'Precision rhinoplasty with a relaxing Seoul recovery stay.', 'Combines expert rhinoplasty with a comfortable boutique hotel recovery stay in Gangnam.', 'Consultation & 3D simulation\nRhinoplasty surgery\nBoutique hotel (5 nights)\n2 follow-up visits', 'International flights\nTravel insurance\nMeals outside of hotel breakfast\nRevision surgery', 'package-rhino.jpg', 3400.00, '6 days / 5 nights', 0),
(6, 6, 'IVF Treatment Package - Kuala Lumpur', 'ivf-treatment-package-kuala-lumpur', 'Full IVF cycle with genetic screening add-on options.', 'A complete IVF journey with a dedicated fertility coordinator supporting patients throughout their stay.', 'Ovarian stimulation & monitoring\nEgg retrieval & ICSI\nEmbryo transfer\nHotel (10 nights)\nDedicated coordinator', 'International flights\nGenetic screening (PGT-A)\nTravel insurance\nMedications beyond the standard protocol', 'package-ivf.jpg', 5600.00, '2-3 weeks', 0);

-- ---------------------------------------------------------------------
-- Blog posts
-- ---------------------------------------------------------------------
CREATE TABLE blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    excerpt VARCHAR(300) NULL,
    content LONGTEXT NULL,
    image VARCHAR(255) NULL,
    author VARCHAR(100) NULL,
    status ENUM('published','draft') NOT NULL DEFAULT 'published',
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO blog_posts (title, slug, excerpt, content, image, author, published_at) VALUES
('How to Choose the Right Hospital Abroad', 'how-to-choose-the-right-hospital-abroad', 'Accreditation, surgeon experience and patient reviews are just the start. Here is our complete checklist.', '<p>Choosing a hospital abroad is one of the most important decisions in your medical travel journey. Look for international accreditation such as JCI, check surgeon credentials and read verified patient reviews before booking.</p><p>Our team personally vets every partner hospital on Let''s Go Medical against these criteria so you can book with confidence.</p>', 'blog-1.jpg', 'Let''s Go Medical Editorial Team', '2026-06-02 09:00:00'),
('5 Questions to Ask Before Your Surgery Abroad', '5-questions-to-ask-before-your-surgery-abroad', 'From aftercare to insurance, these are the questions our patient coordinators recommend asking first.', '<p>1. What happens if I need follow-up care after returning home?<br>2. Is travel insurance with medical coverage included?<br>3. What is the surgeon''s complication rate?<br>4. Are all costs included in the package price?<br>5. Who will be my point of contact during recovery?</p>', 'blog-2.jpg', 'Dr. Amara Osei', '2026-06-18 09:00:00'),
('Recovering Well: Tips for Medical Travel Aftercare', 'recovering-well-tips-for-medical-travel-aftercare', 'Recovery does not stop at the airport. Here is how to look after yourself once you are home.', '<p>Plan for rest, follow your surgeon''s aftercare instructions closely, and keep in touch with your care coordinator. Most complications can be avoided by following a structured recovery plan.</p>', 'blog-3.jpg', 'Let''s Go Medical Editorial Team', '2026-07-01 09:00:00');

-- ---------------------------------------------------------------------
-- Testimonials
-- ---------------------------------------------------------------------
CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_name VARCHAR(120) NOT NULL,
    patient_country VARCHAR(80) NULL,
    treatment_id INT NULL,
    content TEXT NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    photo VARCHAR(255) NULL,
    status ENUM('published','draft') NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_testimonial_treatment FOREIGN KEY (treatment_id) REFERENCES treatments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO testimonials (patient_name, patient_country, treatment_id, content, rating, photo) VALUES
('Sarah Thompson', 'United Kingdom', 2, 'From the first phone call to my follow-up appointment, the whole process was seamless. My hair transplant results exceeded my expectations.', 5, 'testimonial-1.jpg'),
('Michael Brooks', 'United States', 1, 'I saved thousands of dollars on my dental implants without compromising on quality. The clinic was modern and the staff spoke perfect English.', 5, 'testimonial-2.jpg'),
('Fatima Al-Sayed', 'UAE', 5, 'The cardiac team in Delhi gave my father a second chance at life. We are forever grateful for the care and attention we received.', 5, 'testimonial-3.jpg'),
('Jean Dupont', 'Canada', 7, 'My bariatric surgery in Cancun was handled professionally from start to finish, and the recovery hotel made all the difference.', 4, 'testimonial-4.jpg');

-- ---------------------------------------------------------------------
-- Leads / inquiries (contact + free-quote form submissions)
-- ---------------------------------------------------------------------
CREATE TABLE leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NULL,
    country VARCHAR(100) NULL,
    treatment_id INT NULL,
    package_id INT NULL,
    message TEXT NULL,
    source VARCHAR(50) NOT NULL DEFAULT 'contact_form',
    status ENUM('new','contacted','converted','closed') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lead_treatment FOREIGN KEY (treatment_id) REFERENCES treatments(id) ON DELETE SET NULL,
    CONSTRAINT fk_lead_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO leads (full_name, email, phone, country, treatment_id, message, source, status) VALUES
('Anna Kowalski', 'anna.k@example.com', '+48 600 111 222', 'Poland', 2, 'Hello, I would like a quote for a hair transplant in Istanbul for late August.', 'quote_form', 'new'),
('David Owusu', 'david.owusu@example.com', '+233 24 555 0199', 'Ghana', 5, 'Looking for information on cardiac bypass surgery packages for my father.', 'contact_form', 'contacted');

-- ---------------------------------------------------------------------
-- Partners (shown as a logo strip on the About page)
-- ---------------------------------------------------------------------
CREATE TABLE partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    logo VARCHAR(255) NULL,
    website_url VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('published','draft') NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Site settings (key/value store)
-- ---------------------------------------------------------------------
CREATE TABLE settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT NULL
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', "Let's Go Medical"),
('site_tagline', 'Your trusted partner in medical travel'),
('site_phone', '+1 (800) 555-0134'),
('site_whatsapp', '+1 800 555 0134'),
('site_email', 'care@letsgomedical.test'),
('site_address', '221 Global Health Plaza, Suite 400, Miami, FL, USA'),
('facebook_url', 'https://facebook.com/'),
('instagram_url', 'https://instagram.com/'),
('youtube_url', 'https://youtube.com/'),
('site_logo', ''),
('nav_cta_text', 'Get Free Quote'),
('hero_badge_text', 'Trusted by 12,000+ patients worldwide'),
('hero_heading', 'Quality healthcare abroad, without the guesswork.'),
('hero_subtext', 'Compare accredited hospitals, specialist doctors and all-inclusive treatment packages. Get a free personalized quote in under 24 hours.'),
('hero_primary_btn_text', 'Get My Free Quote'),
('hero_secondary_btn_text', 'Browse Treatments'),
('about_badge_text', 'Our Story'),
('about_heading', 'Making Global Healthcare Simple & Transparent'),
('about_content', "Let's Go Medical connects patients from around the world with internationally accredited hospitals and specialist doctors, offering transparent pricing and dedicated patient coordinators from your first enquiry through to recovery at home."),
('about_content_2', 'We partner exclusively with internationally accredited hospitals and experienced specialists, and every quote we provide includes clear, upfront pricing so there are no surprises when you travel.'),
('about_stat_number', '12k+'),
('about_stat_label', 'Patients Helped'),
('mission_text', 'To connect every patient with safe, affordable, world-class healthcare, wherever they are in the world.'),
('vision_text', 'A world where distance and cost are never barriers to receiving excellent medical care.'),
('values_text', 'Transparency, patient safety and genuine care guide every recommendation we make.'),
('treatments_banner', ''),
('destinations_banner', ''),
('packages_banner', ''),
('blog_banner', ''),
('about_banner', ''),
('contact_banner', '');

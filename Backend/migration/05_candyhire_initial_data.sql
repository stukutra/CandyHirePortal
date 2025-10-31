-- ============================================
-- CandyHire Initial Data Insert - Multi-Tenant
-- MySQL 8.0+
-- All demo data uses tenant_id = 'demo-tenant'
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- Insert Demo Roles
-- ============================================
INSERT INTO `roles` (`id`, `tenant_id`, `name`, `description`, `permissions`) VALUES
('role-admin', 'demo-tenant', 'admin', 'Company Administrator - Full access', '{"all": true}'),
('role-recruiter', 'demo-tenant', 'recruiter', 'Recruiter - Manage jobs and candidates', '{"jobs": "write", "candidates": "write", "interviews": "write"}'),
('role-hr-manager', 'demo-tenant', 'hr_manager', 'HR Manager - View reports and analytics', '{"jobs": "read", "candidates": "read", "analytics": "read"}');

-- ============================================
-- Insert Demo System User (for testing)
-- Password: Demo123! (bcrypt hash)
-- ============================================
INSERT INTO `system_users` (`id`, `tenant_id`, `email`, `password_hash`, `first_name`, `last_name`, `username`, `role_id`, `is_active`) VALUES
('demo-user-001', 'demo-tenant', 'demo@candyhire.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'User', 'demo', 'role-admin', TRUE);

-- ============================================
-- Insert Companies
-- ============================================
INSERT INTO `companies` (`id`, `tenant_id`, `name`, `logo`, `industry`, `website`, `email`, `phone`, `address`, `city`, `country`, `employees_count`, `founded_year`, `description`, `type`) VALUES
('C1', 'demo-tenant', 'TechVision Inc', 'https://ui-avatars.com/api/?name=TechVision+Inc&background=f7c7d9&color=fff&size=150', 'Information Technology', 'https://techvision.example.com', 'contact@techvision.example.com', '+1 (555) 123-4567', '123 Tech Street', 'San Francisco', 'USA', '500-1000', 2015, 'Leading provider of enterprise software solutions and cloud services', 'Client'),
('C2', 'demo-tenant', 'Global Finance Corp', 'https://ui-avatars.com/api/?name=Global+Finance&background=dbb5e8&color=fff&size=150', 'Financial Services', 'https://globalfinance.example.com', 'hr@globalfinance.example.com', '+1 (555) 234-5678', '456 Wall Street', 'New York', 'USA', '1000-5000', 2008, 'International banking and investment services provider', 'Client'),
('C3', 'demo-tenant', 'HealthCare Plus', 'https://ui-avatars.com/api/?name=HealthCare+Plus&background=cfe9df&color=fff&size=150', 'Healthcare', 'https://healthcareplus.example.com', 'info@healthcareplus.example.com', '+1 (555) 345-6789', '789 Medical Center Drive', 'Boston', 'USA', '100-500', 2012, 'Innovative healthcare technology and patient care solutions', 'Client'),
('C4', 'demo-tenant', 'EcoEnergy Solutions', 'https://ui-avatars.com/api/?name=EcoEnergy&background=f7c7d9&color=fff&size=150', 'Renewable Energy', 'https://ecoenergy.example.com', 'contact@ecoenergy.example.com', '+44 20 1234 5678', '10 Green Park', 'London', 'UK', '200-500', 2018, 'Sustainable energy solutions for residential and commercial clients', 'Supplier'),
('C5', 'demo-tenant', 'Digital Marketing Pro', 'https://ui-avatars.com/api/?name=Digital+Marketing&background=dbb5e8&color=fff&size=150', 'Marketing & Advertising', 'https://digitalmarketingpro.example.com', 'hello@digitalmarketingpro.example.com', '+1 (555) 456-7890', '321 Creative Avenue', 'Los Angeles', 'USA', '50-100', 2016, 'Full-service digital marketing agency specializing in social media and SEO', 'Supplier'),
('C6', 'demo-tenant', 'BuildRight Construction', 'https://ui-avatars.com/api/?name=BuildRight&background=cfe9df&color=fff&size=150', 'Construction', 'https://buildright.example.com', 'projects@buildright.example.com', '+1 (555) 567-8901', '555 Builder\'s Lane', 'Chicago', 'USA', '500-1000', 2005, 'Commercial and residential construction with focus on sustainable building', 'Client'),
('C7', 'demo-tenant', 'FoodieDelight Restaurants', 'https://ui-avatars.com/api/?name=FoodieDelight&background=f7c7d9&color=fff&size=150', 'Food & Beverage', 'https://foodiedelight.example.com', 'careers@foodiedelight.example.com', '+1 (555) 678-9012', '888 Culinary Boulevard', 'Miami', 'USA', '1000-5000', 2010, 'Chain of gourmet restaurants and catering services', 'Supplier'),
('C8', 'demo-tenant', 'EduTech Academy', 'https://ui-avatars.com/api/?name=EduTech+Academy&background=dbb5e8&color=fff&size=150', 'Education Technology', 'https://edutech.example.com', 'admissions@edutech.example.com', '+1 (555) 789-0123', '777 Learning Way', 'Austin', 'USA', '100-500', 2017, 'Online learning platform with courses in technology and business', 'Supplier'),
('C9', 'demo-tenant', 'Fashion Forward Ltd', 'https://ui-avatars.com/api/?name=Fashion+Forward&background=cfe9df&color=fff&size=150', 'Fashion & Retail', 'https://fashionforward.example.com', 'info@fashionforward.example.com', '+33 1 23 45 67 89', '25 Rue de la Mode', 'Paris', 'France', '500-1000', 2013, 'Contemporary fashion brand with sustainable and ethical practices', 'Client'),
('C10', 'demo-tenant', 'AutoDrive Motors', 'https://ui-avatars.com/api/?name=AutoDrive+Motors&background=f7c7d9&color=fff&size=150', 'Automotive', 'https://autodrive.example.com', 'sales@autodrive.example.com', '+49 89 1234 5678', '50 Motor Strasse', 'Munich', 'Germany', '5000+', 2000, 'Manufacturer of electric and autonomous vehicles', 'Client'),
('C11', 'demo-tenant', 'CloudNet Services', 'https://ui-avatars.com/api/?name=CloudNet&background=dbb5e8&color=fff&size=150', 'Cloud Computing', 'https://cloudnet.example.com', 'support@cloudnet.example.com', '+1 (555) 890-1234', '999 Cloud Avenue', 'Seattle', 'USA', '200-500', 2019, 'Cloud infrastructure and managed services provider', 'Both'),
('C12', 'demo-tenant', 'BioPharm Research', 'https://ui-avatars.com/api/?name=BioPharm&background=cfe9df&color=fff&size=150', 'Biotechnology', 'https://biopharm.example.com', 'research@biopharm.example.com', '+41 22 123 4567', '15 Research Park', 'Geneva', 'Switzerland', '100-500', 2011, 'Biopharmaceutical research and drug development company', 'Client');

-- ============================================
-- Insert Recruiters
-- ============================================
INSERT INTO `recruiters` (`id`, `tenant_id`, `first_name`, `last_name`, `email`, `avatar`, `role`) VALUES
('R1', 'demo-tenant', 'Emma', 'Wilson', 'emma.wilson@candyhire.com', 'https://i.pravatar.cc/150?img=1', 'Senior Technical Recruiter'),
('R2', 'demo-tenant', 'James', 'Martinez', 'james.martinez@candyhire.com', 'https://i.pravatar.cc/150?img=12', 'Technical Recruiter'),
('R3', 'demo-tenant', 'Sophia', 'Chen', 'sophia.chen@candyhire.com', 'https://i.pravatar.cc/150?img=5', 'Design Recruiter'),
('R4', 'demo-tenant', 'Michael', 'Johnson', 'michael.johnson@candyhire.com', 'https://i.pravatar.cc/150?img=13', 'Executive Recruiter'),
('R5', 'demo-tenant', 'Olivia', 'Taylor', 'olivia.taylor@candyhire.com', 'https://i.pravatar.cc/150?img=9', 'Marketing Recruiter'),
('R6', 'demo-tenant', 'David', 'Brown', 'david.brown@candyhire.com', 'https://i.pravatar.cc/150?img=14', 'Engineering Recruiter'),
('R7', 'demo-tenant', 'Rachel', 'Anderson', 'rachel.anderson@candyhire.com', 'https://i.pravatar.cc/150?img=7', 'Sales Recruiter'),
('R8', 'demo-tenant', 'Christopher', 'Lee', 'christopher.lee@candyhire.com', 'https://i.pravatar.cc/150?img=20', 'Data Science Recruiter'),
('R9', 'demo-tenant', 'Isabella', 'Garcia', 'isabella.garcia@candyhire.com', 'https://i.pravatar.cc/150?img=10', 'Product Recruiter'),
('R10', 'demo-tenant', 'Daniel', 'Rodriguez', 'daniel.rodriguez@candyhire.com', 'https://i.pravatar.cc/150?img=22', 'DevOps Recruiter'),
('R11', 'demo-tenant', 'Natalie', 'Martinez', 'natalie.martinez@candyhire.com', 'https://i.pravatar.cc/150?img=11', 'HR Recruiter'),
('R12', 'demo-tenant', 'Anthony', 'White', 'anthony.white@candyhire.com', 'https://i.pravatar.cc/150?img=23', 'Quality Assurance Recruiter');

-- ============================================
-- Insert Jobs
-- ============================================
INSERT INTO `jobs` (`id`, `tenant_id`, `title`, `department`, `location`, `location_type`, `job_type`, `applicants_count`, `status`, `description`, `requirements`, `salary_range`, `posted_date`, `start_date`, `end_date`, `company_id`) VALUES
('1', 'demo-tenant', 'Senior Frontend Developer', 'Engineering', 'San Francisco, CA', 'Hybrid', 'Full-time', 45, 'Open', 'We are looking for an experienced Frontend Developer to join our engineering team.', '5+ years of experience with React, TypeScript, and modern web technologies', '$120,000 - $150,000', '2025-10-15', '2025-10-15', '2025-11-30', 'C1'),
('2', 'demo-tenant', 'UX Designer', 'Design', 'Remote', 'Remote', 'Full-time', 32, 'Open', 'Join our design team to create beautiful and intuitive user experiences.', '3+ years of UX design experience, proficiency in Figma', '$90,000 - $120,000', '2025-10-18', '2025-10-18', '2025-12-15', 'C10'),
('3', 'demo-tenant', 'Product Manager', 'Product', 'New York, NY', 'On-site', 'Full-time', 28, 'Open', 'Lead product strategy and execution for our core platform.', '5+ years of product management experience in B2B SaaS', '$130,000 - $160,000', '2025-10-10', '2025-10-10', '2025-10-25', 'C9'),
('4', 'demo-tenant', 'DevOps Engineer', 'Engineering', 'Austin, TX', 'Hybrid', 'Full-time', 18, 'Open', 'Build and maintain our cloud infrastructure and CI/CD pipelines.', '4+ years with AWS, Docker, Kubernetes, and Terraform', '$110,000 - $140,000', '2025-10-12', '2025-10-12', '2025-11-20', 'C6'),
('5', 'demo-tenant', 'Marketing Manager', 'Marketing', 'Los Angeles, CA', 'Hybrid', 'Full-time', 22, 'Closed', 'Drive marketing strategy and campaigns to grow our brand.', '3+ years of B2B marketing experience', '$85,000 - $110,000', '2025-09-28', '2025-09-28', '2025-10-30', 'C5'),
('6', 'demo-tenant', 'Backend Developer', 'Engineering', 'Remote', 'Remote', 'Full-time', 35, 'Open', 'Develop scalable backend services and APIs.', '3+ years with Node.js, Python, or Java', '$100,000 - $130,000', '2025-10-14', '2025-10-14', '2025-11-28', 'C5'),
('7', 'demo-tenant', 'Data Analyst', 'Analytics', 'Seattle, WA', 'Hybrid', 'Full-time', 25, 'Open', 'Analyze data to drive business insights and decisions.', '2+ years of experience with SQL, Python, and BI tools', '$75,000 - $95,000', '2025-10-16', '2025-10-16', '2025-11-25', 'C11'),
('8', 'demo-tenant', 'Sales Executive', 'Sales', 'New York, NY', 'On-site', 'Full-time', 20, 'Open', 'Drive revenue growth through new client acquisition.', '3+ years of B2B sales experience', '$80,000 - $120,000 + commission', '2025-10-11', '2025-10-11', '2025-11-15', 'C2'),
('9', 'demo-tenant', 'QA Engineer', 'Quality Assurance', 'Chicago, IL', 'Hybrid', 'Full-time', 15, 'Open', 'Ensure software quality through comprehensive testing.', '2+ years of QA automation experience', '$70,000 - $90,000', '2025-10-13', '2025-10-13', '2025-11-22', 'C6'),
('10', 'demo-tenant', 'UI Designer', 'Design', 'Seattle, WA', 'Remote', 'Full-time', 28, 'Open', 'Create beautiful and functional user interfaces.', '3+ years of UI design experience, Figma expertise', '$85,000 - $110,000', '2025-10-17', '2025-10-17', '2025-12-01', 'C11');

-- ============================================
-- Insert Job-Recruiter Relationships
-- ============================================
INSERT INTO `job_recruiters` (`job_id`, `recruiter_id`) VALUES
-- Job 1: Senior Frontend Developer
('1', 'R1'), ('1', 'R2'),
-- Job 2: UX Designer
('2', 'R5'),
-- Job 3: Product Manager
('3', 'R6'), ('3', 'R4'), ('3', 'R1'),
-- Job 4: DevOps Engineer
('4', 'R3'), ('4', 'R1'), ('4', 'R4'),
-- Job 5: Marketing Manager
('5', 'R1'), ('5', 'R2'), ('5', 'R6'),
-- Job 6: Backend Developer
('6', 'R3'), ('6', 'R6'),
-- Job 7: Data Analyst
('7', 'R6'), ('7', 'R4'), ('7', 'R8'),
-- Job 8: Sales Executive
('8', 'R4'), ('8', 'R5'), ('8', 'R7'),
-- Job 9: QA Engineer
('9', 'R2'), ('9', 'R10'), ('9', 'R12'),
-- Job 10: UI Designer
('10', 'R3'), ('10', 'R2');

-- ============================================
-- Insert Referents
-- ============================================
INSERT INTO `referents` (`id`, `tenant_id`, `company_id`, `first_name`, `last_name`, `role`, `email`, `phone`) VALUES
('REF001', 'demo-tenant', 'C1', 'John', 'Mitchell', 'HR Director', 'john.mitchell@techvision.example.com', '+1 (555) 123-4501'),
('REF002', 'demo-tenant', 'C1', 'Sarah', 'Chen', 'Talent Acquisition', 'sarah.chen@techvision.example.com', '+1 (555) 123-4502'),
('REF003', 'demo-tenant', 'C2', 'Michael', 'Rodriguez', 'HR Manager', 'michael.rodriguez@globalfinance.example.com', '+1 (555) 234-5601'),
('REF004', 'demo-tenant', 'C2', 'Emily', 'Thompson', 'Recruiting Manager', 'emily.thompson@globalfinance.example.com', '+1 (555) 234-5602'),
('REF005', 'demo-tenant', 'C2', 'David', 'Park', 'Department Head', 'david.park@globalfinance.example.com', '+1 (555) 234-5603'),
('REF006', 'demo-tenant', 'C3', 'Lisa', 'Anderson', 'HR Director', 'lisa.anderson@healthcareplus.example.com', '+1 (555) 345-6701'),
('REF007', 'demo-tenant', 'C4', 'Robert', 'Williams', 'Account Manager', 'robert.williams@ecoenergy.example.com', '+1 (555) 456-7801'),
('REF008', 'demo-tenant', 'C4', 'Jennifer', 'Garcia', 'Sales Manager', 'jennifer.garcia@ecoenergy.example.com', '+1 (555) 456-7802'),
('REF009', 'demo-tenant', 'C5', 'James', 'Martinez', 'Business Partner', 'james.martinez@digitalmarketing.example.com', '+1 (555) 567-8901'),
('REF010', 'demo-tenant', 'C5', 'Amanda', 'Lee', 'Talent Acquisition', 'amanda.lee@digitalmarketing.example.com', '+1 (555) 567-8902'),
('REF011', 'demo-tenant', 'C6', 'Christopher', 'Brown', 'HR Manager', 'christopher.brown@buildright.example.com', '+1 (555) 678-9001'),
('REF012', 'demo-tenant', 'C7', 'Jessica', 'Wilson', 'Account Manager', 'jessica.wilson@foodiedelight.example.com', '+1 (555) 789-0101'),
('REF013', 'demo-tenant', 'C7', 'Daniel', 'Taylor', 'Operations Manager', 'daniel.taylor@foodiedelight.example.com', '+1 (555) 789-0102'),
('REF014', 'demo-tenant', 'C8', 'Michelle', 'Davis', 'HR Director', 'michelle.davis@edutech.example.com', '+1 (555) 890-1201'),
('REF015', 'demo-tenant', 'C8', 'Kevin', 'Thomas', 'Talent Acquisition', 'kevin.thomas@edutech.example.com', '+1 (555) 890-1202'),
('REF016', 'demo-tenant', 'C9', 'Patricia', 'Moore', 'Recruiting Manager', 'patricia.moore@fashionforward.example.com', '+33 1 23 45 67 01'),
('REF017', 'demo-tenant', 'C9', 'Mark', 'Jackson', 'Account Manager', 'mark.jackson@fashionforward.example.com', '+33 1 23 45 67 02'),
('REF018', 'demo-tenant', 'C10', 'Laura', 'White', 'HR Manager', 'laura.white@autodrive.example.com', '+49 89 1234 5601'),
('REF019', 'demo-tenant', 'C10', 'Steven', 'Harris', 'Talent Acquisition', 'steven.harris@autodrive.example.com', '+49 89 1234 5602'),
('REF020', 'demo-tenant', 'C11', 'Nancy', 'Martin', 'HR Director', 'nancy.martin@cloudnet.example.com', '+1 (555) 890-1301'),
('REF021', 'demo-tenant', 'C11', 'Brian', 'Thompson', 'Business Partner', 'brian.thompson@cloudnet.example.com', '+1 (555) 890-1302'),
('REF022', 'demo-tenant', 'C12', 'Sandra', 'Garcia', 'Recruiting Manager', 'sandra.garcia@biopharm.example.com', '+41 22 123 4501'),
('REF023', 'demo-tenant', 'C12', 'Paul', 'Martinez', 'Account Manager', 'paul.martinez@biopharm.example.com', '+41 22 123 4502');

-- ============================================
-- Insert Document Types (common for all tenants)
-- ============================================
INSERT INTO `document_types` (`id`, `tenant_id`, `name`, `entity_type`, `description`, `icon`, `accepted_formats`, `max_size_mb`, `is_required`) VALUES
('DOCTYPE001', 'demo-tenant', 'Resume/CV', 'candidate', 'Candidate curriculum vitae or resume', 'bi-file-earmark-text', '["application/pdf", "application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document"]', 10, TRUE),
('DOCTYPE002', 'demo-tenant', 'Cover Letter', 'candidate', 'Candidate cover letter', 'bi-file-earmark-text', '["application/pdf", "application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document"]', 5, FALSE),
('DOCTYPE003', 'demo-tenant', 'Portfolio', 'candidate', 'Design or work portfolio', 'bi-folder2-open', '["application/pdf", "application/zip"]', 20, FALSE),
('DOCTYPE004', 'demo-tenant', 'Certificates', 'candidate', 'Professional certificates', 'bi-award', '["application/pdf", "image/jpeg", "image/png"]', 5, FALSE),
('DOCTYPE005', 'demo-tenant', 'ID Document', 'candidate', 'Identity document', 'bi-person-badge', '["application/pdf", "image/jpeg", "image/png"]', 5, FALSE);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- End of Multi-Tenant Initial Data
-- ============================================

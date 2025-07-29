# Goba Hospital Patient Record Management System

A comprehensive web-based patient record management system designed for Goba Hospital, featuring multiple user portals, secure authentication, and efficient medical data management.

## Features

### User Portals
- **Admin Portal**: Complete system management, user registration, and hospital records
- **Doctor Portal**: Patient consultation, surgery, and diagnosis records management
- **Patient Portal**: Personal medical history viewing and search functionality
- **Staff Portal**: Medical administration and dosage records
- **External Health Office Portal**: Patient information upload and transfer

### Core Functionality
- Secure user authentication and authorization
- Patient medical records management (consultation, surgery, diagnosis)
- Advanced search capabilities with multiple filters
- Payment processing integration (Commercial Bank, Awash Bank, Abyssinia Bank, Telebir)
- Audio recording for consultations
- File upload support (PDF, JPG) for patient information
- Responsive design for all devices

### Database Features
- Patient information management with SSN/NID/Passport/Birth Certificate
- Doctor and staff profiles with hospital affiliations
- Comprehensive medical records tracking
- Audit trails and data integrity

## Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript, jQuery
- **Backend**: PHP 8.0+
- **Database**: MySQL 8.0+
- **Additional**: Bootstrap 5, Font Awesome

## Installation

1. Clone the repository
2. Set up a web server (Apache/Nginx) with PHP support
3. Create a MySQL database and import the schema
4. Configure database connection in `config/database.php`
5. Set up file permissions for uploads directory
6. Access the application through your web browser

## Database Schema

The system includes the following main tables:
- `patients` - Patient information and profiles
- `doctors` - Doctor information and hospital affiliations
- `medical_staff` - Staff information and roles
- `hospitals` - Hospital information
- `consultations` - Consultation records with audio support
- `operations` - Surgery and operation records
- `diagnoses` - Diagnosis records
- `medical_administrations` - Staff-administered treatments
- `payments` - Payment transaction records
- User authentication tables for each portal

## Security Features
- Password hashing and encryption
- Session management
- Input validation and sanitization
- SQL injection prevention
- XSS protection

## License
This project is developed for Goba Hospital and is proprietary software.

## Support
For technical support or questions, please contact the development team.
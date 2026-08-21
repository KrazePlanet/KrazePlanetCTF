# Tour Registration Management System

## Overview
The **Tour Registration Management System** is a web-based application designed to simplify the process of managing tour registrations and customer information. This system provides a user-friendly interface, efficient data management, and robust form validation to enhance usability and data accuracy.

## Features
- **User-Friendly Interface**: Clean and intuitive design using HTML and CSS.
- **Backend Processing**: Powered by PHP for dynamic data handling.
- **Data Storage**: MySQL database integration for efficient storage and management of customer and tour data.
- **Form Validation**: Comprehensive validation to prevent errors and ensure data accuracy.
- **Data Reporting**: Easy retrieval and reporting of stored information for administrative purposes.

## Technologies Used
- **Frontend**: HTML, CSS
- **Backend**: PHP
- **Database**: MySQL

# Authors
- [Abdullah Shishir](https://github.com/shishir786)

## Prerequisites
To run this project locally, ensure you have the following installed:
- [XAMPP](https://www.apachefriends.org/index.html) or any other local server environment
- Web browser (Google Chrome, Mozilla Firefox, etc.)
- Code editor (VS Code, Sublime Text, etc.)

## Installation and Setup
1. **Clone the Repository**  
   ```bash
   git clone https://github.com/shishir786/tour_form.git
   cd tour-registration-system
   ```

2. **Start Local Server**  
   - Launch XAMPP or your preferred local server.
   - Start the Apache and MySQL services.

3. **Database Setup**  
   - Open phpMyAdmin at `http://localhost/phpmyadmin/`.
   - Create a database named `tour_management`.
   - Import the provided SQL file:
     - Navigate to the `tour-registration-system/database` folder.
     - Upload `tour_management.sql` to phpMyAdmin.

4. **Configure Database Connection**  
   - Open the `config.php` file in the root folder.
   - Update the database credentials as required:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASSWORD', '');
     define('DB_NAME', 'tour_management');
     ```

5. **Run the Application**  
   - Place the project folder in the `htdocs` directory of XAMPP.
   - Open a web browser and navigate to:
     ```
     http://localhost/tour-registration-system/
     ```

## Usage

1. **Registration Form**  
   - Users can fill out and submit their details to register for a tour.
2. **Admin Panel**  
   - Admins can view, edit, and delete customer registrations details (requires authentication).



## Future Enhancements
- Add user authentication for customers.
- Integrate payment gateway for online tour bookings.
- Implement a notification system for booking confirmations.

## Contributing
Contributions are welcome! Please follow these steps:  
1. Fork the repository.  
2. Create a new branch for your feature (`git checkout -b feature-name`).  
3. Commit your changes (`git commit -m "Add feature"`).  
4. Push to the branch (`git push origin feature-name`).  
5. Open a pull request.  

## License
This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Screenshort
![382595167-5d112005-26cc-4442-8d3d-27241e6b4eaa](https://github.com/user-attachments/assets/6c501c85-6c35-46de-bdf6-d91c812837e7)

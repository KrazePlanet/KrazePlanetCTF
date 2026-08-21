# Document Management Portal

Welcome to the Document Management Portal! This portal is designed to efficiently manage documents while prioritizing security. With sector-wise organization and privilege-based options, users can seamlessly upload, edit, and view documents while ensuring data integrity and confidentiality.

<div align="center">
  <img src="./assets/sample.gif" alt="Sample GIF" width="500">
</div>

## Features

- **Sector-wise Document Management**: Documents can be uploaded and organized based on different sectors or categories, facilitating easy access and navigation.
- **Privilege-based Options**: Users are assigned privilege levels, allowing them to perform specific actions such as editing documents, managing profiles, and more, based on their roles.
- **Document Editing**: Users can edit documents directly within the portal, enabling collaboration and real-time updates.
- **User Profile Management**: Each user has a profile where they can manage their personal information and settings.
- **Secure PDF Viewer**: The portal includes a PDF viewer with built-in security measures to prevent screenshots. Usernames are watermarked on the PDF screen to enhance accountability.
- **Document Security**: Documents cannot be downloaded from the portal, ensuring that sensitive information remains within the system and reducing the risk of unauthorized access.

## Getting Started

To use this application, follow these steps:

1. **Clone the Repository**: Clone the repository using the following command:
   ```
   git clone https://github.com/Theternos/Document-management-portal.git
   ```

2. **Move Inside the Directory**: Change your current directory to the cloned repository using the `cd` command:
   ```
   cd Document-management-portal
   ```

3. **Setup Database**: Import the database schema from `./database/database.sql` to your MySQL database management system.

4. **Configure Database Credentials**: Update the `./config.php` file with your database credentials to establish a connection.

5. **Configure SMTP Settings**: In `./python/password_reset.py`, change line 35 to provide your SMTP login credentials for sending password reset emails.

6. **Dependencies**: Ensure all necessary dependency files are located in the `./assets` directory.

7. **Uploaded Documents**: All uploaded documents will be stored in the `./uploads` directory.

Once you have completed these steps, you can access the Document Management Portal through your web browser and start managing documents securely and efficiently.

For any further assistance or inquiries, please reach out to [kavin.apm2003@gmail.com](mailto:kavin.apm2003@gmail.com).

---

Feel free to customize and extend the functionality of this portal according to your specific requirements. If you encounter any issues or have suggestions for improvement, we welcome your feedback to enhance the user experience further.

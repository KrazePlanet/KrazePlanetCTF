<?php
require_once 'config.php';
requireAdmin();
$page_title = "Add Student";

$error   = "";
$success = "";

// Get courses
$courses = mysqli_query($conn, "SELECT * FROM courses ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $course   = sanitize($_POST['course'] ?? '');
    $address  = sanitize($_POST['address'] ?? '');
    $status   = in_array($_POST['status'], ['active','inactive','graduated']) ? $_POST['status'] : 'active';
    $dob      = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $gender   = in_array($_POST['gender'], ['male','female','other']) ? $_POST['gender'] : null;

    if (empty($name) || empty($email) || empty($course)) {
        $error = "Name, email and course are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check email unique
        $check = mysqli_prepare($conn, "SELECT id FROM students WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Email already registered.";
        } else {
            // Generate Student ID
            $student_id = generateStudentID($conn);

            // Handle image upload
            $image = null;
            if (!empty($_FILES['profile_image']['name'])) {
                $ext  = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                    $error = "Only JPG, PNG, GIF, WEBP allowed.";
                } elseif ($_FILES['profile_image']['size'] > MAX_FILE_SIZE) {
                    $error = "Image must be less than 2MB.";
                } else {
                    $image = uniqid() . '_' . time() . '.' . $ext;
                    if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], UPLOAD_PATH . $image)) {
                        $error = "Failed to upload image.";
                        $image = null;
                    }
                }
            }

            if (empty($error)) {
                $stmt = mysqli_prepare($conn, "INSERT INTO students (student_id, name, email, phone, course, address, profile_image, status, date_of_birth, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssssssssss", $student_id, $name, $email, $phone, $course, $address, $image, $status, $dob, $gender);

                if (mysqli_stmt_execute($stmt)) {
                    logActivity($conn, "Added new student: $name (ID: $student_id)");
                    redirect('students.php?success=added');
                } else {
                    $error = "Something went wrong. Please try again.";
                }
            }
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>➕ Add New Student</h1>
            <p>Fill in the student details below</p>
        </div>
        <div class="topbar-right">
            <a href="students.php" class="btn btn-secondary">← Back to Students</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:24px;" class="responsive-grid">

        <!-- Preview Card -->
        <div class="card" style="text-align:center;">
            <div class="card-header">
                <h2>🖼️ Profile Picture</h2>
            </div>
            <div style="padding:20px 0;">
                <div id="avatarPlaceholder" style="width:100px; height:100px; border-radius:50%; background:var(--primary); display:flex; align-items:center; justify-content:center; color:white; font-size:36px; font-weight:700; margin:0 auto 15px;">
                    👨‍🎓
                </div>
                <p style="color:var(--gray); font-size:13px;">Student ID will be auto-generated</p>
            </div>
        </div>

        <!-- Form -->
        <div class="card">
            <div class="card-header">
                <h2>📝 Student Details</h2>
            </div>
            <form method="POST" action="add.php" enctype="multipart/form-data">

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control"
                            placeholder="Enter full name"
                            value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control"
                            placeholder="Enter email"
                            value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control"
                            placeholder="e.g. 0300-1234567"
                            value="<?php echo isset($_POST['phone']) ? sanitize($_POST['phone']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Course *</label>
                        <select name="course" class="form-control" required>
                            <option value="">Select Course</option>
                            <?php while ($c = mysqli_fetch_assoc($courses)): ?>
                                <option value="<?php echo htmlspecialchars($c['name']); ?>"
                                    <?php echo (isset($_POST['course']) && $_POST['course'] == $c['name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control"
                            value="<?php echo isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="male"   <?php echo (isset($_POST['gender']) && $_POST['gender']=='male')   ? 'selected':''; ?>>Male</option>
                            <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender']=='female') ? 'selected':''; ?>>Female</option>
                            <option value="other"  <?php echo (isset($_POST['gender']) && $_POST['gender']=='other')  ? 'selected':''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="active"    <?php echo (isset($_POST['status']) && $_POST['status']=='active')    ? 'selected':''; ?>>Active</option>
                            <option value="inactive"  <?php echo (isset($_POST['status']) && $_POST['status']=='inactive')  ? 'selected':''; ?>>Inactive</option>
                            <option value="graduated" <?php echo (isset($_POST['status']) && $_POST['status']=='graduated') ? 'selected':''; ?>>Graduated</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Profile Picture <span style="color:var(--gray); text-transform:none;">(optional)</span></label>
                        <input type="file" name="profile_image" class="form-control"
                            accept="image/*" onchange="previewImg(this)">
                        <p class="form-hint">JPG, PNG, GIF, WEBP — max 2MB</p>
                    </div>

                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"
                            placeholder="Enter address"><?php echo isset($_POST['address']) ? sanitize($_POST['address']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">➕ Add Student</button>
                    <a href="students.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewImg(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const placeholder = document.getElementById('avatarPlaceholder');
            placeholder.innerHTML = '';
            placeholder.style.background = 'none';
            const img = document.createElement('img');
            img.src   = e.target.result;
            img.style = 'width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid var(--primary);';
            placeholder.appendChild(img);
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
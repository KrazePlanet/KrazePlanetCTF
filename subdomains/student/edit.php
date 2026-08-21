<?php
require_once 'config.php';
requireAdmin();
$page_title = "Edit Student";

$error   = "";
$success = "";

if (!isset($_GET['id']) || empty($_GET['id'])) redirect('students.php');

$id   = (int) $_GET['id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$student) redirect('students.php');

// Get courses
$courses = mysqli_query($conn, "SELECT * FROM courses ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name    = sanitize($_POST['name'] ?? '');
    $email   = sanitize($_POST['email'] ?? '');
    $phone   = sanitize($_POST['phone'] ?? '');
    $course  = sanitize($_POST['course'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $status  = in_array($_POST['status'], ['active','inactive','graduated']) ? $_POST['status'] : 'active';
    $dob     = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $gender  = in_array($_POST['gender'], ['male','female','other']) ? $_POST['gender'] : null;

    if (empty($name) || empty($email) || empty($course)) {
        $error = "Name, email and course are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        // Check email unique
        $check = mysqli_prepare($conn, "SELECT id FROM students WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($check, "si", $email, $id);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Email already used by another student.";
        } else {
            // Handle image
            $image = $student['profile_image'];
            if (!empty($_FILES['profile_image']['name'])) {
                $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                    $error = "Only JPG, PNG, GIF, WEBP allowed.";
                } elseif ($_FILES['profile_image']['size'] > MAX_FILE_SIZE) {
                    $error = "Image must be less than 2MB.";
                } else {
                    $new_image = uniqid() . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], UPLOAD_PATH . $new_image)) {
                        if (!empty($image) && file_exists(UPLOAD_PATH . $image)) unlink(UPLOAD_PATH . $image);
                        $image = $new_image;
                    } else {
                        $error = "Failed to upload image.";
                    }
                }
            }

            if (empty($error)) {
                $stmt = mysqli_prepare($conn, "UPDATE students SET name=?, email=?, phone=?, course=?, address=?, profile_image=?, status=?, date_of_birth=?, gender=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "sssssssssi", $name, $email, $phone, $course, $address, $image, $status, $dob, $gender, $id);

                if (mysqli_stmt_execute($stmt)) {
                    logActivity($conn, "Updated student: $name (ID: {$student['student_id']})");
                    redirect('students.php?success=updated');
                } else {
                    $error = "Something went wrong.";
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
            <h1>✏️ Edit Student</h1>
            <p>Update student information</p>
        </div>
        <div class="topbar-right">
            <a href="students.php" class="btn btn-secondary">← Back</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:24px;" class="responsive-grid">

        <!-- Preview -->
        <div class="card" style="text-align:center;">
            <div class="card-header">
                <h2>👤 Student Profile</h2>
            </div>
            <div style="padding:15px 0;">
                <?php if (!empty($student['profile_image']) && file_exists(UPLOAD_PATH . $student['profile_image'])): ?>
                    <img id="avatarPreview"
                        src="<?php echo UPLOAD_URL . $student['profile_image']; ?>"
                        style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid var(--primary); margin-bottom:15px;">
                <?php else: ?>
                    <div id="avatarPlaceholder" style="width:100px; height:100px; border-radius:50%; background:var(--primary); display:flex; align-items:center; justify-content:center; color:white; font-size:36px; font-weight:700; margin:0 auto 15px;">
                        <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <h3 style="font-size:16px; font-weight:700;"><?php echo htmlspecialchars($student['name']); ?></h3>
                <p style="color:var(--gray); font-size:13px; margin:5px 0;"><?php echo htmlspecialchars($student['email']); ?></p>
                <code style="background:var(--gray-light); padding:3px 10px; border-radius:5px; font-size:12px;">
                    <?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?>
                </code>
                <div style="margin-top:10px;">
                    <span class="badge badge-<?php echo $student['status'] ?? 'active'; ?>">
                        <?php echo ucfirst($student['status'] ?? 'active'); ?>
                    </span>
                </div>
                <p style="color:var(--gray); font-size:12px; margin-top:10px;">
                    Joined: <?php echo date('M d, Y', strtotime($student['created_at'])); ?>
                </p>
            </div>
        </div>

        <!-- Form -->
        <div class="card">
            <div class="card-header">
                <h2>📝 Edit Details</h2>
            </div>
            <form method="POST" action="edit.php?id=<?php echo $id; ?>" enctype="multipart/form-data">

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control"
                            value="<?php echo htmlspecialchars($student['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control"
                            value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control"
                            value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Course *</label>
                        <select name="course" class="form-control" required>
                            <option value="">Select Course</option>
                            <?php while ($c = mysqli_fetch_assoc($courses)): ?>
                                <option value="<?php echo htmlspecialchars($c['name']); ?>"
                                    <?php echo $student['course'] == $c['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control"
                            value="<?php echo htmlspecialchars($student['date_of_birth'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="male"   <?php echo ($student['gender']??'')==='male'   ? 'selected':''; ?>>Male</option>
                            <option value="female" <?php echo ($student['gender']??'')==='female' ? 'selected':''; ?>>Female</option>
                            <option value="other"  <?php echo ($student['gender']??'')==='other'  ? 'selected':''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="active"    <?php echo ($student['status']??'')==='active'    ? 'selected':''; ?>>Active</option>
                            <option value="inactive"  <?php echo ($student['status']??'')==='inactive'  ? 'selected':''; ?>>Inactive</option>
                            <option value="graduated" <?php echo ($student['status']??'')==='graduated' ? 'selected':''; ?>>Graduated</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" name="profile_image" class="form-control"
                            accept="image/*" onchange="previewImg(this)">
                        <p class="form-hint">Leave empty to keep current image</p>
                    </div>

                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"
                            placeholder="Enter address"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
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
            const preview     = document.getElementById('avatarPreview');
            const placeholder = document.getElementById('avatarPlaceholder');
            if (preview) {
                preview.src = e.target.result;
            } else if (placeholder) {
                placeholder.style.display = 'none';
                const img = document.createElement('img');
                img.id    = 'avatarPreview';
                img.src   = e.target.result;
                img.style = 'width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid var(--primary); margin-bottom:15px;';
                placeholder.parentNode.insertBefore(img, placeholder);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
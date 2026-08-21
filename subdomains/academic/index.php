<?php
// University Academic Portal -- Course Catalog (Filename-based SQLi)
// Real: /index.php  or  /index.php?id=N
// SQLi: /PAYLOAD.php?id=1  (filename IS the payload, .php stripped)
// Examples: /'.php?id=1   /".php?id=1   /1=1.php?id=1

$server="localhost";$username="root";$password="";$database="KrazePlanet_DB";
$conn=mysqli_connect($server,$username,$password);if(!$conn){die("DB connection failed");}
mysqli_query($conn,"CREATE DATABASE IF NOT EXISTS $database");mysqli_select_db($conn,$database);

mysqli_query($conn,"CREATE TABLE IF NOT EXISTS lab1608_courses("
    . "id INT AUTO_INCREMENT PRIMARY KEY,"
    . "title VARCHAR(255) NOT NULL,"
    . "artist VARCHAR(255) NOT NULL,"
    . "release_year INT NOT NULL,"
    . "genre VARCHAR(100) NOT NULL,"
    . "track_count INT NOT NULL DEFAULT 0,"
    . "duration_min INT NOT NULL DEFAULT 0,"
    . "label VARCHAR(255) NOT NULL DEFAULT 'University System'"
. ")") ;

$check=mysqli_query($conn,"SELECT COUNT(*) AS cnt FROM lab1608_courses");
$row=mysqli_fetch_assoc($check);
if($row["cnt"]==0){
    $seeds=array(
        array("CS 501: Algorithm Design","Dr. Ada Chen",2024,"Computer Science",45,4,"College of Engineering"),
        array("BIO 230: Molecular Genetics","Dr. James Rivera",2023,"Biology",38,3,"College of Sciences"),
        array("MATH 410: Linear Algebra","Dr. Sarah Park",2024,"Mathematics",52,4,"College of Sciences"),
        array("PHY 301: Quantum Mechanics","Dr. Marcus Webb",2023,"Physics",28,4,"College of Sciences"),
        array("ENG 110: Composition","Prof. Lisa Chang",2024,"English",60,3,"College of Arts & Humanities"),
        array("HIST 201: World History","Dr. David Kim",2023,"History",42,3,"College of Arts & Humanities"),
        array("CHEM 240: Organic Chemistry","Dr. Nina Patel",2024,"Chemistry",32,4,"College of Sciences"),
        array("PSY 101: Intro to Psychology","Dr. Robert Okafor",2024,"Psychology",75,3,"College of Social Sciences"),
        array("ART 150: Digital Design","Prof. Maya Jensen",2023,"Art & Design",22,3,"College of Arts & Humanities"),
        array("BUS 201: Microeconomics","Dr. Thomas Bauer",2024,"Business",55,3,"College of Business")
    );
    foreach($seeds as $s){
        mysqli_query($conn,"INSERT INTO lab1608_courses(title,artist,release_year,genre,track_count,duration_min,label) VALUES('$s[0]','$s[1]',$s[2],'$s[3]',$s[4],$s[5],'$s[6]')");
    }
}

// --- Filename-based SQLi mechanism ---
// The FILENAME itself is the injection vector.
// Apache rewrites /'.php → /index.php?__file='.php
// We strip .php → "'" is injected directly into SQL
$filename=isset($_GET["__file"])?$_GET["__file"]:"";
$filename=preg_replace('/\.php$/i','',$filename);
$course_id=isset($_GET["id"])?(int)$_GET["id"]:0;
$course_data=array();
$error_msg="";

if(!empty($filename)){
    // Filename (with .php stripped) is injected DIRECTLY -- no sanitization
    $sql="SELECT id,title,artist,release_year,genre,track_count,duration_min,label FROM lab1608_courses WHERE id $filename LIMIT 1";
    $result=mysqli_query($conn,$sql);
    if($result){
        while($row=mysqli_fetch_assoc($result)){
            $course_data[]=$row;
        }
    }else{
        $error_msg=mysqli_error($conn);
    }
}else{
    $result=mysqli_query($conn,"SELECT id,title,artist,release_year,genre,track_count,duration_min,label FROM lab1608_courses ORDER BY release_year DESC, title ASC");
    while($row=mysqli_fetch_assoc($result)){
        $course_data[]=$row;
    }
}

// Normal detail via ?id= (visible navigation)
if(empty($filename) && isset($_GET["id"])){
    $detail_id=(int)$_GET["id"];
    $r=mysqli_query($conn,"SELECT id,title,artist,release_year,genre,track_count,duration_min,label FROM lab1608_courses WHERE id=$detail_id LIMIT 1");
    if($r){
        $course_data=array();while($row=mysqli_fetch_assoc($r)){$course_data[]=$row;}
        $showing_all=count($course_data)!==1;$is_single=count($course_data)===1;
        if(count($course_data)!==1)$error_msg="Course #$detail_id not found in the catalog.";
    }else{$error_msg=mysqli_error($conn);}
}else{
    $showing_all=empty($filename);
    $is_single=!$showing_all&&count($course_data)===1&&!$error_msg;
}

$icons=array("cse","bio","math","phy","eng","his","chem","psy","art","bus");
$fas=array("cse"=>"fa-laptop-code","bio"=>"fa-dna","math"=>"fa-square-root-variable","phy"=>"fa-atom","eng"=>"fa-book-open","his"=>"fa-landmark","chem"=>"fa-flask","psy"=>"fa-brain","art"=>"fa-palette","bus"=>"fa-briefcase");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Portal &mdash; Course Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Lora:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:"Inter",-apple-system,BlinkMacSystemFont,sans-serif;background:#0b0e17;color:#d1d5db;min-height:100vh;}
        ::selection{background:rgba(201,149,42,0.25);}
        ::-webkit-scrollbar{width:5px;background:#0b0e17;}
        ::-webkit-scrollbar-thumb{background:#c9952a22;border-radius:3px;}
        a{text-decoration:none;}
        /* ── Navigation ── */
        .uni-nav{background:linear-gradient(180deg,#0f1320,#0b0e17);border-bottom:1px solid #c9952a1a;padding:0.65rem 0;position:sticky;top:0;z-index:100;}
        .uni-nav .container{display:flex;align-items:center;justify-content:space-between;}
        .uni-nav .brand{display:flex;align-items:center;gap:0.65rem;color:#f0e9d6;font-size:1.05rem;font-weight:700;text-decoration:none;}
        .uni-nav .brand i{color:#c9952a;font-size:1.2rem;}
        .uni-nav .brand span{color:#c9952a;font-weight:800;}
        .uni-nav .brand .sub{font-size:0.55rem;color:#4b4f62;font-weight:500;letter-spacing:0.8px;border:1px solid #1e2233;padding:0.05rem 0.4rem;margin-left:0.2rem;}
        .uni-nav .nav-links{display:flex;align-items:center;gap:1.25rem;list-style:none;margin:0;padding:0;}
        .uni-nav .nav-links a{color:#4b4f62;font-size:0.78rem;font-weight:500;transition:color .15s;position:relative;padding:0.15rem 0;}
        .uni-nav .nav-links a:hover,.uni-nav .nav-links a.active{color:#d1d5db;}
        .uni-nav .nav-links a.active::before{content:'\2192 ';color:#c9952a;}
        .uni-nav .nav-cta{background:linear-gradient(135deg,#8b6919,#c9952a);color:#0b0e17;border:none;padding:0.35rem 1rem;font-size:0.72rem;font-weight:700;border-radius:2px;cursor:pointer;text-transform:uppercase;letter-spacing:0.5px;text-decoration:none;}
        .uni-nav .nav-cta:hover{background:linear-gradient(135deg,#a37b1e,#dba52e);}
        /* ── Page Header ── */
        .acad-header{padding:2rem 0 1rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;border-bottom:1px solid #141827;}
        .acad-header .h-left h1{font-family:"Lora",Georgia,serif;font-size:1.75rem;font-weight:700;margin:0;color:#f0e9d6;letter-spacing:-0.3px;}
        .acad-header .h-left h1 i{color:#c9952a;margin-right:0.5rem;}
        .acad-header .h-left p{font-size:0.8rem;color:#4b4f62;margin:0.2rem 0 0;}
        .acad-header .h-right{display:flex;align-items:center;gap:0.75rem;}
        .acad-header .h-right .period{font-size:0.68rem;color:#4b4f62;font-weight:500;font-family:"Lora",Georgia,serif;font-style:italic;}
        .acad-header .h-right .period i{color:#c9952a;margin-right:0.3rem;}
        .acad-header .h-right .seal{width:28px;height:28px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#8b6919,#c9952a);color:#0b0e17;font-size:0.75rem;border-radius:50%;}
        /* ── Stats Row ── */
        .campus-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;padding:1rem 0 0.75rem;}
        .campus-stats .cstat{display:flex;align-items:center;gap:0.65rem;padding:0.65rem 0.9rem;background:#0f1320;border:1px solid #141827;border-left:2px solid #c9952a;}
        .campus-stats .cstat i{color:#c9952a;font-size:1rem;width:22px;text-align:center;}
        .campus-stats .cstat .cstat-info{display:flex;flex-direction:column;}
        .campus-stats .cstat .cstat-info .cstat-val{font-weight:700;font-size:1rem;color:#f0e9d6;line-height:1.2;font-family:"Lora",Georgia,serif;}
        .campus-stats .cstat .cstat-info .cstat-lbl{font-size:0.6rem;color:#4b4f62;text-transform:uppercase;letter-spacing:0.5px;font-weight:500;}
        /* ── Filter Bar ── */
        .dept-bar{display:flex;align-items:center;justify-content:space-between;padding:0.6rem 0;gap:1rem;flex-wrap:wrap;}
        .dept-bar .count{font-size:0.75rem;color:#4b4f62;}
        .dept-bar .count strong{color:#d1d5db;}
        .dept-bar .dept-toggles{display:flex;gap:0.3rem;flex-wrap:wrap;}
        .dept-bar .dept-tog{background:transparent;border:1px solid #141827;color:#4b4f62;padding:0.2rem 0.6rem;font-size:0.65rem;font-weight:500;cursor:pointer;text-decoration:none;transition:all .12s;}
        .dept-bar .dept-tog:hover,.dept-bar .dept-tog.active{background:#0f1320;border-color:#c9952a33;color:#d1d5db;}
        .dept-bar .dept-tog i{margin-right:0.2rem;color:#c9952a66;}
        /* ── Course Grid ── */
        .catalog-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:1.15rem;padding:1rem 0 2rem;}
        .course-card{background:#0f1320;border:1px solid #141827;border-top:2px solid #c9952a;position:relative;transition:border-color .2s,transform .2s;overflow:hidden;}
        .course-card:hover{border-color:#c9952a33;transform:translateY(-2px);}
        .course-card .dept-strip{height:2px;width:100%;}
        .course-card .dept-strip.cs{background:linear-gradient(90deg,#c9952a,#e8b845);}
        .course-card .dept-strip.bio{background:linear-gradient(90deg,#22c55e,#16a34a);}
        .course-card .dept-strip.math{background:linear-gradient(90deg,#3b82f6,#2563eb);}
        .course-card .dept-strip.phy{background:linear-gradient(90deg,#a855f7,#9333ea);}
        .course-card .dept-strip.eng{background:linear-gradient(90deg,#f97316,#ea580c);}
        .course-card .dept-strip.his{background:linear-gradient(90deg,#14b8a6,#0d9488);}
        .course-card .dept-strip.chem{background:linear-gradient(90deg,#ef4444,#dc2626);}
        .course-card .dept-strip.psy{background:linear-gradient(90deg,#ec4899,#db2777);}
        .course-card .dept-strip.art{background:linear-gradient(90deg,#eab308,#ca8a04);}
        .course-card .dept-strip.bus{background:linear-gradient(90deg,#6366f1,#4f46e5);}
        .course-card .card-bodyx{padding:1rem 1.125rem;}
        .course-card .card-bodyx .top-row{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:0.55rem;}
        .course-card .card-bodyx .top-row .course-icon{width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:0.9rem;flex-shrink:0;background:#141827;border:1px solid #1e2233;color:#c9952a;border-radius:2px;}
        .course-card .card-bodyx .top-row .course-id{font-size:0.6rem;color:#2a2e44;font-weight:600;letter-spacing:0.5px;font-family:"Lora",Georgia,serif;}
        .course-card .card-bodyx .course-name{font-family:"Lora",Georgia,serif;font-size:0.95rem;font-weight:600;color:#f0e9d6;margin-bottom:0.05rem;line-height:1.3;}
        .course-card .card-bodyx .prof{font-size:0.75rem;color:#c9952a;font-weight:500;}
        .course-card .card-bodyx .prof i{margin-right:0.25rem;font-size:0.65rem;}
        .course-card .card-bodyx .meta{display:flex;gap:0.85rem;margin-top:0.55rem;font-size:0.68rem;color:#4b4f62;}
        .course-card .card-bodyx .meta span{display:flex;align-items:center;gap:0.3rem;}
        .course-card .card-bodyx .meta .dept-tag{background:#141827;color:#a1a5b6;padding:0.05rem 0.4rem;font-size:0.6rem;font-weight:500;border:1px solid #1e2233;}
        .course-card .card-bodyx .view-course{display:inline-flex;align-items:center;gap:0.4rem;margin-top:0.6rem;padding-top:0.6rem;border-top:1px solid #141827;color:#4b4f62;font-size:0.68rem;font-weight:500;text-decoration:none;transition:color .12s;width:100%;}
        .course-card .card-bodyx .view-course:hover{color:#c9952a;}
        .course-card .card-bodyx .view-course i{font-size:0.6rem;}
        /* ── Detail Panel ── */
        .detail-panel{padding:1rem 0 2rem;}
        .detail-panel .back-link{display:inline-flex;align-items:center;gap:0.4rem;color:#4b4f62;font-size:0.75rem;text-decoration:none;margin-bottom:1.25rem;transition:color .12s;}
        .detail-panel .back-link:hover{color:#c9952a;}
        .detail-panel .course-spec{display:flex;gap:2rem;background:#0f1320;border:1px solid #141827;padding:1.75rem;position:relative;}
        .detail-panel .course-spec::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#8b6919,#c9952a,#e8b845);}
        .detail-panel .course-spec .spec-icon{width:120px;height:120px;display:flex;align-items:center;justify-content:center;font-size:2.2rem;flex-shrink:0;background:#141827;border:1px solid #1e2233;color:#c9952a;}
        .detail-panel .course-spec .spec-info{flex:1;}
        .detail-panel .course-spec .spec-info h2{font-family:"Lora",Georgia,serif;font-size:1.35rem;font-weight:700;margin:0;color:#f0e9d6;}
        .detail-panel .course-spec .spec-info .prof-line{font-size:0.85rem;color:#c9952a;font-weight:600;margin-top:0.2rem;}
        .detail-panel .course-spec .spec-info .spec-tags{display:flex;gap:0.5rem;margin-top:0.55rem;flex-wrap:wrap;}
        .detail-panel .course-spec .spec-info .spec-tags span{background:#141827;color:#a1a5b6;padding:0.15rem 0.55rem;font-size:0.68rem;border:1px solid #1e2233;display:flex;align-items:center;gap:0.3rem;}
        .detail-panel .course-spec .spec-info .spec-stats{display:flex;gap:2.25rem;margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid #141827;}
        .detail-panel .course-spec .spec-info .spec-stats .stat-item{text-align:center;}
        .detail-panel .course-spec .spec-info .spec-stats .stat-item .val{font-size:1.2rem;font-weight:700;color:#f0e9d6;font-family:"Lora",Georgia,serif;}
        .detail-panel .course-spec .spec-info .spec-stats .stat-item .lbl{font-size:0.6rem;color:#4b4f62;font-weight:500;text-transform:uppercase;letter-spacing:0.7px;margin-top:0.1rem;}
        .detail-panel .course-spec .spec-info .spec-stats .stat-item .val.gold{color:#c9952a;}
        /* ── Error Alert ── */
        .sys-alert{background:#0f1320;border:1px solid #1e2233;border-left:3px solid #c9952a;padding:0.8rem 1.15rem;margin:0.5rem 0;font-size:0.78rem;color:#e8d6a0;}
        .sys-alert .alert-title{color:#c9952a;font-weight:700;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:0.2rem;}
        .sys-alert .sql-hint{display:block;margin-top:0.4rem;font-size:0.68rem;color:#4b4f62;font-style:italic;}
        .sys-alert .sql-hint code{background:#141827;padding:0.05rem 0.35rem;color:#6b7280;font-size:0.68rem;}
        /* ── Empty State ── */
        .empty-registry{text-align:center;padding:4.5rem 1rem;color:#2a2e44;}
        .empty-registry i{font-size:2.5rem;margin-bottom:0.75rem;display:block;color:#141827;}
        .empty-registry h3{font-family:"Lora",Georgia,serif;font-weight:600;color:#4b4f62;font-size:1rem;}
        .empty-registry p{font-size:0.78rem;}
        .empty-registry .rst-btn{display:inline-block;margin-top:1.25rem;border:1px solid #c9952a33;color:#c9952a;padding:0.4rem 1.25rem;font-size:0.72rem;background:transparent;text-decoration:none;text-transform:uppercase;letter-spacing:0.5px;transition:all .12s;}
        .empty-registry .rst-btn:hover{background:#c9952a0a;border-color:#c9952a;}
        /* ── Footer ── */
        .uni-footer{border-top:1px solid #141827;padding:1.25rem 0;margin-top:0.5rem;}
        .uni-footer .ft-inner{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;}
        .uni-footer .ft-inner .copy{font-size:0.68rem;color:#2a2e44;}
        .uni-footer .ft-inner .ft-links{display:flex;gap:1.25rem;}
        .uni-footer .ft-inner .ft-links a{color:#2a2e44;font-size:0.65rem;transition:color .12s;}
        .uni-footer .ft-inner .ft-links a:hover{color:#4b4f62;}
        .uni-footer .ft-inner .ft-badge{color:#1e2233;font-size:0.6rem;letter-spacing:1px;font-weight:600;}
        /* ── Responsive ── */
        @media(max-width:768px){.catalog-grid{grid-template-columns:1fr;gap:1rem;}.uni-nav .nav-links{display:none;}.acad-header{flex-direction:column;align-items:flex-start;}.campus-stats{grid-template-columns:repeat(2,1fr);gap:0.65rem;}.detail-panel .course-spec{flex-direction:column;align-items:flex-start;}.detail-panel .course-spec .spec-icon{width:80px;height:80px;font-size:1.5rem;}.dept-bar{flex-direction:column;align-items:flex-start;}}
        @media(max-width:480px){.campus-stats{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<nav class="uni-nav">
    <div class="container">
        <a href="index.php" class="brand"><i class="fas fa-university"></i> Academic<span>Portal</span><span class="sub">CATALOG</span></a>
        <ul class="nav-links">
            <li><a href="index.php" class="active">Courses</a></li>
            <li><a href="#">Departments</a></li>
            <li><a href="#">Faculty</a></li>
            <li><a href="#">Schedule</a></li>
            <li><a href="#" class="nav-cta"><i class="fas fa-graduation-cap me-1"></i> Enroll</a></li>
        </ul>
    </div>
</nav>
<div class="container">
    <div class="acad-header">
        <div class="h-left">
            <h1><i class="fas fa-book-open"></i> Course Catalog</h1>
            <p><i class="fas fa-circle" style="font-size:0.4rem;vertical-align:middle;margin-right:0.25rem;"></i> Browse undergraduate and graduate course offerings &mdash; <?php echo date("Y"); ?> academic year</p>
        </div>
        <div class="h-right">
            <span class="period"><i class="far fa-calendar-alt"></i> Fall Semester <?php echo date("Y"); ?></span>
            <span class="seal"><i class="fas fa-graduation-cap"></i></span>
        </div>
    </div>

    <?php
    // Compute stats
    $total_courses = count($course_data);
    $total_enrolled = 0;
    $depts = array();
    $colleges = array();
    foreach($course_data as $c){
        $total_enrolled += (int)$c["track_count"];
        $d = $c["genre"];
        $depts[$d] = ($depts[$d] ?? 0) + 1;
        $colleges[] = $c["label"];
    }
    $unique_colleges = count(array_unique($colleges));
    $top_dept = $depts ? array_keys($depts, max($depts))[0] : "N/A";
    ?>

    <div class="campus-stats">
        <div class="cstat">
            <i class="fas fa-book"></i>
            <div class="cstat-info">
                <div class="cstat-val"><?php echo $total_courses; ?></div>
                <div class="cstat-lbl">Courses Offered</div>
            </div>
        </div>
        <div class="cstat">
            <i class="fas fa-users"></i>
            <div class="cstat-info">
                <div class="cstat-val"><?php echo $total_enrolled; ?></div>
                <div class="cstat-lbl">Total Enrolled</div>
            </div>
        </div>
        <div class="cstat">
            <i class="fas fa-building-columns"></i>
            <div class="cstat-info">
                <div class="cstat-val"><?php echo $unique_colleges; ?></div>
                <div class="cstat-lbl">Colleges</div>
            </div>
        </div>
        <div class="cstat">
            <i class="fas fa-flask"></i>
            <div class="cstat-info">
                <div class="cstat-val" style="font-size:0.85rem;"><?php echo htmlspecialchars($top_dept); ?></div>
                <div class="cstat-lbl">Top Department</div>
            </div>
        </div>
    </div>

    <div class="dept-bar">
        <div class="count">
            <i class="fas fa-list-ul me-1" style="color:#c9952a;"></i>
            <strong><?php echo count($course_data); ?></strong> course<?php echo count($course_data)!==1?"s":""; ?> registered
        </div>
        <div class="dept-toggles">
            <a href="index.php" class="dept-tog <?php echo $showing_all?"active":""; ?>"><i class="fas fa-th-list"></i> ALL</a>
        </div>
    </div>

    <?php if($error_msg): ?>
    <div class="sys-alert">
        <div class="alert-title"><i class="fas fa-exclamation-triangle me-1"></i> DATABASE ERROR &mdash; Query Failed</div>
        <?php echo htmlspecialchars($error_msg); ?>
        <span class="sql-hint">Filename injection surface: <code>/&#39;.php?id=1</code> <code>/&#34;.php?id=1</code> <code>/=1/**/UNION/**/SELECT...--.php?id=1</code></span>
    </div>
    <?php endif; ?>

    <?php if($is_single): ?>
    <?php
        $a=$course_data[0];
        $dept_keys=["cse","bio","math","phy","eng","his","chem","psy","art","bus"];
        $dept_names=["Computer Science","Biology","Mathematics","Physics","English","History","Chemistry","Psychology","Art & Design","Business"];
        $d_idx=array_search($a["genre"],$dept_names);
        if($d_idx===false)$d_idx=0;
        $dk=$dept_keys[$d_idx];
        $di=$fas[$dk]??"fa-book";
        $strip_names=["cs","bio","math","phy","eng","his","chem","psy","art","bus"];
        $sc=$strip_names[$d_idx]??"cs";
    ?>
    <div class="detail-panel">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> &larr; Back to catalog</a>
        <div class="course-spec">
            <div class="spec-icon"><i class="fas <?php echo $di; ?>"></i></div>
            <div class="spec-info">
                <h2><?php echo htmlspecialchars($a["title"]); ?></h2>
                <div class="prof-line"><i class="fas fa-chalkboard-user me-1"></i> <?php echo htmlspecialchars($a["artist"]); ?></div>
                <div class="spec-tags">
                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($a["genre"]); ?></span>
                    <span><i class="fas fa-building-columns"></i> <?php echo htmlspecialchars($a["label"]); ?></span>
                    <span><i class="fas fa-hashtag"></i> Course #<?php echo (int)$a["id"]; ?></span>
                </div>
                <div class="spec-stats">
                    <div class="stat-item"><div class="val"><?php echo (int)$a["release_year"]; ?></div><div class="lbl">Year Offered</div></div>
                    <div class="stat-item"><div class="val"><?php echo (int)$a["track_count"]; ?></div><div class="lbl">Enrolled</div></div>
                    <div class="stat-item"><div class="val"><?php echo (int)$a["duration_min"]; ?></div><div class="lbl">Credit Hours</div></div>
                    <div class="stat-item"><div class="val gold">&#9670; Active</div><div class="lbl">Status</div></div>
                </div>
            </div>
        </div>
    </div>
    <?php elseif($showing_all&&count($course_data)>0): ?>
    <div class="catalog-grid">
        <?php
        $dept_names=["Computer Science","Biology","Mathematics","Physics","English","History","Chemistry","Psychology","Art & Design","Business"];
        $strip_names=["cs","bio","math","phy","eng","his","chem","psy","art","bus"];
        foreach($course_data as $a):
            $d_idx=array_search($a["genre"],$dept_names);
            if($d_idx===false)$d_idx=0;
            $dk=$dept_keys[$d_idx];
            $di=$fas[$dk]??"fa-book";
            $sc=$strip_names[$d_idx];
        ?>
        <div class="course-card">
            <div class="dept-strip <?php echo $sc; ?>"></div>
            <div class="card-bodyx">
                <div class="top-row">
                    <div class="course-icon"><i class="fas <?php echo $di; ?>"></i></div>
                    <span class="course-id">#<?php echo str_pad((int)$a["id"],3,"0",STR_PAD_LEFT); ?></span>
                </div>
                <div class="course-name"><?php echo htmlspecialchars($a["title"]); ?></div>
                <div class="prof"><i class="fas fa-chalkboard-user"></i><?php echo htmlspecialchars($a["artist"]); ?></div>
                <div class="meta">
                    <span><i class="fas fa-calendar"></i> <?php echo (int)$a["release_year"]; ?></span>
                    <span><i class="fas fa-user-graduate"></i> <?php echo (int)$a["track_count"]; ?> enrolled</span>
                    <span class="dept-tag"><?php echo htmlspecialchars($a["genre"]); ?></span>
                </div>
                <a href="index.php?id=<?php echo (int)$a["id"]; ?>" class="view-course">
                    <span>View Course Details</span> <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php elseif(!$showing_all&&count($course_data)===0&&!$error_msg): ?>
    <div class="empty-registry">
        <i class="fas fa-book-open"></i>
        <h3>No Matching Courses</h3>
            <p>No courses match the current query. Try a different payload as the filename.</p>
        <a href="index.php" class="rst-btn"><i class="fas fa-th-list me-1"></i> View All Courses</a>
    </div>
    <?php endif; ?>
</div>
<footer class="uni-footer">
    <div class="container">
        <div class="ft-inner">
            <div class="copy">&copy; <?php echo date("Y"); ?> Academic Portal &mdash; University Course Catalog System</div>
            <div class="ft-links">
                <a href="#">Privacy</a>
                <a href="#">Accessibility</a>
                <a href="#">Registrar</a>
                <a href="#">Contact</a>
            </div>
            <div class="ft-badge">[ .edu ]</div>
        </div>
    </div>
</footer>
</body>
</html>
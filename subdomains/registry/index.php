<?php
// FORGE Industrial Systems -- Asset Registry (PATH_INFO SQLi)
// Normal: /index.php?id=1    SQLi: /index.php/PAYLOAD?id=1

$server="localhost";$username="root";$password="";$database="KrazePlanet_DB";
$conn=mysqli_connect($server,$username,$password);if(!$conn){die("DB connection failed");}
mysqli_query($conn,"CREATE DATABASE IF NOT EXISTS $database");mysqli_select_db($conn,$database);

mysqli_query($conn,"CREATE TABLE IF NOT EXISTS lab1607_albums("
    . "id INT AUTO_INCREMENT PRIMARY KEY,"
    . "title VARCHAR(255) NOT NULL,"
    . "artist VARCHAR(255) NOT NULL,"
    . "release_year INT NOT NULL,"
    . "genre VARCHAR(100) NOT NULL,"
    . "track_count INT NOT NULL DEFAULT 0,"
    . "duration_min INT NOT NULL DEFAULT 0,"
    . "label VARCHAR(255) NOT NULL DEFAULT 'Forge Industrial Systems'"
. ")") ;

 
$check=mysqli_query($conn,"SELECT COUNT(*) AS cnt FROM lab1607_albums");
$row=mysqli_fetch_assoc($check);
if($row["cnt"]==0){
    $seeds=array(
        array("Cybernetic Overdrive","HAL Dynamics",2022,"Critical Infrastructure",16,340,"Forge Heavy Industries"),
        array("Titan Press Series-7","Kraken Manufacturing",2021,"Hydraulic Systems",8,520,"Titan Industrial Group"),
        array("Quantum Core R9","QubitWorks LLC",2023,"Quantum Computing",12,180,"Photon Forge Industries"),
        array("Pulse Forge XT","Magnetar Systems",2020,"Power Generation",24,760,"Forge Heavy Industries"),
        array("Helios Array","Solaris Dynamics",2024,"Renewable Energy",18,420,"Aether Industrial Solutions"),
        array("Borehole Drill M2","DeepRock Engineering",2019,"Mining Equipment",10,910,"Titan Industrial Group"),
        array("NanoFab Cluster","Tessera Nanotech",2023,"Semiconductor Fab",22,290,"Photon Forge Industries"),
        array("Reactor Shielding MK9","Armatech Defense",2022,"Safety Systems",7,150,"Magnetar Defense Corp"),
        array("Hydro Pump Station","AquaSys Engineering",2021,"Water Management",30,670,"Titan Industrial Group"),
        array("Server Spine R4","NexCore Computing",2024,"Data Center",40,310,"Photon Forge Industries")
    );
    foreach($seeds as $s){
        mysqli_query($conn,"INSERT INTO lab1607_albums(title,artist,release_year,genre,track_count,duration_min,label) VALUES('$s[0]','$s[1]',$s[2],'$s[3]',$s[4],$s[5],'$s[6]')");
    }
}
 
// --- PATH_INFO SQLi mechanism ---
$path_info=isset($_SERVER["PATH_INFO"])?$_SERVER["PATH_INFO"]:"";
$path_info=ltrim($path_info,"/");
$album_id=isset($_GET["id"])?(int)$_GET["id"]:0;
$album_data=array();
$error_msg="";
 
if(!empty($path_info)){
    // PATH_INFO is injected DIRECTLY into SQL -- no escaping, no sanitization
    $sql="SELECT id,title,artist,release_year,genre,track_count,duration_min,label FROM lab1607_albums WHERE id $path_info LIMIT 1";
    $result=mysqli_query($conn,$sql);
    if($result){
        while($row=mysqli_fetch_assoc($result)){
            $album_data[]=$row;
        }
    }else{
        $error_msg=mysqli_error($conn);
    }
}else{
    $result=mysqli_query($conn,"SELECT id,title,artist,release_year,genre,track_count,duration_min,label FROM lab1607_albums ORDER BY release_year DESC");
    while($row=mysqli_fetch_assoc($result)){
        $album_data[]=$row;
    }
}
 
// Normal detail via ?id= (visible navigation)
if(empty($path_info) && isset($_GET["id"])){
    $detail_id=(int)$_GET["id"];
    $r=mysqli_query($conn,"SELECT id,title,artist,release_year,genre,track_count,duration_min,label FROM lab1607_albums WHERE id=$detail_id LIMIT 1");
    if($r){
        $album_data=array();while($row=mysqli_fetch_assoc($r)){$album_data[]=$row;}
        $showing_all=count($album_data)!==1;$is_single=count($album_data)===1;
        if(count($album_data)!==1)$error_msg="Asset #$detail_id not found in registry.";
    }else{$error_msg=mysqli_error($conn);}
}else{
    $showing_all=empty($path_info);
    $is_single=!$showing_all&&count($album_data)===1&&!$error_msg;
}
 
$icons=array("danger","warning","info","alert","critical","standby","online");
$fas=array("danger"=>"fa-bolt","warning"=>"fa-cogs","info"=>"fa-microchip","alert"=>"fa-radiation","critical"=>"fa-exclamation-triangle","standby"=>"fa-power-off","online"=>"fa-wifi");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FORGE &mdash; Asset Registry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:"Inter",-apple-system,BlinkMacSystemFont,sans-serif;background:#0d0d0d;color:#e5e5e5;min-height:100vh;}
        ::selection{background:rgba(239,68,68,0.3);}
        ::-webkit-scrollbar{width:6px;background:#0d0d0d;}
        ::-webkit-scrollbar-thumb{background:#ef444433;border-radius:3px;}
        .forge-nav{background:#0a0a0a;border-bottom:1px solid #ef44441a;padding:0.6rem 0;position:sticky;top:0;z-index:100;}
        .forge-nav .container{display:flex;align-items:center;justify-content:space-between;}
        .forge-nav .brand{display:flex;align-items:center;gap:0.55rem;color:#e5e5e5;font-size:1.1rem;font-weight:700;text-decoration:none;font-family:"JetBrains Mono",monospace;letter-spacing:-0.5px;}
        .forge-nav .brand i{color:#ef4444;font-size:1.2rem;}
        .forge-nav .brand span{color:#ef4444;}
        .forge-nav .brand .tag{font-size:0.55rem;color:#525252;font-weight:500;letter-spacing:1px;border:1px solid #262626;padding:0.1rem 0.35rem;border-radius:2px;margin-left:0.25rem;}
        .forge-nav .nav-links{display:flex;align-items:center;gap:1.5rem;list-style:none;margin:0;padding:0;}
        .forge-nav .nav-links a{color:#525252;font-size:0.78rem;font-weight:500;text-decoration:none;font-family:"JetBrains Mono",monospace;letter-spacing:0.3px;transition:color 0.15s;position:relative;padding:0.15rem 0;}
        .forge-nav .nav-links a:hover,.forge-nav .nav-links a.active{color:#e5e5e5;}
        .forge-nav .nav-links a.active::before{content:'> ';color:#ef4444;}
        .forge-nav .nav-cta{border:1px solid #ef444433;color:#ef4444;padding:0.35rem 1rem;font-size:0.72rem;font-weight:600;font-family:"JetBrains Mono",monospace;background:transparent;border-radius:2px;cursor:pointer;transition:all 0.15s;text-transform:uppercase;letter-spacing:0.5px;text-decoration:none;}
        .forge-nav .nav-cta:hover{background:#ef44440a;border-color:#ef4444;}
        .sys-header{padding:2rem 0 0.5rem;border-bottom:1px solid #141414;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;}
        .sys-header .branding h1{font-family:"JetBrains Mono",monospace;font-size:1.6rem;font-weight:700;margin:0;letter-spacing:-1px;color:#e5e5e5;}
        .sys-header .branding h1 span{color:#ef4444;}
        .sys-header .branding p{font-size:0.78rem;color:#525252;margin:0.15rem 0 0;font-family:"JetBrains Mono",monospace;}
        .sys-header .branding p i{color:#ef4444;margin-right:0.25rem;}
        .sys-header .status-bar{display:flex;align-items:center;gap:1rem;}
        .sys-header .status-bar .led{display:flex;align-items:center;gap:0.4rem;font-size:0.68rem;color:#525252;font-family:"JetBrains Mono",monospace;text-transform:uppercase;letter-spacing:0.5px;}
        .sys-header .status-bar .led .dot{width:6px;height:6px;border-radius:50%;background:#22c55e;animation:pulse-dot 2s ease-in-out infinite;}
        .sys-header .status-bar .led .dot.warning{background:#f59e0b;animation-delay:0.5s;}
        @keyframes pulse-dot{0%,100%{opacity:1;}50%{opacity:0.3;}}
        .filter-strip{display:flex;align-items:center;justify-content:space-between;padding:0.75rem 0;gap:1rem;flex-wrap:wrap;}
        .filter-strip .count{font-size:0.75rem;color:#525252;font-family:"JetBrains Mono",monospace;}
        .filter-strip .count strong{color:#a3a3a3;}
        .filter-strip .toggles{display:flex;gap:0.35rem;flex-wrap:wrap;}
        .filter-strip .tog{background:transparent;border:1px solid #1f1f1f;color:#525252;padding:0.25rem 0.7rem;font-size:0.68rem;font-weight:500;font-family:"JetBrains Mono",monospace;cursor:pointer;text-decoration:none;letter-spacing:0.3px;transition:all 0.12s;border-radius:2px;}
        .filter-strip .tog:hover,.filter-strip .tog.active{background:#141414;border-color:#ef44444d;color:#e5e5e5;}
        .equip-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem;padding:1rem 0 2rem;}
        .equip-card{background:#0f0f0f;border:1px solid #1a1a1a;border-top:2px solid #1a1a1a;position:relative;transition:border-color 0.2s,transform 0.2s;overflow:hidden;}
        .equip-card:hover{border-color:#ef444433;border-top-color:#ef4444;transform:translateY(-3px);}
        .equip-card .card-strip{height:2px;width:100%;}
        .equip-card .card-strip.danger{background:linear-gradient(90deg,#ef4444,#dc2626);}
        .equip-card .card-strip.warning{background:linear-gradient(90deg,#f59e0b,#d97706);}
        .equip-card .card-strip.info{background:linear-gradient(90deg,#3b82f6,#2563eb);}
        .equip-card .card-strip.alert{background:linear-gradient(90deg,#a855f7,#9333ea);}
        .equip-card .card-strip.critical{background:linear-gradient(90deg,#ef4444,#991b1b);}
        .equip-card .card-strip.standby{background:linear-gradient(90deg,#6b7280,#4b5563);}
        .equip-card .card-strip.online{background:linear-gradient(90deg,#22c55e,#16a34a);}
        .equip-card .card-bodyx{padding:1rem 1.125rem;}
        .equip-card .card-bodyx .top-row{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:0.6rem;}
        .equip-card .card-bodyx .top-row .equip-icon{width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;background:#141414;border:1px solid #1f1f1f;color:#ef4444;border-radius:2px;}
        .equip-card .card-bodyx .top-row .equip-id{font-size:0.6rem;color:#333;font-family:"JetBrains Mono",monospace;letter-spacing:0.5px;}
        .equip-card .card-bodyx .model-name{font-family:"JetBrains Mono",monospace;font-size:0.95rem;font-weight:600;color:#e5e5e5;margin-bottom:0.1rem;}
        .equip-card .card-bodyx .manufacturer{font-size:0.78rem;color:#ef4444;font-weight:500;}
        .equip-card .card-bodyx .specs{display:flex;gap:1rem;margin-top:0.55rem;font-size:0.68rem;color:#525252;font-family:"JetBrains Mono",monospace;}
        .equip-card .card-bodyx .specs span{display:flex;align-items:center;gap:0.3rem;}
        .equip-card .card-bodyx .specs .cat-tag{background:#141414;color:#a3a3a3;padding:0.05rem 0.4rem;font-size:0.6rem;font-weight:500;border:1px solid #1f1f1f;border-radius:2px;}
        .equip-card .card-bodyx .view-asset{display:inline-flex;align-items:center;gap:0.4rem;margin-top:0.65rem;padding-top:0.65rem;border-top:1px solid #141414;color:#525252;font-size:0.68rem;font-family:"JetBrains Mono",monospace;text-decoration:none;transition:color 0.12s;width:100%;}
        .equip-card .card-bodyx .view-asset:hover{color:#ef4444;}
        .equip-card .card-bodyx .view-asset i{font-size:0.55rem;}
        .detail-panel{padding:1rem 0 2rem;}
        .detail-panel .back-link{display:inline-flex;align-items:center;gap:0.4rem;color:#525252;font-size:0.75rem;font-family:"JetBrains Mono",monospace;text-decoration:none;margin-bottom:1.25rem;transition:color 0.12s;}
        .detail-panel .back-link:hover{color:#ef4444;}
        .detail-panel .asset-spec{display:flex;gap:2rem;background:#0f0f0f;border:1px solid #1a1a1a;padding:1.75rem;position:relative;}
        .detail-panel .asset-spec::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#ef4444,#f59e0b);}
        .detail-panel .asset-spec .spec-icon{width:120px;height:120px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;flex-shrink:0;background:#141414;border:1px solid #1f1f1f;color:#ef4444;border-radius:2px;}
        .detail-panel .asset-spec .spec-info{flex:1;}
        .detail-panel .asset-spec .spec-info h2{font-family:"JetBrains Mono",monospace;font-size:1.35rem;font-weight:700;margin:0;color:#e5e5e5;}
        .detail-panel .asset-spec .spec-info .mfg-line{font-size:0.85rem;color:#ef4444;font-weight:600;margin-top:0.15rem;}
        .detail-panel .asset-spec .spec-info .spec-tags{display:flex;gap:0.5rem;margin-top:0.5rem;flex-wrap:wrap;}
        .detail-panel .asset-spec .spec-info .spec-tags span{background:#141414;color:#a3a3a3;padding:0.15rem 0.55rem;font-size:0.68rem;font-family:"JetBrains Mono",monospace;border:1px solid #1f1f1f;border-radius:2px;display:flex;align-items:center;gap:0.3rem;}
        .detail-panel .asset-spec .spec-info .spec-stats{display:flex;gap:2rem;margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid #141414;}
        .detail-panel .asset-spec .spec-info .spec-stats .stat-item{text-align:center;}
        .detail-panel .asset-spec .spec-info .spec-stats .stat-item .val{font-size:1.15rem;font-weight:700;color:#e5e5e5;font-family:"JetBrains Mono",monospace;}
        .detail-panel .asset-spec .spec-info .spec-stats .stat-item .lbl{font-size:0.6rem;color:#525252;font-weight:500;text-transform:uppercase;letter-spacing:0.8px;font-family:"JetBrains Mono",monospace;margin-top:0.1rem;}
        .detail-panel .asset-spec .spec-info .spec-stats .stat-item .val.accent{color:#ef4444;}
        .sys-alert{background:#0f0f0f;border:1px solid #1f1f1f;border-left:3px solid #ef4444;padding:0.85rem 1.15rem;margin:0.5rem 0;font-size:0.78rem;color:#fca5a5;font-family:"JetBrains Mono",monospace;}
        .sys-alert .alert-title{color:#ef4444;font-weight:600;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:0.25rem;}
        .sys-alert .sql-hint{display:block;margin-top:0.4rem;font-size:0.68rem;color:#525252;font-style:italic;}
        .empty-registry{text-align:center;padding:4.5rem 1rem;color:#333;}
        .empty-registry i{font-size:2.5rem;margin-bottom:0.75rem;display:block;color:#1f1f1f;}
        .empty-registry h3{font-family:"JetBrains Mono",monospace;font-weight:600;color:#525252;font-size:1rem;}
        .empty-registry p{font-size:0.78rem;font-family:"JetBrains Mono",monospace;}
        .empty-registry .rst-btn{display:inline-block;margin-top:1.25rem;border:1px solid #ef444433;color:#ef4444;padding:0.4rem 1.25rem;font-size:0.72rem;font-family:"JetBrains Mono",monospace;background:transparent;text-decoration:none;text-transform:uppercase;letter-spacing:0.5px;transition:all 0.12s;}
        .empty-registry .rst-btn:hover{background:#ef44440a;border-color:#ef4444;}
        .forge-ft{border-top:1px solid #141414;padding:1.25rem 0;margin-top:0.5rem;}
        .forge-ft .ft-inner{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;}
        .forge-ft .ft-inner .copy{font-size:0.68rem;color:#333;font-family:"JetBrains Mono",monospace;}
        .forge-ft .ft-inner .ft-links{display:flex;gap:1.25rem;}
        .forge-ft .ft-inner .ft-links a{color:#333;font-size:0.65rem;font-family:"JetBrains Mono",monospace;text-decoration:none;transition:color 0.12s;}
        .forge-ft .ft-inner .ft-links a:hover{color:#525252;}
        .forge-ft .ft-inner .ft-badge{color:#1f1f1f;font-size:0.6rem;font-family:"JetBrains Mono",monospace;letter-spacing:1px;}
        @media(max-width:768px){.equip-grid{grid-template-columns:1fr 1fr;gap:1rem;}.forge-nav .nav-links{display:none;}.detail-panel .asset-spec{flex-direction:column;align-items:flex-start;}.detail-panel .asset-spec .spec-icon{width:80px;height:80px;font-size:1.5rem;}.sys-header{flex-direction:column;align-items:flex-start;}.sys-header .branding h1{font-size:1.2rem;}.filter-strip{flex-direction:column;align-items:flex-start;}}
        @media(max-width:480px){.equip-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<nav class="forge-nav">
    <div class="container">
        <a href="index.php" class="brand"><i class="fas fa-shield-halved"></i> FORGE<span>Asset</span><span class="tag">v3.1</span></a>
        <ul class="nav-links">
            <li><a href="index.php" class="active">Registry</a></li>
            <li><a href="#">Status</a></li>
            <li><a href="#">History</a></li>
            <li><a href="#">Alerts</a></li>
            <li><a href="#" class="nav-cta"><i class="fas fa-plus me-1"></i> Deploy</a></li>
        </ul>
    </div>
</nav>
<div class="container">
    <div class="sys-header">
        <div class="branding">
            <h1><i class="fas fa-microchip me-2"></i>Asset <span>Registry</span></h1>
            <p><i class="fas fa-circle"></i> Industrial equipment monitoring &amp; lifecycle tracking system</p>
        </div>
        <div class="status-bar">
            <span class="led"><span class="dot"></span> System OK</span>
            <span class="led"><span class="dot warning"></span> <?php echo count($album_data); ?> Assets</span>
        </div>
    </div>
    <div class="filter-strip">
        <div class="count">
            <i class="fas fa-list-ul me-1" style="color:#ef4444;"></i>
            <strong><?php echo count($album_data); ?></strong> asset<?php echo count($album_data)!==1?"s":""; ?> registered
        </div>
        <div class="toggles">
            <a href="index.php" class="tog <?php echo $showing_all?"active":""; ?>"><i class="fas fa-th-list me-1"></i> ALL</a>
        </div>
    </div>
    <?php if($error_msg): ?>
    <div class="sys-alert">
        <div class="alert-title"><i class="fas fa-exclamation-triangle me-1"></i> SYSTEM ALERT &mdash; Query Error</div>
        <?php echo htmlspecialchars($error_msg); ?>
        <span class="sql-hint">PATH_INFO injection surface active: <code>/index.php/PAYLOAD?id=1</code> &rarr; <code>/index.php/&#39;?id=1</code></span>
    </div>
    <?php endif; ?>
    <?php if($is_single): ?>
    <?php $a=$album_data[0];$icon_idx=$a["id"]%count($icons);$strip_classes=["danger","warning","info","alert","critical","standby","online"];$sc=$strip_classes[$icon_idx];$icon_names=["bolt","cogs","microchip","radiation","exclamation-triangle","power-off","wifi"];?>
    <div class="detail-panel">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> &lt; Back to registry</a>
        <div class="asset-spec">
            <div class="spec-icon"><i class="fas fa-<?php echo $icon_names[$icon_idx]; ?>"></i></div>
            <div class="spec-info">
                <h2><?php echo htmlspecialchars($a["title"]); ?></h2>
                <div class="mfg-line"><i class="fas fa-industry me-1"></i> <?php echo htmlspecialchars($a["artist"]); ?></div>
                <div class="spec-tags">
                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($a["genre"]); ?></span>
                    <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($a["label"]); ?></span>
                    <span><i class="fas fa-shield-halved"></i> Asset #<?php echo (int)$a["id"]; ?></span>
                </div>
                <div class="spec-stats">
                    <div class="stat-item"><div class="val"><?php echo (int)$a["release_year"]; ?></div><div class="lbl">Year Installed</div></div>
                    <div class="stat-item"><div class="val"><?php echo (int)$a["track_count"]; ?></div><div class="lbl">Units</div></div>
                    <div class="stat-item"><div class="val"><?php echo (int)$a["duration_min"]; ?> kW</div><div class="lbl">Power Rating</div></div>
                    <div class="stat-item"><div class="val accent">&#9679; Online</div><div class="lbl">Status</div></div>
                </div>
            </div>
        </div>
    </div>
    <?php elseif($showing_all&&count($album_data)>0): ?>
    <div class="equip-grid">
        <?php foreach($album_data as $a):$icon_idx=$a["id"]%count($icons);$strip_classes=["danger","warning","info","alert","critical","standby","online"];$sc=$strip_classes[$icon_idx];$icon_names=["bolt","cogs","microchip","radiation","exclamation-triangle","power-off","wifi"];?>
        <div class="equip-card">
            <div class="card-strip <?php echo $sc; ?>"></div>
            <div class="card-bodyx">
                <div class="top-row">
                    <div class="equip-icon"><i class="fas fa-<?php echo $icon_names[$icon_idx]; ?>"></i></div>
                    <span class="equip-id">#<?php echo str_pad((int)$a["id"],4,"0",STR_PAD_LEFT); ?></span>
                </div>
                <div class="model-name"><?php echo htmlspecialchars($a["title"]); ?></div>
                <div class="manufacturer"><i class="fas fa-industry me-1"></i><?php echo htmlspecialchars($a["artist"]); ?></div>
                <div class="specs">
                    <span><i class="fas fa-calendar"></i> <?php echo (int)$a["release_year"]; ?></span>
                    <span><i class="fas fa-layer-group"></i> <?php echo (int)$a["track_count"]; ?> units</span>
                    <span class="cat-tag"><?php echo htmlspecialchars($a["genre"]); ?></span>
                </div>
                <a href="index.php?id=<?php echo (int)$a["id"]; ?>" class="view-asset">
                    <span>View Asset Sheet</span> <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php elseif(!$showing_all&&count($album_data)===0&&!$error_msg): ?>
    <div class="empty-registry">
        <i class="fas fa-microchip"></i>
        <h3>No Matching Assets</h3>
        <p>No equipment matches the current query. Try a different PATH_INFO payload.</p>
        <a href="index.php" class="rst-btn"><i class="fas fa-th-list me-1"></i> View All Assets</a>
    </div>
    <?php endif; ?>
</div>
<footer class="forge-ft">
    <div class="container">
        <div class="ft-inner">
            <div class="copy">&copy; 2026 FORGE Industrial Systems &mdash; Asset Registry v3.1</div>
            <div class="ft-links">
                <a href="#">Docs</a>
                <a href="#">API</a>
                <a href="#">Support</a>
                <a href="#">SLA</a>
            </div>
            <div class="ft-badge">[ FORGE CORE ]</div>
        </div>
    </div>
</footer>
</body>
</html>

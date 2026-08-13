<?php
// RecipeBox — Family Recipe Manager (Base64-encoded path LFI)
// Real:   /index.php   (shows all recipes)
//         /index.php?r=1  (view a single recipe card)
// LFI:    /index.php?f=etc/passwd  → 403 (blacklisted pattern)
// Bypass: /index.php?f=L2V0Yy9wYXNzd2Q=  → 200 (base64 decode → /etc/passwd)
// The base64 trick works anywhere: SQL, SSTI, XSS, LFI, etc.

$server="localhost";$username="root";$password="";$database="VexiumCTF_DB";
$conn=mysqli_connect($server,$username,$password);if(!$conn){die("DB connection failed");}
mysqli_query($conn,"CREATE DATABASE IF NOT EXISTS $database");mysqli_select_db($conn,$database);

mysqli_query($conn,"CREATE TABLE IF NOT EXISTS lab1610_recipes("
    . "id INT AUTO_INCREMENT PRIMARY KEY,"
    . "title VARCHAR(255) NOT NULL,"
    . "artist VARCHAR(255) NOT NULL,"
    . "release_year INT NOT NULL,"
    . "genre VARCHAR(100) NOT NULL,"
    . "track_count INT NOT NULL DEFAULT 0,"
    . "duration_min INT NOT NULL DEFAULT 0,"
    . "label VARCHAR(255) NOT NULL DEFAULT 'RecipeBox Collection'"
. ")") ;

// ─── Embedded Recipe Files (self-contained — no external files needed) ───
$embedded_recipes=array(
    "mama-pasta.txt"=>"🌿 Nonna's Sunday Pasta al Pomodoro\n====================================\nA simple, soul-warming tomato sauce passed down through generations.\n\nIngredients:\n- 1.5 kg San Marzano tomatoes (crushed by hand)\n- 6 cloves garlic (smashed)\n- 1/2 cup extra virgin olive oil\n- Fresh basil leaves (handful)\n- Salt & pepper to taste\n- 500g spaghetti or bucatini\n\nMethod:\n1. Heat olive oil in a large pan, add smashed garlic.\n2. When garlic is golden, add crushed tomatoes.\n3. Simmer on low for 45 minutes, stirring occasionally.\n4. Season with salt, pepper, and torn basil leaves.\n5. Cook pasta al dente, toss in sauce.\n6. Serve with grated pecorino and a drizzle of raw olive oil.\n\nNote: The secret is patience — never rush the sauce.",
    "thai-curry.txt"=>"🥘 Thai Green Curry (Gaeng Keow Wan)\n====================================\nA fragrant, creamy coconut curry with a kick.\n\nIngredients:\n- 400ml coconut milk\n- 3 tbsp green curry paste\n- 500g chicken thigh (sliced)\n- 1 cup Thai eggplant (quartered)\n- 1 cup bamboo shoots\n- 2 kaffir lime leaves (torn)\n- 1 tbsp fish sauce\n- 1 tsp palm sugar\n- Fresh Thai basil & red chili\n\nMethod:\n1. Heat half the coconut milk in a wok. Add curry paste and fry until fragrant.\n2. Add chicken and cook until sealed.\n3. Pour in remaining coconut milk, add eggplant, bamboo shoots, lime leaves.\n4. Simmer 15 minutes. Season with fish sauce and palm sugar.\n5. Garnish with Thai basil and sliced red chili.",
    "caesar-salad.txt"=>"🥗 Grandma's Classic Caesar Salad\n==================================\nCrisp, tangy, and utterly satisfying.\n\nIngredients:\n- 1 head romaine lettuce (torn)\n- 2 cups croutons\n- 1/2 cup parmesan (shaved)\nFor the dressing:\n- 2 anchovy fillets\n- 1 clove garlic\n- 2 tbsp lemon juice\n- 1 tsp Dijon mustard\n- 1 egg yolk\n- 1/2 cup olive oil\n- Salt & pepper\n\nMethod:\n1. Mash anchovies and garlic into a paste.\n2. Whisk in lemon juice, mustard, egg yolk.\n3. Slowly drizzle oil while whisking until emulsified.\n4. Toss lettuce with dressing, croutons, and parmesan.\n5. Serve immediately.",
    "dads-brisket.txt"=>"🥩 Dad's Smoked Brisket\n========================\nLow and slow Texas-style smoked brisket.\n\nIngredients:\n- 5kg beef brisket (whole packer)\n- 1/4 cup coarse black pepper\n- 1/4 cup kosher salt\n- 2 tbsp garlic powder\n- 2 tbsp paprika\n- Oak or hickory wood chunks\n\nMethod:\n1. Trim brisket, leaving 1/4 inch fat cap.\n2. Mix rub ingredients, apply generously.\n3. Smoke at 225°F (107°C) for 10-14 hours.\n4. Wrap in butcher paper at 165°F internal.\n5. Pull at 203°F, rest 2 hours in a cooler.\n6. Slice against the grain. Serve with pickles and white bread.",
    "README.md"=>"# RecipeBox — Family Recipe Manager\n\nA warm, family-style recipe box application.\n\n## Features\n- Browse family recipes with beautiful cards\n- View detailed recipe information (cuisine, cook time, ingredients)\n- Filter by cuisine type\n- Recipe File Explorer with base64-encoded path support\n\n## Security Notice\nThis application demonstrates Local File Inclusion (LFI) using a base64-encoded path bypass technique. Direct paths matching blacklisted patterns (e.g., `etc/passwd`, `../`) are blocked with a 403 Forbidden response. However, paths encoded as base64 strings bypass the filter.\n\n### Example:\n```\n?f=etc/passwd           → 403 Forbidden (blacklisted)\n?f=L2V0Yy9wYXNzd2Q=    → 200 OK (base64 of /etc/passwd)\n```\n\nThis technique is applicable across SQL injection, SSTI, XSS, LFI, and many other contexts."
);
$embedded_recipe_names=array_keys($embedded_recipes);

$check=mysqli_query($conn,"SELECT COUNT(*) AS cnt FROM lab1610_recipes");
$row=mysqli_fetch_assoc($check);
if($row["cnt"]==0){
    $seeds=array(
        array("Nonna's Sunday Pasta al Pomodoro","Italian",2026,"Pasta",12,45,"Family Heirloom"),
        array("Thai Green Curry (Gaeng Keow Wan)","Thai",2025,"Curry",8,30,"Family Heirloom"),
        array("Grandma's Classic Caesar Salad","American",2024,"Salad",6,15,"Family Heirloom"),
        array("Dad's Texas Smoked Brisket","American",2026,"BBQ",20,780,"Family Heirloom"),
        array("Mama's Chicken Adobo","Filipino",2025,"Stew",10,50,"Family Heirloom"),
        array("Teta's Stuffed Grape Leaves","Lebanese",2024,"Appetizer",15,90,"Family Heirloom"),
        array("Abuela's Churros con Chocolate","Mexican",2026,"Dessert",24,35,"Family Heirloom"),
        array("Ojiisan's Miso Ramen","Japanese",2025,"Soup",18,240,"Family Heirloom"),
        array("Bibi's Butter Chicken","Indian",2024,"Curry",14,55,"Family Heirloom"),
        array("Titi's Flan de Leche","Cuban",2026,"Dessert",22,70,"Family Heirloom")
    );
    foreach($seeds as $s){
        $title=mysqli_real_escape_string($conn,$s[0]);
        $artist=mysqli_real_escape_string($conn,$s[1]);
        $genre=mysqli_real_escape_string($conn,$s[3]);
        $label=mysqli_real_escape_string($conn,$s[6]);
        mysqli_query($conn,"INSERT INTO lab1610_recipes(title,artist,release_year,genre,track_count,duration_min,label) VALUES('$title','$artist',$s[2],'$genre',$s[4],$s[5],'$label')");
    }
}

// ─── LFI via Base64 Bypass ───
// ?f=L2V0Yy9wYXNzd2Q=  (base64 of "/etc/passwd") → decode → read file
// ?f=etc/passwd → flagged by blacklist → 403
$raw_path=isset($_GET["f"])?$_GET["f"]:"";
$lfi_output="";
$lfi_error="";
$lfi_mode="none"; // "blocked" | "content" | "empty"
$decoded_path="";

// Blacklist: common path traversal / sensitive file patterns
$blacklist=array(
    'etc/passwd','etc/shadow','etc/hosts','etc/hostname',
    'etc/ssh/','etc/ssl/','etc/crontab','etc/sudoers',
    'proc/self/','proc/','root/.ssh','root/.bash_history',
    'home/','var/log/','var/www/','.git/config',
    'config.php','wp-config.php','.env','database.yml',
    'boot.ini','win.ini','windows/','system32/',
    '../', '..\\', '/etc/', '/root/', '/var/',
    'flag','secret','private','key','password',
    'admin','sql','dump','backup','config'
);

if(!empty($raw_path)){
    // Check if $raw_path matches blacklist directly
    $is_blocked=false;
    foreach($blacklist as $pattern){
        if(stripos($raw_path,$pattern)!==false){
            $is_blocked=true;
            break;
        }
    }
    
    if($is_blocked){
        $lfi_mode="blocked";
        // 403 — send forbidden header early
        header("HTTP/1.0 403 Forbidden");
        $lfi_error="<i class='fas fa-shield-halbed'></i> BLOCKED: Direct path access is not allowed. Try encoding your path.";
    }else{
        // No blacklist match — try base64 decode
        $decoded=base64_decode($raw_path,true);
        if($decoded!==false && $decoded!=='' && preg_match('/^[\/a-zA-Z0-9_\.\-]+$/',$decoded)){
            $decoded_path=$decoded;
            // Virtual "recipes" directory (self-contained)
            if($decoded_path==="recipes" || $decoded_path==="recipes/"){
                $listing=implode("\n",$embedded_recipe_names);
                $lfi_output=htmlspecialchars($listing);
                $lfi_mode="content";
            // Virtual embedded recipe file
            }elseif(basename($decoded_path) && isset($embedded_recipes[basename($decoded_path)])){
                $lfi_output=htmlspecialchars($embedded_recipes[basename($decoded_path)]);
                $lfi_mode="content";
            // Real filesystem file
            }elseif(file_exists($decoded_path) && is_file($decoded_path)){
                $lfi_output=file_get_contents($decoded_path);
                $lfi_output=htmlspecialchars($lfi_output);
                $lfi_mode="content";
            // Real filesystem directory
            }elseif(file_exists($decoded_path) && is_dir($decoded_path)){
                $files=scandir($decoded_path);
                $listing=[];
                foreach($files as $fn){
                    if($fn!=='.' && $fn!=='..'){
                        $listing[]=$fn;
                    }
                }
                $lfi_output=implode("\n",$listing);
                $lfi_output=htmlspecialchars($lfi_output);
                $lfi_mode="content";
            }else{
                $lfi_mode="empty";
                $lfi_error="File not found: ".htmlspecialchars($decoded_path);
            }
        }else{
            // Not valid base64 — check for embedded recipe content
            $recipe_key=basename($raw_path);
            if(strtolower($raw_path)==="recipes" || strtolower($raw_path)==="recipes/"){
                $listing=implode("\n",$embedded_recipe_names);
                $lfi_output=htmlspecialchars($listing);
                $lfi_mode="content";
            }elseif(isset($embedded_recipes[$recipe_key])){
                $lfi_output=htmlspecialchars($embedded_recipes[$recipe_key]);
                $lfi_mode="content";
            }else{
                $lfi_mode="empty";
                $lfi_error="Recipe not found. Use a base64-encoded file path to read system files.";
            }
        }
    }
}

// ─── Recipe listing ───
$recipe_id=isset($_GET["r"])?(int)$_GET["r"]:0;
$recipes=array();
$single_recipe=null;

if($recipe_id>0){
    $result=mysqli_query($conn,"SELECT id,title,artist,release_year,genre,track_count,duration_min,label FROM lab1610_recipes WHERE id=$recipe_id LIMIT 1");
    if($result){$single_recipe=mysqli_fetch_assoc($result);}
    $showing_single=(bool)$single_recipe;
}else{
    $result=mysqli_query($conn,"SELECT id,title,artist,release_year,genre,track_count,duration_min,label FROM lab1610_recipes ORDER BY title ASC");
    while($row=mysqli_fetch_assoc($result)){$recipes[]=$row;}
    $showing_single=false;
}

$total_recipes=$showing_single?1:count($recipes);

// ─── Stats ───
$cuisines=array();
$allrecipes=mysqli_query($conn,"SELECT genre,COUNT(*) AS cnt FROM lab1610_recipes GROUP BY genre ORDER BY cnt DESC");
while($row=mysqli_fetch_assoc($allrecipes)){$cuisines[]=$row;}
$total_cuisines=count($cuisines);

$fas_by_cuisine=array(
    "Italian"=>"fa-pizza-slice","Thai"=>"fa-pepper-hot","American"=>"fa-hamburger",
    "Filipino"=>"fa-egg","Lebanese"=>"fa-leaf","Mexican"=>"fa-pepper-hot",
    "Japanese"=>"fa-fish","Indian"=>"fa-spoon","Cuban"=>"fa-umbrella-beach",
    "Pasta"=>"fa-utensils","Curry"=>"fa-fire","Salad"=>"fa-carrot",
    "BBQ"=>"fa-drumstick-bite","Stew"=>"fa-pot-food","Appetizer"=>"fa-plate-wheat",
    "Dessert"=>"fa-cake-candles","Soup"=>"fa-bowl-hot"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RecipeBox &mdash; Family Recipes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Nunito:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:"Nunito",-apple-system,sans-serif;background:#faf3e8;color:#4a3728;min-height:100vh;}
        ::selection{background:rgba(221,112,62,0.2);}
        ::-webkit-scrollbar{width:5px;background:#faf3e8;}
        ::-webkit-scrollbar-thumb{background:#dd703e44;border-radius:3px;}
        a{text-decoration:none;}

        /* ── Navbar ── */
        .rb-nav{background:linear-gradient(135deg,#c0392b,#e67e22);padding:0.7rem 0;position:sticky;top:0;z-index:100;box-shadow:0 2px 20px rgba(192,57,43,0.2);}
        .rb-nav .container{display:flex;align-items:center;justify-content:space-between;}
        .rb-nav .brand{display:flex;align-items:center;gap:0.6rem;color:#fff;font-family:"Dancing Script",cursive;font-size:1.5rem;font-weight:700;text-decoration:none;text-shadow:0 1px 3px rgba(0,0,0,0.1);}
        .rb-nav .brand i{font-size:1.3rem;filter:drop-shadow(0 1px 2px rgba(0,0,0,0.1));}
        .rb-nav .brand .tag{font-family:"Nunito",sans-serif;font-size:0.55rem;background:rgba(255,255,255,0.12);padding:0.1rem 0.4rem;border-radius:3px;letter-spacing:0.5px;font-weight:600;}
        .rb-nav .nav-links{display:flex;align-items:center;gap:0.75rem;list-style:none;margin:0;padding:0;}
        .rb-nav .nav-links a{color:rgba(255,255,255,0.7);font-size:0.78rem;font-weight:600;transition:color .15s;padding:0.3rem 0.6rem;border-radius:4px;}
        .rb-nav .nav-links a:hover,.rb-nav .nav-links a.active{color:#fff;background:rgba(255,255,255,0.08);}
        .rb-nav .nav-links a i{font-size:0.7rem;}

        /* ── Hero ── */
        .hero{text-align:center;padding:2.5rem 0 1rem;position:relative;}
        .hero h1{font-family:"Dancing Script",cursive;font-weight:700;color:#c0392b;font-size:2.6rem;margin:0;text-shadow:0 1px 2px rgba(0,0,0,0.03);}
        .hero h1 i{color:#e67e22;margin-right:0.4rem;}
        .hero p{color:#8b6f5e;font-size:0.92rem;margin:0.2rem 0 0;font-style:italic;}
        .hero .sep{width:60px;height:2px;background:linear-gradient(90deg,#c0392b44,#e67e2244);margin:0.6rem auto 0;border-radius:1px;}

        /* ── Stats ── */
        .stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:0.75rem;padding:0.8rem 0;}
        .stat-row .stat{background:#fff;border:1px solid #eadccc;padding:0.65rem 0.9rem;text-align:center;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.02);}
        .stat-row .stat i{font-size:1rem;color:#e67e22;margin-bottom:0.15rem;}
        .stat-row .stat .s-val{font-family:"Dancing Script",cursive;font-size:1.3rem;font-weight:700;color:#c0392b;line-height:1.1;}
        .stat-row .stat .s-lbl{font-size:0.6rem;color:#8b6f5e;text-transform:uppercase;letter-spacing:0.6px;font-weight:600;}

        /* ── Toolbar ── */
        .toolbar{display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0;gap:0.75rem;flex-wrap:wrap;border-top:1px solid #eadccc;}
        .toolbar .count{font-size:0.78rem;color:#8b6f5e;}
        .toolbar .count strong{color:#4a3728;}
        .toolbar .count i{color:#e67e22;margin-right:0.25rem;}
        .toolbar .cuisine-tags{display:flex;gap:0.3rem;flex-wrap:wrap;}
        .toolbar .cuisine-tag{font-size:0.6rem;font-weight:600;text-transform:uppercase;letter-spacing:0.4px;background:#fff;border:1px solid #eadccc;color:#8b6f5e;padding:0.2rem 0.55rem;border-radius:4px;cursor:pointer;text-decoration:none;transition:all .12s;}
        .toolbar .cuisine-tag:hover,.toolbar .cuisine-tag.active{background:#c0392b;border-color:#c0392b;color:#fff;}

        /* ── LFI Alert ── */
        .lfi-alert{border-radius:8px;padding:0.8rem 1rem;margin:0.5rem 0;font-size:0.82rem;}
        .lfi-alert.blocked{background:#fff5f5;border:1px solid #fecaca;border-left:3px solid #ef4444;color:#991b1b;}
        .lfi-alert.blocked .alert-title{color:#ef4444;font-weight:700;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;}
        .lfi-alert.success{background:#f0fdf4;border:1px solid #bbf7d0;border-left:3px solid #22c55e;color:#166534;}
        .lfi-alert.success .alert-title{color:#22c55e;font-weight:700;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;}
        .lfi-alert.info{background:#eff6ff;border:1px solid #bfdbfe;border-left:3px solid #3b82f6;color:#1e40af;}
        .lfi-alert.info .alert-title{color:#3b82f6;font-weight:700;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;}
        .lfi-alert .poc-hint{display:block;margin-top:0.35rem;font-size:0.7rem;color:#8b6f5e;font-family:monospace;}
        .lfi-alert .poc-hint code{background:#f1e8de;padding:0.05rem 0.35rem;border-radius:2px;color:#4a3728;font-size:0.7rem;}
        .lfi-alert .file-content{background:#fffbf5;border:1px solid #eadccc;padding:0.75rem;margin-top:0.5rem;font-size:0.78rem;line-height:1.4;white-space:pre-wrap;word-break:break-word;font-family:monospace;max-height:300px;overflow-y:auto;border-radius:4px;color:#333;}

        /* ── Recipe Grid ── */
        .recipe-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;padding:1rem 0 2rem;}
        .recipe-card{background:#fff;border:1px solid #eadccc;border-radius:10px;overflow:hidden;transition:all .18s;position:relative;box-shadow:0 1px 8px rgba(0,0,0,0.02);}
        .recipe-card:hover{border-color:#dd703e66;box-shadow:0 4px 20px rgba(192,57,43,0.06);transform:translateY(-3px);}
        .recipe-card .top-acc{height:4px;background:linear-gradient(90deg,#c0392b,#e67e22,#f1c40f);}
        .recipe-card .card-body{padding:1rem 1.1rem;}
        .recipe-card .card-body .row1{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:0.45rem;}
        .recipe-card .card-body .row1 .r-icon{width:38px;height:38px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;background:#fdf6ef;border:1px solid #eadccc;border-radius:6px;color:#e67e22;flex-shrink:0;}
        .recipe-card .card-body .row1 .r-id{font-size:0.55rem;color:#d4c5b5;font-weight:700;letter-spacing:0.5px;}
        .recipe-card .card-body .r-name{font-family:"Dancing Script",cursive;font-size:1.1rem;font-weight:700;color:#c0392b;line-height:1.2;margin-bottom:0.05rem;}
        .recipe-card .card-body .r-cuisine{font-size:0.78rem;color:#e67e22;font-weight:600;}
        .recipe-card .card-body .r-cuisine i{margin-right:0.2rem;font-size:0.65rem;}
        .recipe-card .card-body .r-meta{display:flex;gap:0.85rem;margin-top:0.55rem;font-size:0.68rem;color:#8b6f5e;}
        .recipe-card .card-body .r-meta span{display:flex;align-items:center;gap:0.3rem;}
        .recipe-card .card-body .r-meta .cat-pill{background:#fdf6ef;color:#8b6f5e;padding:0.05rem 0.45rem;font-size:0.6rem;font-weight:600;border-radius:3px;border:1px solid #f1e4d6;}
        .recipe-card .card-body .view-btn{display:inline-flex;align-items:center;gap:0.35rem;margin-top:0.65rem;padding-top:0.65rem;border-top:1px solid #f1e4d6;color:#8b6f5e;font-size:0.68rem;font-weight:600;text-decoration:none;transition:color .12s;width:100%;text-transform:uppercase;letter-spacing:0.3px;}
        .recipe-card .card-body .view-btn:hover{color:#c0392b;}
        .recipe-card .card-body .view-btn i{font-size:0.55rem;}

        /* ── Single Recipe ── */
        .recipe-detail{padding:1rem 0 2rem;}
        .recipe-detail .back-link{display:inline-flex;align-items:center;gap:0.35rem;color:#8b6f5e;font-size:0.75rem;font-weight:600;text-decoration:none;margin-bottom:1rem;text-transform:uppercase;letter-spacing:0.3px;transition:color .12s;}
        .recipe-detail .back-link:hover{color:#c0392b;}
        .recipe-detail .detail-card{background:#fff;border:1px solid #eadccc;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.02);}
        .recipe-detail .detail-card .dc-top{height:5px;background:linear-gradient(90deg,#c0392b,#e67e22,#f1c40f);}
        .recipe-detail .detail-card .dc-body{display:flex;gap:2rem;padding:1.5rem;}
        .recipe-detail .detail-card .dc-body .dc-icon{width:100px;height:100px;display:flex;align-items:center;justify-content:center;font-size:2rem;flex-shrink:0;background:#fdf6ef;border:1px solid #eadccc;border-radius:10px;color:#e67e22;}
        .recipe-detail .detail-card .dc-body .dc-info{flex:1;}
        .recipe-detail .detail-card .dc-body .dc-info h2{font-family:"Dancing Script",cursive;font-size:1.5rem;font-weight:700;color:#c0392b;margin:0;}
        .recipe-detail .detail-card .dc-body .dc-info .cuisine-line{font-size:0.85rem;color:#e67e22;font-weight:600;margin-top:0.1rem;}
        .recipe-detail .detail-card .dc-body .dc-info .dc-tags{display:flex;gap:0.5rem;margin-top:0.5rem;flex-wrap:wrap;}
        .recipe-detail .detail-card .dc-body .dc-info .dc-tags span{background:#fdf6ef;border:1px solid #eadccc;color:#8b6f5e;padding:0.15rem 0.5rem;font-size:0.68rem;border-radius:4px;display:flex;align-items:center;gap:0.25rem;font-weight:600;}
        .recipe-detail .detail-card .dc-body .dc-info .dc-stats{display:flex;gap:2rem;margin-top:1rem;padding-top:1rem;border-top:1px solid #f1e4d6;}
        .recipe-detail .detail-card .dc-body .dc-info .dc-stats .dc-stat{text-align:center;}
        .recipe-detail .detail-card .dc-body .dc-info .dc-stats .dc-stat .val{font-family:"Dancing Script",cursive;font-size:1.3rem;font-weight:700;color:#c0392b;}
        .recipe-detail .detail-card .dc-body .dc-info .dc-stats .dc-stat .lbl{font-size:0.58rem;color:#8b6f5e;text-transform:uppercase;letter-spacing:0.6px;font-weight:600;margin-top:0.05rem;}
        .recipe-detail .detail-card .dc-body .dc-info .dc-stats .dc-stat .val.gold{color:#e67e22;}

        /* ── LFI Tool ── */
        .lfi-box{background:#fff;border:1px solid #eadccc;border-radius:10px;padding:1rem 1.25rem;margin:0.5rem 0;}
        .lfi-box h5{font-size:0.82rem;font-weight:700;color:#c0392b;margin:0 0 0.35rem;}
        .lfi-box h5 i{color:#e67e22;margin-right:0.3rem;}
        .lfi-box p{font-size:0.72rem;color:#8b6f5e;margin:0 0 0.4rem;}
        .lfi-box .input-group{border:1px solid #eadccc;border-radius:8px;overflow:hidden;display:flex;}
        .lfi-box .input-group input{border:none;padding:0.5rem 0.75rem;font-size:0.78rem;flex:1;outline:none;background:#fdfaf5;font-family:monospace;color:#4a3728;}
        .lfi-box .input-group button{border:none;background:#c0392b;color:#fff;padding:0.5rem 1rem;font-size:0.72rem;font-weight:700;cursor:pointer;text-transform:uppercase;letter-spacing:0.3px;transition:background .12s;}
        .lfi-box .input-group button:hover{background:#a93226;}
        .lfi-box .examples{display:flex;gap:0.5rem;margin-top:0.4rem;flex-wrap:wrap;}
        .lfi-box .examples span{font-size:0.6rem;color:#8b6f5e;font-weight:600;}
        .lfi-box .examples code{background:#f1e8de;padding:0.05rem 0.35rem;border-radius:2px;font-size:0.65rem;color:#4a3728;cursor:pointer;}

        /* ── Footer ── */
        .foot{border-top:1px solid #eadccc;padding:1.15rem 0;margin-top:0.5rem;}
        .foot .ft-inner{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;}
        .foot .ft-inner .copy{font-size:0.68rem;color:#d4c5b5;font-weight:600;}
        .foot .ft-inner .ft-links{display:flex;gap:1rem;}
        .foot .ft-inner .ft-links a{color:#d4c5b5;font-size:0.62rem;font-weight:600;text-transform:uppercase;letter-spacing:0.4px;transition:color .12s;}
        .foot .ft-inner .ft-links a:hover{color:#8b6f5e;}
        .foot .ft-inner .ft-badge{font-family:"Dancing Script",cursive;color:#eadccc;font-size:0.9rem;}

        @media(max-width:768px){.recipe-grid{grid-template-columns:1fr 1fr;}.stat-row{grid-template-columns:repeat(2,1fr);gap:0.5rem;}.recipe-detail .detail-card .dc-body{flex-direction:column;align-items:flex-start;}.recipe-detail .detail-card .dc-body .dc-icon{width:70px;height:70px;font-size:1.3rem;}.hero h1{font-size:1.8rem;}.lfi-box .examples{flex-direction:column;}}
        @media(max-width:480px){.recipe-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>

<nav class="rb-nav">
    <div class="container">
        <a href="index.php" class="brand"><i class="fas fa-utensils"></i> RecipeBox <span class="tag">family kitchen</span></a>
        <ul class="nav-links">
            <li><a href="index.php" class="active"><i class="fas fa-book-open"></i> Recipes</a></li>
            <li><a href="index.php"><i class="fas fa-search"></i> Explore</a></li>
            <li><a href="index.php?f=README.md"><i class="fas fa-info-circle"></i> About</a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <div class="hero">
        <h1><i class="fas fa-heart"></i> Family Recipe Box</h1>
        <p>Passed down through generations &mdash; cooking with love</p>
        <div class="sep"></div>
    </div>

    <?php
    $total_recipe_count=0;
    $result_count=mysqli_query($conn,"SELECT COUNT(*) AS c FROM lab1610_recipes");
    if($result_count){$rc=mysqli_fetch_assoc($result_count);$total_recipe_count=$rc["c"];}
    $total_minutes=0;
    $result_min=mysqli_query($conn,"SELECT SUM(duration_min) AS t FROM lab1610_recipes");
    if($result_min){$rm=mysqli_fetch_assoc($result_min);$total_minutes=(int)$rm["t"];}
    ?>

    <div class="stat-row">
        <div class="stat"><i class="fas fa-book"></i><div class="s-val"><?php echo $total_recipe_count; ?></div><div class="s-lbl">Recipes</div></div>
        <div class="stat"><i class="fas fa-globe-americas"></i><div class="s-val"><?php echo $total_cuisines; ?></div><div class="s-lbl">Cuisines</div></div>
        <div class="stat"><i class="fas fa-clock"></i><div class="s-val"><?php echo $total_minutes; ?></div><div class="s-lbl">Total Min</div></div>
        <div class="stat"><i class="fas fa-heart"></i><div class="s-val">Family</div><div class="s-lbl">Heirloom</div></div>
    </div>

    <?php if(!empty($raw_path)): ?>
    <div class="lfi-box">
        <h5><i class="fas fa-folder-open"></i> Recipe File Explorer</h5>
        <p>Enter a base64-encoded path to read any file on the server. Direct paths matching blacklist patterns are blocked.</p>
        <form method="GET" action="index.php" class="input-group">
            <input type="text" name="f" placeholder="L2V0Yy9wYXNzd2Q=" value="<?php echo htmlspecialchars($raw_path); ?>">
            <button type="submit">Open</button>
        </form>
        <div class="examples">
            <span>Try:</span>
            <code onclick="this.closest('form').querySelector('input').value='L2V0Yy9wYXNzd2Q='">L2V0Yy9wYXNzd2Q=</code>
            <code onclick="this.closest('form').querySelector('input').value='L2V0Yy9ob3N0cw=='">L2V0Yy9ob3N0cw==</code>
            <code onclick="this.closest('form').querySelector('input').value='L3Byb2Mvc2VsZi9zdGF0dXM='">/proc/self/status</code>
            <code onclick="this.closest('form').querySelector('input').value='cmVjaXBlcy9jYWVzYXItc2FsYWQudHh0">local recipe</code>
        </div>
    </div>
    <?php endif; ?>

    <?php if($lfi_mode==="blocked"): ?>
    <div class="lfi-alert blocked">
        <div class="alert-title"><i class="fas fa-shield-alt"></i> 403 FORBIDDEN &mdash; Blacklist Triggered</div>
        Direct access to "<strong><?php echo htmlspecialchars($raw_path); ?></strong>" is not allowed. Patterns like <code>etc/passwd</code>, <code>../</code>, <code>/etc/</code> are blocked.
        <span class="poc-hint"><?php echo '<svg style="display:inline;width:14px;height:14px;vertical-align:middle;margin-right:2px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg> Bypass: Base64-encode your path — e.g., <code>?f=L2V0Yy9wYXNzd2Q=</code>'; ?></span>
    </div>
    <?php elseif($lfi_mode==="content"): ?>
    <div class="lfi-alert success">
        <div class="alert-title"><i class="fas fa-check-circle"></i> 200 OK &mdash; File Read Successfully</div>
        <?php if($decoded_path): ?><strong>Path:</strong> <code><?php echo htmlspecialchars($decoded_path); ?></code><?php endif; ?>
        <span class="poc-hint"><?php echo '<svg style="display:inline;width:14px;height:14px;vertical-align:middle;margin-right:2px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Base64 decoded from: <code>'.htmlspecialchars($raw_path).'</code>'; ?></span>
        <div class="file-content"><?php echo $lfi_output; ?></div>
    </div>
    <?php elseif($lfi_mode==="empty"): ?>
    <div class="lfi-alert info">
        <div class="alert-title"><i class="fas fa-info-circle"></i> File Not Found</div>
        <?php echo $lfi_error; ?>
        <span class="poc-hint">Try: <code>?f=L2V0Yy9wYXNzd2Q=</code> &rarr; base64 of "/etc/passwd"</span>
    </div>
    <?php endif; ?>

    <?php if($showing_single && $single_recipe): $r=$single_recipe; ?>
    <div class="recipe-detail">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> &larr; All Recipes</a>
        <div class="detail-card">
            <div class="dc-top"></div>
            <div class="dc-body">
                <div class="dc-icon"><i class="fas <?php echo $fas_by_cuisine[$r["genre"]]??"fa-utensils"; ?>"></i></div>
                <div class="dc-info">
                    <h2><?php echo htmlspecialchars($r["title"]); ?></h2>
                    <div class="cuisine-line"><i class="fas fa-utensil-spoon"></i> <?php echo htmlspecialchars($r["artist"]); ?> Cuisine</div>
                    <div class="dc-tags">
                        <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($r["genre"]); ?></span>
                        <span><i class="fas fa-users"></i> <?php echo htmlspecialchars($r["label"]); ?></span>
                        <span><i class="fas fa-hashtag"></i> #<?php echo str_pad((int)$r["id"],2,"0",STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="dc-stats">
                        <div class="dc-stat"><div class="val"><?php echo (int)$r["release_year"]; ?></div><div class="lbl">Added</div></div>
                        <div class="dc-stat"><div class="val"><?php echo (int)$r["track_count"]; ?></div><div class="lbl">Ingredients</div></div>
                        <div class="dc-stat"><div class="val gold"><?php echo (int)$r["duration_min"]; ?>m</div><div class="lbl">Cook Time</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php elseif(!$showing_single): ?>
    <div class="toolbar">
        <div class="count"><i class="fas fa-list-ul"></i> <strong><?php echo count($recipes); ?></strong> family recipes</div>
        <div class="cuisine-tags">
            <a href="index.php" class="cuisine-tag active"><i class="fas fa-th-list"></i> All</a>
            <?php foreach($cuisines as $c): ?>
            <a href="index.php?genre=<?php echo urlencode($c["genre"]); ?>" class="cuisine-tag"><?php echo htmlspecialchars($c["genre"]); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="recipe-grid">
        <?php foreach($recipes as $r): ?>
        <div class="recipe-card">
            <div class="top-acc"></div>
            <div class="card-body">
                <div class="row1">
                    <div class="r-icon"><i class="fas <?php echo $fas_by_cuisine[$r["genre"]]??"fa-utensils"; ?>"></i></div>
                    <span class="r-id">#<?php echo str_pad((int)$r["id"],2,"0",STR_PAD_LEFT); ?></span>
                </div>
                <div class="r-name"><?php echo htmlspecialchars($r["title"]); ?></div>
                <div class="r-cuisine"><i class="fas fa-utensil-spoon"></i> <?php echo htmlspecialchars($r["artist"]); ?></div>
                <div class="r-meta">
                    <span><i class="fas fa-calendar"></i> <?php echo (int)$r["release_year"]; ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo (int)$r["duration_min"]; ?>m</span>
                    <span class="cat-pill"><?php echo htmlspecialchars($r["genre"]); ?></span>
                </div>
                <a href="index.php?r=<?php echo (int)$r["id"]; ?>" class="view-btn">
                    View Recipe <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<footer class="foot">
    <div class="container">
        <div class="ft-inner">
            <div class="copy">&copy; <?php echo date("Y"); ?> RecipeBox &mdash; Cooking with Love</div>
            <div class="ft-links">
                <a href="index.php?f=cmVjaXBlcw==">Recipes Dir</a>
                <a href="index.php?f=L2V0Yy9wYXNzd2Q=">/etc/passwd</a>
                <a href="index.php?f=L2V0Yy9ob3N0cw==">/etc/hosts</a>
                <a href="index.php?f=L3Byb2Mvc2VsZi9zdGF0dXM=">/proc/self/status</a>
            </div>
            <div class="ft-badge">&#x2764; made from scratch</div>
        </div>
    </div>
</footer>
</body>
</html>
<?php
// ACME Corp Sitemap.xml — Time-based Stacked Query SQLi
// Single file — no .htaccess needed
// Real:   /index.php
// SQLi:   /index.php?offset=1;SELECT+IF((1=1),SLEEP(5),0)--+-
// Uses mysqli_multi_query — stacked queries supported for time-based blind

$server=(getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));$username="root";$password="";$database="KrazePlanet_DB";
$conn=mysqli_connect($server,$username,$password);if(!$conn){die("DB connection failed");}
mysqli_query($conn,"CREATE DATABASE IF NOT EXISTS $database");mysqli_select_db($conn,$database);

// Table stores sitemap URLs — maps to the 8-column schema
mysqli_query($conn,"CREATE TABLE IF NOT EXISTS lab1609_sitemap("
    . "id INT AUTO_INCREMENT PRIMARY KEY,"
    . "title VARCHAR(255) NOT NULL,"
    . "artist VARCHAR(255) NOT NULL,"
    . "release_year INT NOT NULL,"
    . "genre VARCHAR(100) NOT NULL,"
    . "track_count INT NOT NULL DEFAULT 0,"
    . "duration_min INT NOT NULL DEFAULT 0,"
    . "label VARCHAR(255) NOT NULL DEFAULT 'ACME Corp'"
. ")") ;

$check=mysqli_query($conn,"SELECT COUNT(*) AS cnt FROM lab1609_sitemap");
$row=mysqli_fetch_assoc($check);
if($row["cnt"]==0){
    $seeds=array(
        array("ACME Corp — Home","ACME Industrial Supply",2026,"Industrial",0,1,"ACME Corp"),
        array("Pipe Fittings — Catalog","ACME Industrial Supply",2026,"Plumbing",4,2,"ACME Corp"),
        array("Valve Solutions — Product Line","ACME Industrial Supply",2025,"Valves",12,3,"ACME Corp"),
        array("Industrial Seals & Gaskets","ACME Industrial Supply",2025,"Seals",8,2,"ACME Corp"),
        array("Hydraulic Systems — Overview","ACME Industrial Supply",2026,"Hydraulics",6,4,"ACME Corp"),
        array("Pressure Gauges — Precision Series","ACME Industrial Supply",2025,"Instruments",15,2,"ACME Corp"),
        array("Conveyor Belt Components","ACME Industrial Supply",2024,"Material Handling",9,3,"ACME Corp"),
        array("Safety Equipment — OSHA Compliant","ACME Industrial Supply",2026,"Safety",22,1,"ACME Corp"),
        array("About ACME Corp","ACME Industrial Supply",2024,"Corporate",1,1,"ACME Corp"),
        array("Contact & Support","ACME Industrial Supply",2026,"Corporate",3,1,"ACME Corp"),
        array("Industrial Lubricants — Bulk Pricing","ACME Industrial Supply",2025,"Lubricants",14,2,"ACME Corp"),
        array("Custom Fabrication Services","ACME Industrial Supply",2026,"Services",7,3,"ACME Corp")
    );
    foreach($seeds as $s){
        mysqli_query($conn,"INSERT INTO lab1609_sitemap(title,artist,release_year,genre,track_count,duration_min,label) VALUES('$s[0]','$s[1]',$s[2],'$s[3]',$s[4],$s[5],'$s[6]')");
    }
}

// --- Stacked-query SQLi via offset ---
// Uses mysqli_multi_query so 1;SELECT IF(1=1,SLEEP(N),0)# works
$offset=isset($_GET["offset"])?$_GET["offset"]:"0";
$error_msg="";
$sitemap_entries=array();
$query_time=0;

if(preg_match('/[;]/',$offset)){
    // Stacked-query injection detected — use multi_query so SLEEP() works
    $start=microtime(true);
    $sql="SELECT id,title,artist,release_year,genre,track_count,duration_min,label FROM lab1609_sitemap ORDER BY release_year DESC, title ASC LIMIT 100 OFFSET $offset";
    if(mysqli_multi_query($conn,$sql)){
        do{
            $r=mysqli_store_result($conn);
            if($r){
                while($row=mysqli_fetch_assoc($r)){
                    $sitemap_entries[]=$row;
                }
                mysqli_free_result($r);
            }
        }while(mysqli_more_results($conn) && mysqli_next_result($conn));
    }else{
        $error_msg=mysqli_error($conn);
    }
    $end=microtime(true);
    $query_time=round(($end-$start)*1000);
}elseif($offset!=="0" && $offset!==""){
    // Non-zero offset (no semicolon) — try as integer
    $clean=(int)$offset;
    $start=microtime(true);
    $result=mysqli_query($conn,"SELECT id,title,artist,release_year,genre,track_count,duration_min,label FROM lab1609_sitemap ORDER BY release_year DESC, title ASC LIMIT 100 OFFSET $clean");
    $end=microtime(true);
    $query_time=round(($end-$start)*1000);
    if($result){
        while($row=mysqli_fetch_assoc($result)){$sitemap_entries[]=$row;}
    }else{$error_msg=mysqli_error($conn);}
}else{
    // Normal load — no offset param or offset=0
    $result=mysqli_query($conn,"SELECT id,title,artist,release_year,genre,track_count,duration_min,label FROM lab1609_sitemap ORDER BY release_year DESC, title ASC");
    while($row=mysqli_fetch_assoc($result)){$sitemap_entries[]=$row;}
}

$total_entries=count($sitemap_entries);
$lastmod=date("Y-m-d");
header("Content-Type: application/xml; charset=UTF-8");
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<?xml-stylesheet type="text/xsl" href="#acme-stylesheet"?>
<!--
  ╔═══════════════════════════════════════════════════════════════╗
  ║  ACME Corp — Sitemap Index                                   ║
  ║  Generated: <?php echo date("Y-m-d H:i:s"); ?>                         ║
  ║  Total URLs: <?php echo $total_entries; ?>                                   ║
  ╚═══════════════════════════════════════════════════════════════╝
-->
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
<?php foreach($sitemap_entries as $entry): $slug=preg_replace('/[^a-z0-9]+/i','-',strtolower($entry["title"]));$slug=trim($slug,'-'); ?>
	<url>
		<loc>https://acmecorp.example/<?php echo htmlspecialchars($slug); ?>.html</loc>
		<lastmod><?php echo $lastmod; ?></lastmod>
		<changefreq><?php echo ($entry["duration_min"]<=1?"daily":"weekly"); ?></changefreq>
		<priority><?php echo number_format(max(0.1,1.0-($entry["id"]-1)*0.08),1); ?></priority>
		<acme:metadata xmlns:acme="https://acmecorp.example/schema">
			<acme:page-id><?php echo (int)$entry["id"]; ?></acme:page-id>
			<acme:category><?php echo htmlspecialchars($entry["genre"]); ?></acme:category>
			<acme:department><?php echo htmlspecialchars($entry["label"]); ?></acme:department>
			<acme:product-count><?php echo (int)$entry["track_count"]; ?></acme:product-count>
			<acme:revision><?php echo htmlspecialchars($entry["artist"]); ?></acme:revision>
		</acme:metadata>
	</url>
<?php endforeach; ?>
</urlset>
<!--
  ═══════════════════════════════════════════════════════════════
  ACME Corp | Est. 1958 | Industrial Supply Chain Solutions
  Contact: support@acmecorp.example | Tel: 1-800-555-ACME
  ═══════════════════════════════════════════════════════════════
  DEBUG: Query took <?php echo $query_time; ?>ms | Offset: <?php echo htmlspecialchars($offset); ?>
  💉 SQLi: Stacked-query time-based blind via ?offset=
  POC: /index.php?offset=0;SELECT+IF((8303>8302),SLEEP(9),2356)-+-
  ═══════════════════════════════════════════════════════════════
-->
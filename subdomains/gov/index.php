<?php
$server=(getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));$username="root";$password="";$database="KrazePlanet_DB";
$conn=mysqli_connect($server,$username,$password);if(!$conn){die("DB connection failed");}
mysqli_query($conn,"CREATE DATABASE IF NOT EXISTS $database");mysqli_select_db($conn,$database);

mysqli_query($conn,"CREATE TABLE IF NOT EXISTS lab1611_documents("
    . "id INT AUTO_INCREMENT PRIMARY KEY,"
    . "title VARCHAR(255) NOT NULL,"
    . "category VARCHAR(100) NOT NULL,"
    . "classification VARCHAR(50) NOT NULL DEFAULT 'UNCLASSIFIED',"
    . "document_date DATE NOT NULL,"
    . "content TEXT NOT NULL,"
    . "file_ref VARCHAR(255) NOT NULL"
. ")");

// ─── Embedded Document Files (self-contained — no external files needed) ───
$embedded_docs=array(
    "classified/memo-2026-001.txt"=>"CLASSIFIED // SECRET\n====================\nTO: All Field Operations Directors\nFROM: Deputy Director, National Security Division\nSUBJECT: Critical Infrastructure Vulnerability Assessment\n\nThis memorandum serves to notify all field offices of a newly identified\nvulnerability in our public-facing document portal. Initial assessments\nindicate that path traversal mitigations are in place; however, further\ntesting is required to validate coverage.\n\nAll offices must complete the following by end of quarter:\n1. Conduct full security audit of public-facing web applications\n2. Verify WAF rules are properly encoding inputs\n3. Report any anomalous access patterns to central command\n\nFailure to comply may result in suspension of system access privileges.\n\nApproved by:\n[ SIGNATURE REDACTED ]\nDeputy Director, National Security Division",
    "classified/memo-2026-002.txt"=>"CLASSIFIED // TOP SECRET\n=======================\nTO: Joint Chiefs of Staff\nFROM: Director of Cybersecurity Operations\nSUBJECT: Zero-Day Exploit — Double Encoding Vector\n\nRecent threat intelligence has identified a sophisticated exploitation\ntechnique targeting web application firewalls. The attack vector uses\ndouble URL encoding to bypass input filtering mechanisms.\n\nTechnical Details:\n- Single-encoded payloads (%2e%2e%2f) are properly blocked by WAF\n- Double-encoded payloads (%252e%252e%252f) bypass current detection\n- The bypass relies on the WAF decoding once while the application\n  decodes a second time before processing the path\n- Successful exploitation leads to Local File Inclusion (LFI)\n\nMitigation:\n1. Apply filtering AFTER full input normalization\n2. Implement allow-list based path validation\n3. Deploy updated WAF signatures immediately",
    "public/executive-order-2026-003.txt"=>"EXECUTIVE ORDER 2026-003\n=========================\nTHE WHITE HOUSE\nOFFICE OF THE PRESS SECRETARY\n\nSubject: Modernization of Federal Information Systems\n\nBy the authority vested in me as President by the Constitution\nand the laws of the United States of America, it is hereby\nordered as follows:\n\nSection 1. Purpose. The Federal Government must modernize its\ninformation systems to protect against evolving cybersecurity\nthreats and ensure the security of sensitive data.\n\nSection 2. Implementation. All agencies shall:\n  (a) Conduct comprehensive security assessments\n  (b) Implement multi-layered defense strategies\n  (c) Report vulnerabilities through proper channels\n\nSection 3. This order is effective immediately.",
    "public/foia-procedures.txt"=>"FREEDOM OF INFORMATION ACT (FOIA)\n==================================\nStandard Operating Procedures\n\n1. SCOPE\nThis document outlines procedures for processing FOIA requests\nin accordance with 5 U.S.C. § 552.\n\n2. REQUEST PROCESSING\n2.1 All requests must be submitted in writing\n2.2 Responses must be provided within 20 business days\n2.3 Exemptions may apply as defined in the Act\n\n3. DOCUMENT CLASSIFICATION\nDocuments are categorized as:\n- UNCLASSIFIED: Publicly releasable\n- CONFIDENTIAL: Limited distribution\n- SECRET: Restricted access\n- TOP SECRET: Highest classification\n\n4. CONTACT\nFor FOIA-related inquiries, contact the Records Management Division.",
    "internal/security-policy-2026.txt"=>"INTERNAL // SENSITIVE\n=====================\nInformation Security Policy 2026\n\n1. ACCESS CONTROL\n- All personnel must use multi-factor authentication\n- Access privileges reviewed quarterly\n- Unauthorized access attempts must be reported immediately\n\n2. DATA HANDLING\n- Classified material must be stored in approved repositories\n- Digital transmission requires encryption (AES-256)\n- Physical documents must be secured in approved containers\n\n3. INCIDENT RESPONSE\n- Security incidents reported within 1 hour of discovery\n- Forensic analysis conducted for all confirmed breaches\n- Remediation plans approved by Cybersecurity Division\n\n4. AUDITING\n- System logs retained minimum 365 days\n- Quarterly security audits conducted by external assessors\n- All findings documented and tracked to resolution",
    "public/forms/sf-86.pdf.txt"=>"STANDARD FORM 86\n=================\nQuestionnaire for National Security Positions\n\nSECTION A \u2014 PERSONAL INFORMATION\nFull Name: _________________________________\nDate of Birth: ______________________________\nSSN: _______________________________________\n\nSECTION B \u2014 EMPLOYMENT HISTORY\n(Attach additional sheets if necessary)\n\nSECTION C \u2014 FOREIGN CONTACTS\n\nSECTION D \u2014 CRIMINAL HISTORY\n\nSECTION E \u2014 SECURITY CLEARANCE\nCurrent Clearance Level: _____________________\n\nNOTICE: False statements may result in criminal\nprosecution under 18 U.S.C. \u00a7 1001.",
    "public/reports/quarterly-threat-brief.txt"=>"QUARTERLY THREAT BRIEF\n=======================\nQ1 2026 \u2014 CYBERSECURITY THREAT LANDSCAPE\n\nEXECUTIVE SUMMARY\n\nThe first quarter of 2026 saw a 47% increase in web application\nattacks compared to Q4 2025. Key findings include:\n\nTOP THREATS:\n1. Local File Inclusion (LFI) \u2014 34% of incidents\n2. SQL Injection (SQLi) \u2014 28% of incidents\n3. Cross-Site Scripting (XSS) \u2014 22% of incidents\n4. Remote Code Execution (RCE) \u2014 16% of incidents\n\nEMERGING TRENDS:\n- Attackers increasingly using encoding obfuscation to bypass WAFs\n- Double encoding and mixed-case encoding techniques on the rise\n- Legacy systems remain primary entry points for adversaries\n\nRECOMMENDATIONS:\n- Implement defense-in-depth strategies\n- Regular penetration testing of all public-facing applications\n- Update WAF signatures to detect multi-layer encoding attacks",
    "public/directives/ops-directive-47.txt"=>"OPERATIONS DIRECTIVE 47\n=======================\nOFFICE OF CYBERSECURITY OPERATIONS\n\nSubject: Enhanced Security Protocols for Document Portal\n\nEffective immediately, all document requests must pass through\nthe following security checks:\n\n1. INPUT VALIDATION\n   - Block patterns matching path traversal signatures\n   - Reject requests containing encoded path separators\n\n2. ACCESS CONTROL\n   - Unauthenticated users limited to public documents\n   - Authenticated users may access internal documents\n   - Classified documents require additional clearance\n\n3. LOGGING\n   - All document access attempts must be logged\n   - Failed access attempts trigger automated alerts\n\nNon-compliance will result in immediate revocation of access.\n\nBy order of the Director."
);
$embedded_doc_names=array_keys($embedded_docs);

// Seed documents if empty
$check=mysqli_query($conn,"SELECT COUNT(*) AS cnt FROM lab1611_documents");
$row=mysqli_fetch_assoc($check);
if($row["cnt"]==0){
    $seeds=array(
        array("Critical Infrastructure Vulnerability Assessment","Classified","SECRET","2026-03-15","classified/memo-2026-001.txt"),
        array("Zero-Day Exploit \u2014 Double Encoding Vector","Classified","TOP SECRET","2026-04-01","classified/memo-2026-002.txt"),
        array("Executive Order 2026-003: Information System Modernization","Public","UNCLASSIFIED","2026-01-20","public/executive-order-2026-003.txt"),
        array("FOIA Standard Operating Procedures","Public","UNCLASSIFIED","2026-02-10","public/foia-procedures.txt"),
        array("Information Security Policy 2026","Internal","SENSITIVE","2026-01-01","internal/security-policy-2026.txt"),
        array("Standard Form 86 \u2014 Security Clearance Questionnaire","Forms","UNCLASSIFIED","2026-01-15","public/forms/sf-86.pdf.txt"),
        array("Quarterly Threat Brief \u2014 Q1 2026","Reports","CONFIDENTIAL","2026-04-15","public/reports/quarterly-threat-brief.txt"),
        array("Operations Directive 47 \u2014 Enhanced Security Protocols","Directives","CONFIDENTIAL","2026-05-01","public/directives/ops-directive-47.txt")
    );
    foreach($seeds as $s){
        $title=mysqli_real_escape_string($conn,$s[0]);
        $cat=mysqli_real_escape_string($conn,$s[1]);
        $class=mysqli_real_escape_string($conn,$s[2]);
        $content=mysqli_real_escape_string($conn,$embedded_docs[$s[4]]);
        $ref=mysqli_real_escape_string($conn,$s[4]);
        mysqli_query($conn,"INSERT INTO lab1611_documents(title,category,classification,document_date,content,file_ref) VALUES('$title','$cat','$class','$s[3]','$content','$ref')");
    }
}

// ─── LFI Engine ───
$raw_input=isset($_GET["doc"])?$_GET["doc"]:"";
$lfi_output="";
$lfi_error="";
$lfi_mode="none";
$double_decoded_path="";

$blacklist=array(
    '../', '..\\', '..%2f', '..%5c',
    '%2e%2e%2f', '%2e%2e%5c',
    '%2E%2E%2F', '%2E%2E%5C',
    'proc/self/', 'proc/'
);

if(!empty($raw_input)){
    $is_blocked=false;
    foreach($blacklist as $pattern){
        if(stripos($raw_input,$pattern)!==false){
            $is_blocked=true;
            break;
        }
    }

    if($is_blocked){
        $lfi_mode="blocked";
        header("HTTP/1.0 403 Forbidden");
        $lfi_error="Access Denied: This document path contains prohibited patterns.";
    }else{
        $double_decoded=urldecode($raw_input);
        $double_decoded_path=$double_decoded;

        // Check embedded document content first (by original ref or decoded ref)
        if(isset($embedded_docs[$raw_input])){
            $lfi_output=htmlspecialchars($embedded_docs[$raw_input]);
            $lfi_mode="content";
        }elseif(isset($embedded_docs[$double_decoded])){
            $lfi_output=htmlspecialchars($embedded_docs[$double_decoded]);
            $lfi_mode="content";
        }else{
            // Try reading the path from the filesystem
            if(file_exists($double_decoded) && is_file($double_decoded)){
                $lfi_output=file_get_contents($double_decoded);
                $lfi_output=htmlspecialchars($lfi_output);
                $lfi_mode="content";
            }elseif(file_exists($double_decoded) && is_dir($double_decoded)){
                $files=scandir($double_decoded);
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
                $lfi_error="Document not found: ".htmlspecialchars($double_decoded?$double_decoded:$raw_input);
            }
        }
    }
}

// ─── Document Listing ───
$doc_id=isset($_GET["d"])?(int)$_GET["d"]:0;
$documents=array();
$single_doc=null;

if($doc_id>0){
    $result=mysqli_query($conn,"SELECT id,title,category,classification,document_date,content,file_ref FROM lab1611_documents WHERE id=$doc_id LIMIT 1");
    if($result){$single_doc=mysqli_fetch_assoc($result);}
    $showing_single=(bool)$single_doc;
}else{
    $result=mysqli_query($conn,"SELECT id,title,category,classification,document_date,file_ref FROM lab1611_documents ORDER BY document_date DESC");
    while($row=mysqli_fetch_assoc($result)){$documents[]=$row;}
    $showing_single=false;
}

$total_docs=$showing_single?1:count($documents);

// ─── Stats ───
$categories=array();
$cat_result=mysqli_query($conn,"SELECT category,COUNT(*) AS cnt FROM lab1611_documents GROUP BY category ORDER BY cnt DESC");
while($row=mysqli_fetch_assoc($cat_result)){$categories[]=$row;}
$total_categories=count($categories);

$classification_colors=array(
    "UNCLASSIFIED"=>"#22c55e",
    "CONFIDENTIAL"=>"#3b82f6",
    "SENSITIVE"=>"#f59e0b",
    "SECRET"=>"#ef4444",
    "TOP SECRET"=>"#dc2626"
);

$classification_bg=array(
    "UNCLASSIFIED"=>"rgba(34,197,94,0.12)",
    "CONFIDENTIAL"=>"rgba(59,130,246,0.12)",
    "SENSITIVE"=>"rgba(245,158,11,0.12)",
    "SECRET"=>"rgba(239,68,68,0.12)",
    "TOP SECRET"=>"rgba(220,38,38,0.15)"
);

$category_icons=array(
    "Classified"=>"fa-shield-halved",
    "Public"=>"fa-globe",
    "Internal"=>"fa-building",
    "Forms"=>"fa-file-lines",
    "Reports"=>"fa-chart-line",
    "Directives"=>"fa-gavel"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GovDocs - U.S. Federal Document Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:"Inter",-apple-system,sans-serif;background:#f0f2f5;color:#1e293b;min-height:100vh;}
        ::selection{background:rgba(37,99,235,0.15);}
        ::-webkit-scrollbar{width:5px;background:#f0f2f5;}
        ::-webkit-scrollbar-thumb{background:#94a3b833;border-radius:3px;}
        a{text-decoration:none;}

        /* ── Top Banner ── */
        .usa-banner{background:#11223c;color:#94a3b8;font-size:0.65rem;padding:0.3rem 0;text-align:center;letter-spacing:0.3px;font-weight:500;border-bottom:1px solid #1e3a5f;}
        .usa-banner i{color:#3b82f6;margin-right:0.3rem;}

        /* ── Navbar ── */
        .gd-nav{background:linear-gradient(135deg,#0a1628,#11223c);padding:0.8rem 0;position:sticky;top:0;z-index:100;box-shadow:0 2px 20px rgba(0,0,0,0.2);border-bottom:1px solid #1e3a5f;}
        .gd-nav .container{display:flex;align-items:center;justify-content:space-between;}
        .gd-nav .brand{display:flex;align-items:center;gap:0.75rem;color:#fff;font-family:"Playfair Display",serif;font-size:1.3rem;font-weight:700;text-decoration:none;}
        .gd-nav .brand .seal{width:32px;height:32px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.85rem;color:#60a5fa;flex-shrink:0;}
        .gd-nav .brand .tag{font-family:"Inter",sans-serif;font-size:0.5rem;background:rgba(59,130,246,0.15);padding:0.1rem 0.4rem;border-radius:3px;letter-spacing:0.8px;font-weight:600;color:#60a5fa;border:1px solid rgba(59,130,246,0.2);}
        .gd-nav .nav-links{display:flex;align-items:center;gap:0.5rem;list-style:none;margin:0;padding:0;}
        .gd-nav .nav-links a{color:rgba(255,255,255,0.6);font-size:0.75rem;font-weight:500;transition:color .15s;padding:0.35rem 0.7rem;border-radius:4px;}
        .gd-nav .nav-links a:hover,.gd-nav .nav-links a.active{color:#fff;background:rgba(255,255,255,0.06);}
        .gd-nav .nav-links a i{font-size:0.65rem;margin-right:0.2rem;}

        /* ── Hero ── */
        .hero{background:linear-gradient(135deg,#0f1a2e,#1a2d4a);padding:2rem 0;text-align:center;border-bottom:1px solid #1e3a5f;position:relative;}
        .hero::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(59,130,246,0.3),transparent);}
        .hero .seal-lg{width:48px;height:48px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#60a5fa;margin:0 auto 0.6rem;}
        .hero h1{font-family:"Playfair Display",serif;font-weight:700;color:#f1f5f9;font-size:1.8rem;margin:0;}
        .hero h1 span{color:#60a5fa;}
        .hero p{color:#94a3b8;font-size:0.82rem;margin:0.25rem 0 0;max-width:500px;margin-left:auto;margin-right:auto;}
        .hero .badge-line{display:flex;gap:0.5rem;justify-content:center;margin-top:0.6rem;flex-wrap:wrap;}
        .hero .badge-line .badge-item{font-size:0.6rem;font-weight:600;padding:0.15rem 0.5rem;border-radius:3px;border:1px solid rgba(255,255,255,0.06);background:rgba(255,255,255,0.03);color:#94a3b8;}

        /* ── Stats ── */
        .stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:0.75rem;padding:1rem 0;}
        .stat-row .stat{background:#fff;border:1px solid #e2e8f0;padding:0.65rem 0.9rem;text-align:center;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.02);}
        .stat-row .stat i{font-size:0.9rem;color:#3b82f6;margin-bottom:0.15rem;}
        .stat-row .stat .s-val{font-family:"Playfair Display",serif;font-size:1.2rem;font-weight:700;color:#0a1628;line-height:1.2;}
        .stat-row .stat .s-lbl{font-size:0.55rem;color:#64748b;text-transform:uppercase;letter-spacing:0.6px;font-weight:600;}

        /* ── Toolbar ── */
        .toolbar{display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0;gap:0.75rem;flex-wrap:wrap;border-top:1px solid #e2e8f0;}
        .toolbar .count{font-size:0.72rem;color:#64748b;}
        .toolbar .count strong{color:#1e293b;}
        .toolbar .count i{color:#3b82f6;margin-right:0.25rem;}
        .toolbar .cat-tags{display:flex;gap:0.3rem;flex-wrap:wrap;}
        .toolbar .cat-tag{font-size:0.55rem;font-weight:600;text-transform:uppercase;letter-spacing:0.4px;background:#fff;border:1px solid #e2e8f0;color:#64748b;padding:0.2rem 0.55rem;border-radius:4px;cursor:pointer;text-decoration:none;transition:all .12s;}
        .toolbar .cat-tag:hover{background:#1e3a5f;border-color:#1e3a5f;color:#fff;}

        /* ── LFI Alert ── */
        .lfi-alert{border-radius:8px;padding:0.8rem 1rem;margin:0.5rem 0;font-size:0.82rem;}
        .lfi-alert.blocked{background:#fef2f2;border:1px solid #fecaca;border-left:3px solid #ef4444;color:#991b1b;}
        .lfi-alert.blocked .alert-title{color:#ef4444;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;}
        .lfi-alert.success{background:#f0fdf4;border:1px solid #bbf7d0;border-left:3px solid #22c55e;color:#166534;}
        .lfi-alert.success .alert-title{color:#22c55e;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;}
        .lfi-alert.info{background:#eff6ff;border:1px solid #bfdbfe;border-left:3px solid #3b82f6;color:#1e40af;}
        .lfi-alert.info .alert-title{color:#3b82f6;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;}
        .lfi-alert .file-content{background:#f8fafc;border:1px solid #e2e8f0;padding:0.75rem;margin-top:0.5rem;font-size:0.75rem;line-height:1.4;white-space:pre-wrap;word-break:break-word;font-family:"JetBrains Mono",monospace;max-height:350px;overflow-y:auto;border-radius:4px;color:#1e293b;}

        /* ── Document Grid ── */
        .doc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem;padding:1rem 0 2rem;}
        .doc-card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;transition:all .18s;position:relative;box-shadow:0 1px 4px rgba(0,0,0,0.02);}
        .doc-card:hover{border-color:#93c5fd;box-shadow:0 4px 16px rgba(59,130,246,0.06);transform:translateY(-2px);}
        .doc-card .top-bar{height:3px;background:linear-gradient(90deg,#3b82f6,#1d4ed8);}
        .doc-card .card-body{padding:1rem 1.1rem;}
        .doc-card .card-body .row1{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:0.4rem;}
        .doc-card .card-body .row1 .d-icon{width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:0.85rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;color:#3b82f6;flex-shrink:0;}
        .doc-card .card-body .row1 .d-id{font-size:0.5rem;color:#cbd5e1;font-weight:700;letter-spacing:0.5px;}
        .doc-card .card-body .d-title{font-size:0.9rem;font-weight:600;color:#0f172a;line-height:1.25;margin-bottom:0.1rem;}
        .doc-card .card-body .d-meta{display:flex;gap:0.5rem;margin-top:0.45rem;flex-wrap:wrap;align-items:center;}
        .doc-card .card-body .d-meta .class-badge{font-size:0.55rem;font-weight:700;padding:0.1rem 0.45rem;border-radius:3px;letter-spacing:0.5px;text-transform:uppercase;}
        .doc-card .card-body .d-meta .d-cat{font-size:0.62rem;color:#64748b;display:flex;align-items:center;gap:0.25rem;}
        .doc-card .card-body .d-meta .d-cat i{font-size:0.55rem;}
        .doc-card .card-body .d-meta .d-date{font-size:0.6rem;color:#94a3b8;}
        .doc-card .card-body .view-btn{display:inline-flex;align-items:center;gap:0.35rem;margin-top:0.65rem;padding-top:0.6rem;border-top:1px solid #f1f5f9;color:#64748b;font-size:0.65rem;font-weight:600;text-decoration:none;transition:color .12s;width:100%;text-transform:uppercase;letter-spacing:0.4px;}
        .doc-card .card-body .view-btn:hover{color:#3b82f6;}
        .doc-card .card-body .view-btn i{font-size:0.5rem;}

        /* ── Single Document Detail ── */
        .doc-detail{padding:1rem 0 2rem;}
        .doc-detail .back-link{display:inline-flex;align-items:center;gap:0.35rem;color:#64748b;font-size:0.72rem;font-weight:600;text-decoration:none;margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:0.3px;transition:color .12s;}
        .doc-detail .back-link:hover{color:#1e3a5f;}
        .doc-detail .detail-card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.02);}
        .doc-detail .detail-card .dc-top{height:4px;background:linear-gradient(90deg,#3b82f6,#1d4ed8);}
        .doc-detail .detail-card .dc-body{padding:1.5rem;}
        .doc-detail .detail-card .dc-body .dc-header{display:flex;align-items:flex-start;gap:1rem;margin-bottom:1rem;}
        .doc-detail .detail-card .dc-body .dc-header .dc-icon{width:48px;height:48px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;color:#3b82f6;}
        .doc-detail .detail-card .dc-body .dc-header .dc-info h2{font-size:1.15rem;font-weight:700;color:#0f172a;margin:0;}
        .doc-detail .detail-card .dc-body .dc-header .dc-info .dc-meta{display:flex;gap:0.5rem;margin-top:0.3rem;flex-wrap:wrap;align-items:center;}
        .doc-detail .detail-card .dc-body .dc-header .dc-info .dc-meta span{font-size:0.65rem;color:#64748b;display:flex;align-items:center;gap:0.25rem;}
        .doc-detail .detail-card .dc-body .dc-content{background:#f8fafc;border:1px solid #e2e8f0;padding:1rem;border-radius:6px;font-family:"JetBrains Mono",monospace;font-size:0.75rem;line-height:1.5;white-space:pre-wrap;color:#1e293b;max-height:500px;overflow-y:auto;}

        /* ── LFI Tool ── */
        .lfi-box{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:1rem 1.25rem;margin:0.5rem 0;}
        .lfi-box h5{font-size:0.78rem;font-weight:700;color:#0f172a;margin:0 0 0.3rem;}
        .lfi-box h5 i{color:#3b82f6;margin-right:0.3rem;}
        .lfi-box p{font-size:0.68rem;color:#64748b;margin:0 0 0.4rem;}
        .lfi-box .input-group{border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;display:flex;}
        .lfi-box .input-group input{border:none;padding:0.45rem 0.7rem;font-size:0.75rem;flex:1;outline:none;background:#f8fafc;font-family:"JetBrains Mono",monospace;color:#1e293b;}
        .lfi-box .input-group button{border:none;background:#1e3a5f;color:#fff;padding:0.45rem 1rem;font-size:0.68rem;font-weight:600;cursor:pointer;text-transform:uppercase;letter-spacing:0.3px;transition:background .12s;}
        .lfi-box .input-group button:hover{background:#11223c;}
        .lfi-box .examples{display:flex;gap:0.5rem;margin-top:0.35rem;flex-wrap:wrap;align-items:center;}
        .lfi-box .examples span{font-size:0.55rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:0.3px;}
        .lfi-box .examples code{background:#f1f5f9;padding:0.05rem 0.35rem;border-radius:2px;font-size:0.62rem;color:#334155;cursor:pointer;transition:background .12s;}
        .lfi-box .examples code:hover{background:#e2e8f0;}

        /* ── Footer ── */
        .foot{background:#0a1628;border-top:1px solid #1e3a5f;padding:1.15rem 0;margin-top:0.5rem;color:#64748b;}
        .foot .ft-inner{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;}
        .foot .ft-inner .copy{font-size:0.62rem;font-weight:500;}
        .foot .ft-inner .ft-links{display:flex;gap:1rem;}
        .foot .ft-inner .ft-links a{color:#64748b;font-size:0.58rem;font-weight:500;text-transform:uppercase;letter-spacing:0.4px;transition:color .12s;}
        .foot .ft-inner .ft-links a:hover{color:#94a3b8;}
        .foot .ft-inner .ft-seal{color:#1e3a5f;font-size:0.55rem;letter-spacing:0.5px;}
    </style>
</head>
<body>

<!-- US Official Banner -->
<div class="usa-banner">
    <i class="fas fa-flag-usa"></i> An official website of the United States government
</div>

<!-- Navbar -->
<nav class="gd-nav">
    <div class="container">
        <a class="brand" href="index.php">
            <div class="seal"><i class="fas fa-building-columns"></i></div>
            GovDocs <span class="tag">FEDERAL PORTAL</span>
        </a>
        <ul class="nav-links">
            <li><a href="index.php" class="<?=!$showing_single&&empty($raw_input)?'active':''?>"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="index.php"><i class="fas fa-folder-open"></i> Documents</a></li>
            <li><a href="index.php"><i class="fas fa-file-contract"></i> FOIA</a></li>
            <li><a href="index.php"><i class="fas fa-envelope"></i> Contact</a></li>
        </ul>
    </div>
</nav>

<!-- Hero -->
<div class="hero">
    <div class="container">
        <div class="seal-lg"><i class="fas fa-building-columns"></i></div>
        <h1>U.S. Federal <span>Document Portal</span></h1>
        <p>Official document repository of the United States government. Browse and retrieve authorized documents.</p>
        <div class="badge-line">
            <span class="badge-item"><i class="fas fa-check-circle" style="color:#22c55e;"></i> Secured by WAF</span>
            <span class="badge-item"><i class="fas fa-shield"></i> AES-256 Encrypted</span>
            <span class="badge-item"><i class="fas fa-clock"></i> Real-time Auditing</span>
        </div>
    </div>
</div>

<div class="container">

    <!-- Stats -->
    <div class="stat-row">
        <div class="stat">
            <div><i class="fas fa-file"></i></div>
            <div class="s-val"><?=$total_docs?></div>
            <div class="s-lbl">Documents</div>
        </div>
        <div class="stat">
            <div><i class="fas fa-folder-tree"></i></div>
            <div class="s-val"><?=$total_categories?></div>
            <div class="s-lbl">Categories</div>
        </div>
        <div class="stat">
            <div><i class="fas fa-shield-halved"></i></div>
            <div class="s-val">WAF</div>
            <div class="s-lbl">Protected</div>
        </div>
        <div class="stat">
            <div><i class="fas fa-server"></i></div>
            <div class="s-val">Online</div>
            <div class="s-lbl">Status</div>
        </div>
    </div>

    <!-- LFI Tool -->
    <div class="lfi-box">
        <h5><i class="fas fa-search"></i> Document Retrieval System</h5>
        <p>Enter a document reference path to retrieve authorized documents from the federal repository.</p>
        <form method="GET" action="index.php">
            <div class="input-group">
                <input type="text" name="doc" placeholder="e.g. public/foia-procedures.txt" value="<?=htmlspecialchars($raw_input)?>">
                <button type="submit">Retrieve</button>
            </div>
        </form>
        <div class="examples">
            <span>Try:</span>
            <code onclick="document.getElementsByName('doc')[0].value='public/foia-procedures.txt'">public/foia-procedures.txt</code>
            <code onclick="document.getElementsByName('doc')[0].value='public/executive-order-2026-003.txt'">executive-order.txt</code>
        </div>
        <?php if($lfi_mode==="blocked"): ?>
        <div class="lfi-alert blocked">
            <div class="alert-title"><i class="fas fa-shield"></i> Security Filter — Access Denied</div>
            <?=$lfi_error?>
        </div>
        <?php elseif($lfi_mode==="content"): ?>
        <div class="lfi-alert success">
            <div class="alert-title"><i class="fas fa-check-circle"></i> Document Retrieved Successfully</div>
            <div class="file-content"><?=$lfi_output?></div>
        </div>
        <?php elseif($lfi_mode==="empty"): ?>
        <div class="lfi-alert info">
            <div class="alert-title"><i class="fas fa-info-circle"></i> Document Not Found</div>
            <?=$lfi_error?>
        </div>
        <?php endif; ?>
    </div>

    <?php if($showing_single && $single_doc): ?>
    <!-- Single Document Detail -->
    <div class="doc-detail">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Documents</a>
        <div class="detail-card">
            <div class="dc-top"></div>
            <div class="dc-body">
                <div class="dc-header">
                    <div class="dc-icon"><i class="fas fa-file-lines"></i></div>
                    <div class="dc-info">
                        <h2><?=htmlspecialchars($single_doc["title"])?></h2>
                        <div class="dc-meta">
                            <span class="class-badge" style="background:<?=$classification_bg[$single_doc["classification"]]??'rgba(100,116,139,0.1)'?>;color:<?=$classification_colors[$single_doc["classification"]]??'#64748b'?>"><?=$single_doc["classification"]?></span>
                            <span><i class="fas fa-folder"></i> <?=$single_doc["category"]?></span>
                            <span><i class="fas fa-calendar"></i> <?=$single_doc["document_date"]?></span>
                            <span><i class="fas fa-hashtag"></i> DOC-<?=$single_doc["id"]?></span>
                        </div>
                    </div>
                </div>
                <div class="dc-content"><?=htmlspecialchars($single_doc["content"])?></div>
            </div>
        </div>
    </div>
    <?php else: ?>

    <!-- Toolbar -->
    <div class="toolbar">
        <div class="count"><i class="fas fa-file"></i> <strong><?=$total_docs?></strong> documents in repository</div>
        <div class="cat-tags">
            <?php foreach($categories as $c): ?>
            <span class="cat-tag" style="border-color:<?=($c["category"]==="Classified")?'#ef4444':($c["category"]==="Public"?'#22c55e':'#e2e8f0')?>">
                <i class="fas <?=$category_icons[$c["category"]]??'fa-file'?>" style="font-size:0.5rem;"></i> <?=$c["category"]?> (<?=$c["cnt"]?>)
            </span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Document Grid -->
    <div class="doc-grid">
        <?php foreach($documents as $doc): 
            $color=$classification_colors[$doc["classification"]]??"#64748b";
            $bg=$classification_bg[$doc["classification"]]??"rgba(100,116,139,0.08)";
            $icon=$category_icons[$doc["category"]]??"fa-file";
        ?>
        <div class="doc-card">
            <div class="top-bar" style="background:linear-gradient(90deg,<?=$color?>,<?=$color?>88)"></div>
            <div class="card-body">
                <div class="row1">
                    <div class="d-icon"><i class="fas <?=$icon?>"></i></div>
                    <div class="d-id">DOC-<?=$doc["id"]?></div>
                </div>
                <div class="d-title"><?=htmlspecialchars($doc["title"])?></div>
                <div class="d-meta">
                    <span class="class-badge" style="background:<?=$bg?>;color:<?=$color?>"><?=$doc["classification"]?></span>
                    <span class="d-cat"><i class="fas <?=$icon?>"></i> <?=$doc["category"]?></span>
                    <span class="d-date"><i class="far fa-calendar"></i> <?=$doc["document_date"]?></span>
                </div>
                <a href="index.php?d=<?=$doc["id"]?>" class="view-btn">View Document <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<!-- Footer -->
<div class="foot">
    <div class="container">
        <div class="ft-inner">
            <div class="copy">&copy; <?=date("Y")?> U.S. Federal Document Portal. All rights reserved.</div>
            <div class="ft-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Security Notice</a>
                <a href="#">Accessibility</a>
                <a href="#">FOIA</a>
            </div>
            <div class="ft-seal"><i class="fas fa-building-columns"></i> .gov</div>
        </div>
    </div>
</div>

<script>
// Auto-submit form when clicking example codes
document.querySelectorAll('.examples code').forEach(el => {
    el.addEventListener('click', function() {
        document.getElementsByName('doc')[0].value = this.textContent;
        this.closest('form').submit();
    });
});
</script>

</body>
</html>

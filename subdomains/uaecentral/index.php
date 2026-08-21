<?php
if(isset($_GET["fname"]) && isset($_GET["lname"])){
    echo htmlspecialchars($_GET["fname"], ENT_QUOTES);
    echo htmlspecialchars($_GET["lname"], ENT_QUOTES);
}
elseif(isset($_GET["id"])){
    $arr = array('details','alert','confirm','prompt','eval','details','ontoggle','onmousemove','onmouseover','script','Script','sCript','scRipt','scrIpt','scriPt','scripT','SCript','SCRipt','SCRIpt','SCRIPt','SCRIPT','script','img','image','svg','onfocus', '"c"+"onfirm(1)">', '"co"+"nfirm(1)">', '"con"+"firm(1)">', '"conf"+"irm(1)">', '"confi"+"rm(1)">', '"confir"+"m(1)">', '"confirm"+"(1)">', '"c"+"o"+"n"+"f"+"i"+"r"+"m"+"(1)">', "&#97;lert(1)", "a&#108;ert(1)", "al&#101;rt(1)", "ale&#114;t(1)", "aler&#116;(1)", "&#97;&#108;&#101;&#114;&#116;(1)", "alert&#40;1&#41;", "&#x61;lert(1)", "a&#x6c;ert(1)", "al&#x65;rt(1)", "ale&#x72;t(1)", "aler&#x74;(1)", "&#x61;&#x6c;&#x65;&#x72;&#x74;(1)", "alert&#x28;1&#x29;", "&#x000061;lert(1)", "a&#x00006c;ert(1)", "al&#x000065;rt(1)", "ale&#x000072;t(1)", "aler&#x000074;(1)", "&#x000061;&#x00006c;&#x000065;&#x000072;&#x000074;(1)", "alert&#x000028;1&#x000029;", "\u0061lert(1)", "a\u006cert(1)", "al\u0065rt(1)", "ale\u0072t(1)", "aler\u0074(1)", "\u0061\u006c\u0065\u0072\u0074(1)", "&#99;onfirm(1)", "c&#111;nfirm(1)", "co&#110;firm(1)", "con&#102;irm(1)", "conf&#105;rm(1)", "confi&#114;m(1)", "confir&#109;(1)", "&#99;&#111;&#110;&#102;&#105;&#114;&#109;(1)", "confirm&#40;1&#41;", "&#x63;onfirm(1)", "c&#x6f;nfirm(1)", "co&#x6e;firm(1)", "con&#x66;irm(1)", "conf&#x69;rm(1)", "confi&#x72;m(1)", "confir&#x6d;(1)", "&#x63;&#x6f;&#x6e;&#x66;&#x69;&#x72;&#x6d;(1)", "confirm&#x28;1&#x29;", "&#x000063;onfirm(1)", "c&#x00006f;nfirm(1)", "co&#x00006e;firm(1)", "con&#x000066;irm(1)", "conf&#x000069;rm(1)", "confi&#x000072;m(1)", "confir&#x00006d;(1)", "&#x000063;&#x00006f;&#x00006e;&#x000066;&#x000069;&#x000072;&#x00006d;(1)", "confirm&#x000028;1&#x000029;", "\u0063onfirm(1)", "c\u006fnfirm(1)", "co\u006efirm(1)", "con\u0066irm(1)", "conf\u0069rm(1)", "confi\u0072m(1)", "confir\u006d(1)", "\u0063\u006f\u006e\u0066\u0069\u0072\u006d(1)", "&#112;rompt(1)", "p&#114;ompt(1)", "pr&#111;mpt(1)", "pro&#109;pt(1)", "prom&#112;t(1)", "promp&#116;(1)", "&#112;&#114;&#111;&#109;&#112;&#116;(1)", "prompt&#40;1&#41;", "&#x70;rompt(1)", "p&#x72;ompt(1)", "pr&#x6f;mpt(1)", "pro&#x6d;pt(1)", "prom&#x70;t(1)", "promp&#x74;(1)", "&#x70;&#x72;&#x6f;&#x6d;&#x70;&#x74;(1)", "prompt&#x28;1&#x29;", "&#x000070;rompt(1)", "p&#x000072;ompt(1)", "pr&#x00006f;mpt(1)", "pro&#x00006d;pt(1)", "prom&#x000070;t(1)", "promp&#x000074;(1)", "&#x000070;&#x000072;&#x00006f;&#x00006d;&#x000070;&#x000074;(1)", "prompt&#x000028;1&#x000029;", "\u0070rompt(1)", "p\u0072ompt(1)", "pr\u006fmpt(1)", "pro\u006dpt(1)", "prom\u0070t(1)", "promp\u0074(1)", "\u0070\u0072\u006f\u006d\u0070\u0074(1)");
    $re = str_replace($arr, '', $_GET['id']);
    echo $re;
}
elseif(isset($_GET["cat"])){
    $arr = array('details','alert','confirm','prompt','eval','details','ontoggle','onmousemove','onmouseover','script','Script','sCript','scRipt','scrIpt','scriPt','scripT','SCript','SCRipt','SCRIpt','SCRIPt','SCRIPT','script','img','image','svg','onfocus', '"c"+"onfirm(1)">', '"co"+"nfirm(1)">', '"con"+"firm(1)">', '"conf"+"irm(1)">', '"confi"+"rm(1)">', '"confir"+"m(1)">', '"confirm"+"(1)">', '"c"+"o"+"n"+"f"+"i"+"r"+"m"+"(1)">', "&#97;lert(1)", "a&#108;ert(1)", "al&#101;rt(1)", "ale&#114;t(1)", "aler&#116;(1)", "&#97;&#108;&#101;&#114;&#116;(1)", "alert&#40;1&#41;", "&#x61;lert(1)", "a&#x6c;ert(1)", "al&#x65;rt(1)", "ale&#x72;t(1)", "aler&#x74;(1)", "&#x61;&#x6c;&#x65;&#x72;&#x74;(1)", "alert&#x28;1&#x29;", "&#x000061;lert(1)", "a&#x00006c;ert(1)", "al&#x000065;rt(1)", "ale&#x000072;t(1)", "aler&#x000074;(1)", "&#x000061;&#x00006c;&#x000065;&#x000072;&#x000074;(1)", "alert&#x000028;1&#x000029;", "\u0061lert(1)", "a\u006cert(1)", "al\u0065rt(1)", "ale\u0072t(1)", "aler\u0074(1)", "\u0061\u006c\u0065\u0072\u0074(1)", "&#99;onfirm(1)", "c&#111;nfirm(1)", "co&#110;firm(1)", "con&#102;irm(1)", "conf&#105;rm(1)", "confi&#114;m(1)", "confir&#109;(1)", "&#99;&#111;&#110;&#102;&#105;&#114;&#109;(1)", "confirm&#40;1&#41;", "&#x63;onfirm(1)", "c&#x6f;nfirm(1)", "co&#x6e;firm(1)", "con&#x66;irm(1)", "conf&#x69;rm(1)", "confi&#x72;m(1)", "confir&#x6d;(1)", "&#x63;&#x6f;&#x6e;&#x66;&#x69;&#x72;&#x6d;(1)", "confirm&#x28;1&#x29;", "&#x000063;onfirm(1)", "c&#x00006f;nfirm(1)", "co&#x00006e;firm(1)", "con&#x000066;irm(1)", "conf&#x000069;rm(1)", "confi&#x000072;m(1)", "confir&#x00006d;(1)", "&#x000063;&#x00006f;&#x00006e;&#x000066;&#x000069;&#x000072;&#x00006d;(1)", "confirm&#x000028;1&#x000029;", "\u0063onfirm(1)", "c\u006fnfirm(1)", "co\u006efirm(1)", "con\u0066irm(1)", "conf\u0069rm(1)", "confi\u0072m(1)", "confir\u006d(1)", "\u0063\u006f\u006e\u0066\u0069\u0072\u006d(1)", "&#112;rompt(1)", "p&#114;ompt(1)", "pr&#111;mpt(1)", "pro&#109;pt(1)", "prom&#112;t(1)", "promp&#116;(1)", "&#112;&#114;&#111;&#109;&#112;&#116;(1)", "prompt&#40;1&#41;", "&#x70;rompt(1)", "p&#x72;ompt(1)", "pr&#x6f;mpt(1)", "pro&#x6d;pt(1)", "prom&#x70;t(1)", "promp&#x74;(1)", "&#x70;&#x72;&#x6f;&#x6d;&#x70;&#x74;(1)", "prompt&#x28;1&#x29;", "&#x000070;rompt(1)", "p&#x000072;ompt(1)", "pr&#x00006f;mpt(1)", "pro&#x00006d;pt(1)", "prom&#x000070;t(1)", "promp&#x000074;(1)", "&#x000070;&#x000072;&#x00006f;&#x00006d;&#x000070;&#x000074;(1)", "prompt&#x000028;1&#x000029;", "\u0070rompt(1)", "p\u0072ompt(1)", "pr\u006fmpt(1)", "pro\u006dpt(1)", "prom\u0070t(1)", "promp\u0074(1)", "\u0070\u0072\u006f\u006d\u0070\u0074(1)");
    $re = str_replace($arr, '', $_GET['cat']);
    echo $re;
}
elseif(isset($_GET["page"])){
    $arr = array('details','alert','confirm','prompt','eval','details','ontoggle','onmousemove','onmouseover','script','Script','sCript','scRipt','scrIpt','scriPt','scripT','SCript','SCRipt','SCRIpt','SCRIPt','SCRIPT','script','img','image','svg','onfocus', '"c"+"onfirm(1)">', '"co"+"nfirm(1)">', '"con"+"firm(1)">', '"conf"+"irm(1)">', '"confi"+"rm(1)">', '"confir"+"m(1)">', '"confirm"+"(1)">', '"c"+"o"+"n"+"f"+"i"+"r"+"m"+"(1)">', "&#97;lert(1)", "a&#108;ert(1)", "al&#101;rt(1)", "ale&#114;t(1)", "aler&#116;(1)", "&#97;&#108;&#101;&#114;&#116;(1)", "alert&#40;1&#41;", "&#x61;lert(1)", "a&#x6c;ert(1)", "al&#x65;rt(1)", "ale&#x72;t(1)", "aler&#x74;(1)", "&#x61;&#x6c;&#x65;&#x72;&#x74;(1)", "alert&#x28;1&#x29;", "&#x000061;lert(1)", "a&#x00006c;ert(1)", "al&#x000065;rt(1)", "ale&#x000072;t(1)", "aler&#x000074;(1)", "&#x000061;&#x00006c;&#x000065;&#x000072;&#x000074;(1)", "alert&#x000028;1&#x000029;", "\u0061lert(1)", "a\u006cert(1)", "al\u0065rt(1)", "ale\u0072t(1)", "aler\u0074(1)", "\u0061\u006c\u0065\u0072\u0074(1)", "&#99;onfirm(1)", "c&#111;nfirm(1)", "co&#110;firm(1)", "con&#102;irm(1)", "conf&#105;rm(1)", "confi&#114;m(1)", "confir&#109;(1)", "&#99;&#111;&#110;&#102;&#105;&#114;&#109;(1)", "confirm&#40;1&#41;", "&#x63;onfirm(1)", "c&#x6f;nfirm(1)", "co&#x6e;firm(1)", "con&#x66;irm(1)", "conf&#x69;rm(1)", "confi&#x72;m(1)", "confir&#x6d;(1)", "&#x63;&#x6f;&#x6e;&#x66;&#x69;&#x72;&#x6d;(1)", "confirm&#x28;1&#x29;", "&#x000063;onfirm(1)", "c&#x00006f;nfirm(1)", "co&#x00006e;firm(1)", "con&#x000066;irm(1)", "conf&#x000069;rm(1)", "confi&#x000072;m(1)", "confir&#x00006d;(1)", "&#x000063;&#x00006f;&#x00006e;&#x000066;&#x000069;&#x000072;&#x00006d;(1)", "confirm&#x000028;1&#x000029;", "\u0063onfirm(1)", "c\u006fnfirm(1)", "co\u006efirm(1)", "con\u0066irm(1)", "conf\u0069rm(1)", "confi\u0072m(1)", "confir\u006d(1)", "\u0063\u006f\u006e\u0066\u0069\u0072\u006d(1)", "&#112;rompt(1)", "p&#114;ompt(1)", "pr&#111;mpt(1)", "pro&#109;pt(1)", "prom&#112;t(1)", "promp&#116;(1)", "&#112;&#114;&#111;&#109;&#112;&#116;(1)", "prompt&#40;1&#41;", "&#x70;rompt(1)", "p&#x72;ompt(1)", "pr&#x6f;mpt(1)", "pro&#x6d;pt(1)", "prom&#x70;t(1)", "promp&#x74;(1)", "&#x70;&#x72;&#x6f;&#x6d;&#x70;&#x74;(1)", "prompt&#x28;1&#x29;", "&#x000070;rompt(1)", "p&#x000072;ompt(1)", "pr&#x00006f;mpt(1)", "pro&#x00006d;pt(1)", "prom&#x000070;t(1)", "promp&#x000074;(1)", "&#x000070;&#x000072;&#x00006f;&#x00006d;&#x000070;&#x000074;(1)", "prompt&#x000028;1&#x000029;", "\u0070rompt(1)", "p\u0072ompt(1)", "pr\u006fmpt(1)", "pro\u006dpt(1)", "prom\u0070t(1)", "promp\u0074(1)", "\u0070\u0072\u006f\u006d\u0070\u0074(1)");
    $re = str_replace($arr, '', $_GET['page']);
    echo $re;
}
elseif(isset($_GET["number"])){
    $arr = array('details','alert','confirm','prompt','eval','details','ontoggle','onmousemove','onmouseover','script','Script','sCript','scRipt','scrIpt','scriPt','scripT','SCript','SCRipt','SCRIpt','SCRIPt','SCRIPT','script','img','image','svg','onfocus', '"c"+"onfirm(1)">', '"co"+"nfirm(1)">', '"con"+"firm(1)">', '"conf"+"irm(1)">', '"confi"+"rm(1)">', '"confir"+"m(1)">', '"confirm"+"(1)">', '"c"+"o"+"n"+"f"+"i"+"r"+"m"+"(1)">', "&#97;lert(1)", "a&#108;ert(1)", "al&#101;rt(1)", "ale&#114;t(1)", "aler&#116;(1)", "&#97;&#108;&#101;&#114;&#116;(1)", "alert&#40;1&#41;", "&#x61;lert(1)", "a&#x6c;ert(1)", "al&#x65;rt(1)", "ale&#x72;t(1)", "aler&#x74;(1)", "&#x61;&#x6c;&#x65;&#x72;&#x74;(1)", "alert&#x28;1&#x29;", "&#x000061;lert(1)", "a&#x00006c;ert(1)", "al&#x000065;rt(1)", "ale&#x000072;t(1)", "aler&#x000074;(1)", "&#x000061;&#x00006c;&#x000065;&#x000072;&#x000074;(1)", "alert&#x000028;1&#x000029;", "\u0061lert(1)", "a\u006cert(1)", "al\u0065rt(1)", "ale\u0072t(1)", "aler\u0074(1)", "\u0061\u006c\u0065\u0072\u0074(1)", "&#99;onfirm(1)", "c&#111;nfirm(1)", "co&#110;firm(1)", "con&#102;irm(1)", "conf&#105;rm(1)", "confi&#114;m(1)", "confir&#109;(1)", "&#99;&#111;&#110;&#102;&#105;&#114;&#109;(1)", "confirm&#40;1&#41;", "&#x63;onfirm(1)", "c&#x6f;nfirm(1)", "co&#x6e;firm(1)", "con&#x66;irm(1)", "conf&#x69;rm(1)", "confi&#x72;m(1)", "confir&#x6d;(1)", "&#x63;&#x6f;&#x6e;&#x66;&#x69;&#x72;&#x6d;(1)", "confirm&#x28;1&#x29;", "&#x000063;onfirm(1)", "c&#x00006f;nfirm(1)", "co&#x00006e;firm(1)", "con&#x000066;irm(1)", "conf&#x000069;rm(1)", "confi&#x000072;m(1)", "confir&#x00006d;(1)", "&#x000063;&#x00006f;&#x00006e;&#x000066;&#x000069;&#x000072;&#x00006d;(1)", "confirm&#x000028;1&#x000029;", "\u0063onfirm(1)", "c\u006fnfirm(1)", "co\u006efirm(1)", "con\u0066irm(1)", "conf\u0069rm(1)", "confi\u0072m(1)", "confir\u006d(1)", "\u0063\u006f\u006e\u0066\u0069\u0072\u006d(1)", "&#112;rompt(1)", "p&#114;ompt(1)", "pr&#111;mpt(1)", "pro&#109;pt(1)", "prom&#112;t(1)", "promp&#116;(1)", "&#112;&#114;&#111;&#109;&#112;&#116;(1)", "prompt&#40;1&#41;", "&#x70;rompt(1)", "p&#x72;ompt(1)", "pr&#x6f;mpt(1)", "pro&#x6d;pt(1)", "prom&#x70;t(1)", "promp&#x74;(1)", "&#x70;&#x72;&#x6f;&#x6d;&#x70;&#x74;(1)", "prompt&#x28;1&#x29;", "&#x000070;rompt(1)", "p&#x000072;ompt(1)", "pr&#x00006f;mpt(1)", "pro&#x00006d;pt(1)", "prom&#x000070;t(1)", "promp&#x000074;(1)", "&#x000070;&#x000072;&#x00006f;&#x00006d;&#x000070;&#x000074;(1)", "prompt&#x000028;1&#x000029;", "\u0070rompt(1)", "p\u0072ompt(1)", "pr\u006fmpt(1)", "pro\u006dpt(1)", "prom\u0070t(1)", "promp\u0074(1)", "\u0070\u0072\u006f\u006d\u0070\u0074(1)");
    $re = str_replace($arr, '', $_GET['number']);
    echo $re;
}
elseif(isset($_GET["page_id"])){
    $arr = array('details','alert','confirm','prompt','eval','details','ontoggle','onmousemove','onmouseover','script','Script','sCript','scRipt','scrIpt','scriPt','scripT','SCript','SCRipt','SCRIpt','SCRIPt','SCRIPT','script','img','image','svg','onfocus', '"c"+"onfirm(1)">', '"co"+"nfirm(1)">', '"con"+"firm(1)">', '"conf"+"irm(1)">', '"confi"+"rm(1)">', '"confir"+"m(1)">', '"confirm"+"(1)">', '"c"+"o"+"n"+"f"+"i"+"r"+"m"+"(1)">', "&#97;lert(1)", "a&#108;ert(1)", "al&#101;rt(1)", "ale&#114;t(1)", "aler&#116;(1)", "&#97;&#108;&#101;&#114;&#116;(1)", "alert&#40;1&#41;", "&#x61;lert(1)", "a&#x6c;ert(1)", "al&#x65;rt(1)", "ale&#x72;t(1)", "aler&#x74;(1)", "&#x61;&#x6c;&#x65;&#x72;&#x74;(1)", "alert&#x28;1&#x29;", "&#x000061;lert(1)", "a&#x00006c;ert(1)", "al&#x000065;rt(1)", "ale&#x000072;t(1)", "aler&#x000074;(1)", "&#x000061;&#x00006c;&#x000065;&#x000072;&#x000074;(1)", "alert&#x000028;1&#x000029;", "\u0061lert(1)", "a\u006cert(1)", "al\u0065rt(1)", "ale\u0072t(1)", "aler\u0074(1)", "\u0061\u006c\u0065\u0072\u0074(1)", "&#99;onfirm(1)", "c&#111;nfirm(1)", "co&#110;firm(1)", "con&#102;irm(1)", "conf&#105;rm(1)", "confi&#114;m(1)", "confir&#109;(1)", "&#99;&#111;&#110;&#102;&#105;&#114;&#109;(1)", "confirm&#40;1&#41;", "&#x63;onfirm(1)", "c&#x6f;nfirm(1)", "co&#x6e;firm(1)", "con&#x66;irm(1)", "conf&#x69;rm(1)", "confi&#x72;m(1)", "confir&#x6d;(1)", "&#x63;&#x6f;&#x6e;&#x66;&#x69;&#x72;&#x6d;(1)", "confirm&#x28;1&#x29;", "&#x000063;onfirm(1)", "c&#x00006f;nfirm(1)", "co&#x00006e;firm(1)", "con&#x000066;irm(1)", "conf&#x000069;rm(1)", "confi&#x000072;m(1)", "confir&#x00006d;(1)", "&#x000063;&#x00006f;&#x00006e;&#x000066;&#x000069;&#x000072;&#x00006d;(1)", "confirm&#x000028;1&#x000029;", "\u0063onfirm(1)", "c\u006fnfirm(1)", "co\u006efirm(1)", "con\u0066irm(1)", "conf\u0069rm(1)", "confi\u0072m(1)", "confir\u006d(1)", "\u0063\u006f\u006e\u0066\u0069\u0072\u006d(1)", "&#112;rompt(1)", "p&#114;ompt(1)", "pr&#111;mpt(1)", "pro&#109;pt(1)", "prom&#112;t(1)", "promp&#116;(1)", "&#112;&#114;&#111;&#109;&#112;&#116;(1)", "prompt&#40;1&#41;", "&#x70;rompt(1)", "p&#x72;ompt(1)", "pr&#x6f;mpt(1)", "pro&#x6d;pt(1)", "prom&#x70;t(1)", "promp&#x74;(1)", "&#x70;&#x72;&#x6f;&#x6d;&#x70;&#x74;(1)", "prompt&#x28;1&#x29;", "&#x000070;rompt(1)", "p&#x000072;ompt(1)", "pr&#x00006f;mpt(1)", "pro&#x00006d;pt(1)", "prom&#x000070;t(1)", "promp&#x000074;(1)", "&#x000070;&#x000072;&#x00006f;&#x00006d;&#x000070;&#x000074;(1)", "prompt&#x000028;1&#x000029;", "\u0070rompt(1)", "p\u0072ompt(1)", "pr\u006fmpt(1)", "pro\u006dpt(1)", "prom\u0070t(1)", "promp\u0074(1)", "\u0070\u0072\u006f\u006d\u0070\u0074(1)");
    $re = str_replace($arr, '', $_GET['page_id']);
    echo $re;
}
elseif(isset($_GET["categoryid"])){
    $arr = array('details','alert','confirm','prompt','eval','details','ontoggle','onmousemove','onmouseover','script','Script','sCript','scRipt','scrIpt','scriPt','scripT','SCript','SCRipt','SCRIpt','SCRIPt','SCRIPT','script','img','image','svg','onfocus', '"c"+"onfirm(1)">', '"co"+"nfirm(1)">', '"con"+"firm(1)">', '"conf"+"irm(1)">', '"confi"+"rm(1)">', '"confir"+"m(1)">', '"confirm"+"(1)">', '"c"+"o"+"n"+"f"+"i"+"r"+"m"+"(1)">', "&#97;lert(1)", "a&#108;ert(1)", "al&#101;rt(1)", "ale&#114;t(1)", "aler&#116;(1)", "&#97;&#108;&#101;&#114;&#116;(1)", "alert&#40;1&#41;", "&#x61;lert(1)", "a&#x6c;ert(1)", "al&#x65;rt(1)", "ale&#x72;t(1)", "aler&#x74;(1)", "&#x61;&#x6c;&#x65;&#x72;&#x74;(1)", "alert&#x28;1&#x29;", "&#x000061;lert(1)", "a&#x00006c;ert(1)", "al&#x000065;rt(1)", "ale&#x000072;t(1)", "aler&#x000074;(1)", "&#x000061;&#x00006c;&#x000065;&#x000072;&#x000074;(1)", "alert&#x000028;1&#x000029;", "\u0061lert(1)", "a\u006cert(1)", "al\u0065rt(1)", "ale\u0072t(1)", "aler\u0074(1)", "\u0061\u006c\u0065\u0072\u0074(1)", "&#99;onfirm(1)", "c&#111;nfirm(1)", "co&#110;firm(1)", "con&#102;irm(1)", "conf&#105;rm(1)", "confi&#114;m(1)", "confir&#109;(1)", "&#99;&#111;&#110;&#102;&#105;&#114;&#109;(1)", "confirm&#40;1&#41;", "&#x63;onfirm(1)", "c&#x6f;nfirm(1)", "co&#x6e;firm(1)", "con&#x66;irm(1)", "conf&#x69;rm(1)", "confi&#x72;m(1)", "confir&#x6d;(1)", "&#x63;&#x6f;&#x6e;&#x66;&#x69;&#x72;&#x6d;(1)", "confirm&#x28;1&#x29;", "&#x000063;onfirm(1)", "c&#x00006f;nfirm(1)", "co&#x00006e;firm(1)", "con&#x000066;irm(1)", "conf&#x000069;rm(1)", "confi&#x000072;m(1)", "confir&#x00006d;(1)", "&#x000063;&#x00006f;&#x00006e;&#x000066;&#x000069;&#x000072;&#x00006d;(1)", "confirm&#x000028;1&#x000029;", "\u0063onfirm(1)", "c\u006fnfirm(1)", "co\u006efirm(1)", "con\u0066irm(1)", "conf\u0069rm(1)", "confi\u0072m(1)", "confir\u006d(1)", "\u0063\u006f\u006e\u0066\u0069\u0072\u006d(1)", "&#112;rompt(1)", "p&#114;ompt(1)", "pr&#111;mpt(1)", "pro&#109;pt(1)", "prom&#112;t(1)", "promp&#116;(1)", "&#112;&#114;&#111;&#109;&#112;&#116;(1)", "prompt&#40;1&#41;", "&#x70;rompt(1)", "p&#x72;ompt(1)", "pr&#x6f;mpt(1)", "pro&#x6d;pt(1)", "prom&#x70;t(1)", "promp&#x74;(1)", "&#x70;&#x72;&#x6f;&#x6d;&#x70;&#x74;(1)", "prompt&#x28;1&#x29;", "&#x000070;rompt(1)", "p&#x000072;ompt(1)", "pr&#x00006f;mpt(1)", "pro&#x00006d;pt(1)", "prom&#x000070;t(1)", "promp&#x000074;(1)", "&#x000070;&#x000072;&#x00006f;&#x00006d;&#x000070;&#x000074;(1)", "prompt&#x000028;1&#x000029;", "\u0070rompt(1)", "p\u0072ompt(1)", "pr\u006fmpt(1)", "pro\u006dpt(1)", "prom\u0070t(1)", "promp\u0074(1)", "\u0070\u0072\u006f\u006d\u0070\u0074(1)");
    $re = str_replace($arr, '', $_GET['categoryid']);
    echo $re;
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KrazePlanet</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
</head>
<body>

    <!-- navbar -->
  <nav class="navbar navbar-expand-md navbar-dark"
    style="background-color: rgb(58, 63, 68); --darkreader-inline-bgcolor:#2f3335;" data-darkreader-inline-bgcolor="">
    <div class="container-fluid">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" style="color: rgb(107, 189, 69);"
              href="/">KrazePlanet</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="../../about">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="../../contact">Contact Us</a>
          </li>
        </ul>
        <form class="d-flex" role="search">
          <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
          <button class="btn btn-outline-success" type="submit">Search</button>
        </form>
      </div>
    </div>
  </nav>

  <!-- Accordion -->
  <div class="card mt-3" style="width: 80%; margin-left: 10%; border-radius: 26px;">
    <div class="card-header text-center">
      Backend Source Code
    </div>
    <div class="card-body">
<pre>
if(isset($_GET["fname"]) && isset($_GET["lname"])){
    echo htmlspecialchars($_GET["fname"], ENT_QUOTES);
    echo htmlspecialchars($_GET["lname"], ENT_QUOTES);
}
# use arjun tool to find hidden parameter
</pre>
    </div>
  </div>

<!-- input fields -->
  <div class="mt-3" style="width: 40%; margin-left: 10%;">
    <form action="" method="get">
      <label for="exampleFormControlTextarea1" class="form-label mt-3 mb-1">First Name</label>
      <input class="form-control" type="text" placeholder="Enter input" aria-label="default input example" name="fname">
      <label for="exampleFormControlTextarea1" class="form-label mt-3 mb-1">Last Name</label>
      <input class="form-control" type="text" placeholder="Enter input" aria-label="default input example" name="lname">
      <input type="submit" hidden />
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa"
    crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.5/dist/umd/popper.min.js"
    integrity="sha384-Xe+8cL9oJa6tN/veChSP7q+mnSPaj5Bcu9mPX5F5xIGE0DVittaqT5lorf0EI7Vk"
    crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.min.js"
    integrity="sha384-ODmDIVzN+pFdexxHEHFBQH3/9/vQ9uori45z4JjnFsRydbmQbmL5t1tQ0culUzyK"
    crossorigin="anonymous"></script>
</body>
</html>

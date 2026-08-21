const sections = document.querySelectorAll(".section");

window.addEventListener("scroll", function(event) {
    sections.forEach((section) => {
        let top = window.scrollY + 250;
        let offset = section.offsetTop;
        let height = section.offsetHeight;
        let id = section.getAttribute("id");

        if (top >= offset && top < offset + height) {
            document.querySelectorAll(".active").forEach((a) => {
                a.classList.remove("active");
            });

            document.querySelector("[href*=" + id + "]").classList.add("active");
        }
    });
});

$("navy a").click(function(e) {
    e.preventDefault();
    $("navy a").removeClass("active_now");
    $(this).addClass("active_now");
    if (this.id === !"payment") {
        $(".payment").addClass("noshow");
    } else if (this.id === "payment") {
        $(".payment").removeClass("noshow");
        $(".rightbox").children().not(".payment").addClass("noshow");
    } else if (this.id === "profile") {
        $(".profile").removeClass("noshow");
        $(".rightbox").children().not(".profile").addClass("noshow");
    } else if (this.id === "settings") {
        $(".settings").removeClass("noshow");
        $(".rightbox").children().not(".settings").addClass("noshow");
    }
});
(function () {
    "use strict";

    window.mtnNoBanner = function (plid, container) {
        if (container) {
            container.setAttribute("data-placement-fallback", String(plid));
        }
    };

    window.mobucksApi = {
        sdkVersion: "2.4.1",
        adminEndpoint: "/admin/",
        placeAd: function (options, onEmpty) {
            if (!options || !options.containerElementId) {
                return;
            }
            var container = document.getElementById(options.containerElementId);
            if (!container) {
                return;
            }
            fetch("/api/mobucks_ad.php?plid=" + encodeURIComponent(options.plid || 73), {
                method: "GET",
                headers: { "Accept": "application/json" }
            })
            .then(function (r) { return r.json(); })
            .then(function (ad) {
                if (ad && ad.banner_html && container.querySelector("a")) {
                    container.querySelector("a").textContent = ad.banner_title;
                } else if (typeof onEmpty === "function") {
                    onEmpty();
                }
            })
            .catch(function () {
                if (typeof onEmpty === "function") {
                    onEmpty();
                }
            });
        }
    };
})();


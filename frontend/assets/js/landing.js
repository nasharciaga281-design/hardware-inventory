document.addEventListener("DOMContentLoaded", function () {
    const header = document.querySelector("[data-landing-header]");
    const toggle = header ? header.querySelector("[data-menu-toggle]") : null;

    if (!header || !toggle) {
        return;
    }

    const icon = toggle.querySelector("i");

    const setMenu = function (open) {
        header.classList.toggle("nav-open", open);
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
        toggle.setAttribute("aria-label", open ? "Close navigation menu" : "Open navigation menu");

        if (icon) {
            icon.className = open ? "bi bi-x-lg" : "bi bi-list";
        }
    };

    toggle.addEventListener("click", function () {
        setMenu(toggle.getAttribute("aria-expanded") !== "true");
    });

    header.querySelectorAll("a").forEach(function (link) {
        link.addEventListener("click", function () {
            setMenu(false);
        });
    });

    document.addEventListener("click", function (event) {
        if (header.classList.contains("nav-open") && !header.contains(event.target)) {
            setMenu(false);
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && header.classList.contains("nav-open")) {
            setMenu(false);
            toggle.focus();
        }
    });

    window.addEventListener("resize", function () {
        if (window.innerWidth > 860) {
            setMenu(false);
        }
    });
});

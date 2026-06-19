document.addEventListener("DOMContentLoaded", () => {
    // Language switch logic
    const langBtn = document.getElementById("lang-switch");
    const languages = ["en", "bg"]; // Removed "de" from the languages array
    let currentLangIdx = 0;

    function updateLangTexts() {
        document.querySelectorAll("[data-en]").forEach(el => {
            el.textContent = el.getAttribute(`data-${languages[currentLangIdx]}`);
        });
    }

    function showToast(msg) {
        let toast = document.createElement("div");
        toast.className = "toast";
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.classList.add("fade");
        }, 100);
        setTimeout(() => {
            toast.remove();
        }, 2000);
    }

    if (langBtn) {
        langBtn.addEventListener("click", () => {
            currentLangIdx = (currentLangIdx + 1) % languages.length;
            langBtn.textContent = languages[(currentLangIdx + 1) % languages.length].toUpperCase();
            updateLangTexts();
            showToast(`Language switched to ${languages[currentLangIdx].toUpperCase()}`);
        });
        langBtn.textContent = languages[1].toUpperCase();
    }

    updateLangTexts();

    // Fade-in animation utility
    function fadeInElements(selector) {
        const fadeIns = document.querySelectorAll(selector);
        fadeIns.forEach((el, i) => {
            el.style.animationDelay = `${i * 0.2}s`;
            el.classList.add("visible");
        });
    }
    fadeInElements(".fade-in");

    // Simple SPA navigation simulation
    const navLinks = document.querySelectorAll("[data-page-link]");
    const pages = document.querySelectorAll(".page");
    navLinks.forEach(link => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            const target = link.getAttribute("data-page-link");
            pages.forEach(p => p.style.display = "none");
            document.getElementById(target).style.display = "block";
            showToast(`Navigated to ${target}`);
        });
    });

    // Fade-out utility (for future use)
    window.fadeOutElement = function(el) {
        el.classList.remove("visible");
        el.classList.add("fade-out");
        setTimeout(() => el.style.display = "none", 500);
    };

    const scrollBtn = document.getElementById("scroll-to-top");
    if (scrollBtn) {
        window.addEventListener("scroll", () => {
            if (window.scrollY > 300) {
                scrollBtn.classList.add("visible");
            } else {
                scrollBtn.classList.remove("visible");
            }
        });
        scrollBtn.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }

    const consentPopup = document.getElementById("cookie-consent");
    const acceptButton = document.getElementById("cookie-accept");
    if (!localStorage.getItem("cookieConsent")) {
         consentPopup.style.display = "block";
    }
    acceptButton && acceptButton.addEventListener("click", () => {
         localStorage.setItem("cookieConsent", "true");
         consentPopup.style.display = "none";
    });

    const hamburger = document.getElementById("hamburger");
    const navRight = document.querySelector(".nav-right");
    const navbarEl = document.querySelector(".navbar");
    if (hamburger && navRight) {
        hamburger.addEventListener("click", (e) => {
            e.stopPropagation();
            hamburger.classList.toggle("open");
            navRight.classList.toggle("open");
        });
        document.addEventListener("click", (e) => {
            if (navbarEl && !navbarEl.contains(e.target)) {
                hamburger.classList.remove("open");
                navRight.classList.remove("open");
            }
        });
    }
});

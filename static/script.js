document.addEventListener("DOMContentLoaded", () => {
    // Language switch logic
    const langBtn = document.getElementById("lang-switch");
    const languages = ["en", "bg"];
    const LANG_KEY = "siteLang";
    let memoryLang = "en";

    function readLang() {
        try {
            const stored = localStorage.getItem(LANG_KEY);
            if (languages.includes(stored)) {
                return stored;
            }
        } catch (err) {
            return memoryLang;
        }
        return memoryLang;
    }

    function saveLang(lang) {
        memoryLang = lang;
        try {
            localStorage.setItem(LANG_KEY, lang);
        } catch (err) {
            return;
        }
    }

    let currentLangIdx = languages.indexOf(readLang());

    function updateLangButton() {
        if (langBtn) {
            langBtn.textContent = languages[(currentLangIdx + 1) % languages.length].toUpperCase();
        }
    }

    function updateLangTexts() {
        const lang = languages[currentLangIdx];
        document.documentElement.setAttribute("lang", lang);
        document.querySelectorAll("[data-en]").forEach(el => {
            const value = el.getAttribute(`data-${lang}`);
            if (value !== null) {
                el.textContent = value;
            }
        });
        updateLangButton();
    }

    window.addEventListener("storage", (event) => {
        if (event.key === LANG_KEY && languages.includes(event.newValue)) {
            currentLangIdx = languages.indexOf(event.newValue);
            updateLangTexts();
        }
    });

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
            saveLang(languages[currentLangIdx]);
            updateLangTexts();
            showToast(`Language switched to ${languages[currentLangIdx].toUpperCase()}`);
        });
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
    let consentGiven = false;
    try {
        consentGiven = Boolean(localStorage.getItem("cookieConsent"));
    } catch (err) {
        consentGiven = false;
    }
    if (consentPopup && !consentGiven) {
        consentPopup.style.display = "block";
    }
    if (acceptButton) {
        acceptButton.addEventListener("click", () => {
            try {
                localStorage.setItem("cookieConsent", "true");
            } catch (err) {
                consentGiven = true;
            }
            if (consentPopup) {
                consentPopup.style.display = "none";
            }
        });
    }

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

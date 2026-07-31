document.getElementById("searchBox").addEventListener("input", function () {
    const query = this.value.toLowerCase().trim();

    // ===== CLASSI =====
    document.querySelectorAll(".list-group-item").forEach(item => {
        const match = item.textContent.toLowerCase().includes(query);
        item.style.display = match ? "" : "none";
    });

    document.querySelectorAll(".card").forEach(card => {

        // Card delle classi
        const list = card.querySelector(".list-group");
        if (list) {
            const visibleItems = [...list.querySelectorAll(".list-group-item")]
                .some(el => el.style.display !== "none");

            card.parentElement.style.display = (visibleItems || query === "")
                ? ""
                : "none";
            return;
        }

        // Card docenti/laboratori
        const title = card.querySelector(".card-title");
        if (!title) return;

        const match = title.textContent.toLowerCase().includes(query);

        card.parentElement.style.display = (match || query === "")
            ? ""
            : "none";
    });

    // ===== NASCONDI TITOLI VUOTI =====
    document.querySelectorAll("h2").forEach(title => {

        let next = title.nextElementSibling;
        if (!next) return;

        const visible = [...next.children].some(col => col.style.display !== "none");

        title.style.display = (visible || query === "")
            ? ""
            : "none";

        next.style.display = (visible || query === "")
            ? ""
            : "none";
    });
});

//Tema Bootstrap automatico

function getTheme() {
    const saved = localStorage.getItem('theme');
    if (saved) return saved;

    return window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light';
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
}

// Applica il tema in base a quello del browser (chiaro/scuro)
applyTheme(getTheme());

// Listener per cambio tema del browser
window.matchMedia('(prefers-color-scheme: dark)')
    .addEventListener('change', e => {
        if (!localStorage.getItem('theme')) {
            applyTheme(e.matches ? 'dark' : 'light');
        }
    });
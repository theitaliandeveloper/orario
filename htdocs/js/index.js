/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see https://www.gnu.org/licenses/.
*/

// Caricamento homepage
document.addEventListener("DOMContentLoaded", async function() {

        // Fetch Classi
        try {
            const res = await fetch("api/getClassi.php",{ signal: AbortSignal.timeout(3000) });
            const classi = await res.json();
            
            const years = { 1: "Prime", 2: "Seconde", 3: "Terze", 4: "Quarte", 5: "Quinte" };
            let html = "";
            for (let y = 1; y <= 5; y++) {
                const filtered = classi.filter(c => c.name.startsWith(y.toString()));
                html += `<div class="col-12 col-sm-6 col-md-4 col-lg"><div class="card h-100"><div class="card-body">
                         <h5 class="card-title">${years[y]}</h5><div class="list-group list-group-flush">`;
                filtered.forEach(c => {
                    html += `<a href="orario.php?view=classe&id=${c.id}" class="list-group-item list-group-item-action">${c.name}</a>`;
                });
                html += `</div></div></div></div>`;
            }
            document.getElementById("classes-container").innerHTML = html;
        } catch (e) { console.error("Error loading classes", e); }

        // Fetch Docenti
        try {
            const res = await fetch("api/getDocenti.php",{ signal: AbortSignal.timeout(3000) });
            const docenti = await res.json();
            let html = "";
            docenti.forEach(d => {
                if (!d.includes("Sconosciuto")) {
                    html += `<div class="col-12 col-sm-6 col-md-4 col-lg-3"><div class="card h-100 shadow-sm"><div class="card-body text-center">
                         <h5 class="card-title">${d}</h5>
                         <a href="orario.php?view=docente&id=${encodeURIComponent(d)}" class="btn btn-outline-info btn-sm">Visualizza orario</a>
                         </div></div></div>`;
                }
            });
            document.getElementById("teachers-container").innerHTML = html;
        } catch (e) { console.error("Error loading teachers", e); }

        // Fetch Labs
        try {
            const res = await fetch("api/getLabs.php",{ signal: AbortSignal.timeout(3000) });
            const labs = await res.json();
            let html = "";
            labs.forEach(l => {
                html += `<div class="col-12 col-sm-6 col-md-4 col-lg-3"><div class="card h-100 shadow-sm"><div class="card-body text-center">
                         <h5 class="card-title">${l}</h5>
                         <a href="orario.php?view=laboratorio&id=${encodeURIComponent(l)}" class="btn btn-outline-info btn-sm">Visualizza orario</a>
                         </div></div></div>`;
            });
            document.getElementById("labs-container").innerHTML = html;
        } catch (e) { console.error("Error loading labs", e); }
    });

// Ricerca
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
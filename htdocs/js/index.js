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
function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function parseTime(value) {
    const parts = String(value ?? "").split(":").map(Number);
    if (parts.length !== 2 || parts.some(Number.isNaN)) return null;
    return parts[0] * 60 + parts[1];
}

function joinWithAnd(items) {
    if (items.length <= 1) return items[0] || "";
    if (items.length === 2) return `${items[0]} e ${items[1]}`;
    return `${items.slice(0, -1).join(", ")} e ${items[items.length - 1]}`;
}

function updateCurrentTime() {
    const clock = document.getElementById("current-time");
    if (!clock) return;

    const now = new Date();
    const time = now.toLocaleTimeString("it-IT", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: false
    });
    const currentMinutes = now.getHours() * 60 + now.getMinutes();
    const periods = [
        ...(window.TIMETABLE_HOURS || []),
        ...(window.TIMETABLE_BREAKS || [])
    ];
    const currentPeriod = periods.find(period => {
        const start = parseTime(period.start);
        const end = parseTime(period.end);
        return start !== null && end !== null && currentMinutes >= start && currentMinutes < end;
    });

    clock.textContent = `${time} - ${currentPeriod?.label || "Nessuna lezione"}`;
    window.setTimeout(updateCurrentTime, 1000 - now.getMilliseconds());
}

async function updateCurrentOccupancy() {
    try {
        const response = await fetch("api/getCurrentOccupancy.php", {
            signal: AbortSignal.timeout(10000)
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const occupancy = await response.json();
        document.querySelectorAll("[data-teacher-position]").forEach(element => {
            const teacher = occupancy.teachers?.[element.dataset.teacherPosition];
            if (!teacher) {
                element.textContent = "Nessuna lezione in corso";
                return;
            }

            const places = [...(teacher.classes || []), ...(teacher.rooms || [])];
            element.textContent = places.length
                ? `Adesso: ${places.join(" • ")}`
                : "Lezione in corso";
        });

        document.querySelectorAll("[data-lab-occupancy]").forEach(element => {
            const classes = occupancy.rooms?.[element.dataset.labOccupancy] || [];
            element.textContent = classes.length
                ? `In uso da ${joinWithAnd(classes)}`
                : "Nessuna classe presente";
        });
    } catch (error) {
        console.error("Errore nel caricamento dell'occupazione corrente", error);
        document.querySelectorAll("[data-teacher-position], [data-lab-occupancy]").forEach(element => {
            element.textContent = "";
        });
    }
}

document.addEventListener("DOMContentLoaded", async function() {
    updateCurrentTime();

        // Fetch Classi
        try {
            const res = await fetch("api/getClassi.php",{ signal: AbortSignal.timeout(10000) });
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
            const res = await fetch("api/getDocenti.php",{ signal: AbortSignal.timeout(10000) });
            const docenti = await res.json();
            const visibleTeachers = docenti.filter(d => !d.includes("Sconosciuto"));
            let html = "";
            visibleTeachers.forEach(d => {
                html += `<div class="col-12 col-sm-6 col-md-4 col-lg-3"><div class="card h-100 shadow-sm"><div class="card-body text-center">
                     <h5 class="card-title">${escapeHtml(d)}</h5>
                     <div class="teacher-current-position small text-body-secondary mt-2" data-teacher-position="${escapeHtml(d)}"></div>
                     <a href="orario.php?view=docente&id=${encodeURIComponent(d)}" class="btn btn-outline-info btn-sm mt-2">Visualizza orario</a>
                     </div></div></div>`;
            });
            document.getElementById("teachers-container").innerHTML = html;
        } catch (e) { console.error("Error loading teachers", e); }

        // Fetch Labs
        try {
            const res = await fetch("api/getLabs.php",{ signal: AbortSignal.timeout(10000) });
            const labs = await res.json();
            let html = "";
            labs.forEach(l => {
                html += `<div class="col-12 col-sm-6 col-md-4 col-lg-3"><div class="card h-100 shadow-sm"><div class="card-body text-center">
                         <h5 class="card-title">${escapeHtml(l)}</h5>
                         <div class="lab-current-occupancy small text-body-secondary mt-2" data-lab-occupancy="${escapeHtml(l)}"></div>
                         <a href="orario.php?view=laboratorio&id=${encodeURIComponent(l)}" class="btn btn-outline-info btn-sm mt-2">Visualizza orario</a>
                         </div></div></div>`;
            });
            document.getElementById("labs-container").innerHTML = html;
            updateCurrentOccupancy();
            setInterval(updateCurrentOccupancy, 60000);
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
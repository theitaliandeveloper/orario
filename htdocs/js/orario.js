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

// JavaScript per la tabella oraria

function showLoadError() {
    const homeButton = '<a href="index.php" class="btn btn-primary">Torna alla home</a>';
    document.getElementById("desktop-table").innerHTML = `<tbody><tr><td class="text-center">${homeButton}</td></tr></tbody>`;
    document.getElementById("mobile-view").innerHTML = `<div class="d-flex justify-content-center">${homeButton}</div>`;
    document.getElementById("pdf-export").innerHTML = "";
}

document.addEventListener("DOMContentLoaded", async function() {
    const days = ["Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"];
    try {
        const res = await fetch(`api/getOrario.php?type=${VIEW_TYPE}&id=${encodeURIComponent(VIEW_ID)}`,{ signal: AbortSignal.timeout(10000) }); // Prova a caricare i dati con timeout di 2 secondi

        if (!res.ok) {
            if (res.status == 404) {
                location.replace("404.php");
            }
            else {
                document.getElementById("page-title").innerText = "Errore nel caricamento";
                showLoadError();
            }
            return;
        }
    
        const data = await res.json();

        const classIds = new Map();
        if (VIEW_TYPE !== "classe") {
            try {
                const classesRes = await fetch("api/getClassi.php", { signal: AbortSignal.timeout(10000) });
                if (classesRes.ok) {
                    const classes = await classesRes.json();
                    classes.forEach(item => classIds.set(item.name, item.id));
                }
            } catch (e) {
                console.warn("Impossibile caricare gli ID delle classi", e);
            }
        }

        let titleName = "";
        let pageTitle = "";
        if (VIEW_TYPE === "classe") titleName = data.class_name;
        if (VIEW_TYPE === "docente") titleName = data.teacher;
        if (VIEW_TYPE === "laboratorio") titleName = data.room;
        pageTitle = `Orario ${(VIEW_TYPE != "laboratorio") ? VIEW_TYPE : ""} ${titleName}`;
        document.getElementById("page-title").innerText = pageTitle;
        if (VIEW_TYPE === "docente" && SHOW_ORE_CATTEDRA === "1") {
            document.getElementById("page-hours").textContent = `Ore cattedra docente: ${data.hours}`;
        }
        document.title = APP_NAME + " - " + pageTitle;

        const timetable = data.timetable;
        const hours = (data.timetable_hours || []).map(hour => `${hour.label}<br> ${hour.start} - ${hour.end}`);
        const breaks = data.timetable_breaks || [];

        function dayKey(day) {
            return day.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        }

        function escapeHtml(value) {
            return String(value ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function entityLink(type, name) {
            if (!name) return "";
            const id = type === "classe" ? classIds.get(name) : name;
            if (id === undefined) return escapeHtml(name);
            const colorClass = "text-reset";
            return `<a class="${colorClass} text-decoration-none" href="orario.php?view=${type}&id=${encodeURIComponent(id)}">${escapeHtml(name)}</a>`;
        }

        const joinWithAnd = items => {
            if (items.length === 0) return "";
            if (items.length === 1) return items[0];
            if (items.length === 2) return `${items[0]} e ${items[1]}`;
            return `${items.slice(0, -1).join(", ")} e ${items[items.length - 1]}`;
        };

        const joinEntities = (type, items) => joinWithAnd((items || []).map(item => entityLink(type, item)));

        function slotDetails(slot) {
            if (VIEW_TYPE === 'classe') {
                return {
                    secondary: joinEntities("docente", slot.teachers),
                    rooms: joinEntities("laboratorio", slot.rooms)
                };
            }

            if (VIEW_TYPE === 'docente') {
                return {
                    secondary: joinEntities("classe", slot.classes),
                    rooms: joinEntities("laboratorio", slot.rooms)
                };
            }

            return {
                secondary: joinWithAnd(
                    (slot.classes || [])
                        .map(item => item.teacher
                            ? `${entityLink("classe", item.class)} (${entityLink("docente", item.teacher)})`
                            : entityLink("classe", item.class)
                        )
                ),
                rooms: ""
            };
        }

        // ============================================================
        // Render Desktop
        // ============================================================

        let dHead = `<th>Ora/Giorno</th>`;
        days.forEach(d => dHead += `<th>${d}</th>`);
        document.getElementById("desktop-head").innerHTML = dHead;

        let dBody = "";
        for (let i = 1; i <= hours.length; i++) {
            dBody += `<tr><td class="fw-bold">${hours[i-1]}</td>`;
            days.forEach(d => {
                const dayClean = dayKey(d);
                const slot = (timetable[dayClean] && timetable[dayClean][i]) ? timetable[dayClean][i] : null;
                if (slot && slot.subject) {
                    const details = slotDetails(slot);
                    const secondary = details.secondary;
                    const rooms = details.rooms;

                    dBody += `<td data-label="${d}">
                        <div class="subject fw-bold text-primary-emphasis">${escapeHtml(slot.subject)}</div>
                        ${secondary ? `<div class="teacher small">${secondary}</div>` : ''}
                        ${rooms ? `<div class="room text-secondary-emphasis small">${rooms}</div>` : ''}
                    </td>`;
                } else {
                    dBody += `<td data-label="${d}"></td>`;
                }
            });
            dBody += `</tr>`;

            breaks.filter(item => Number(item.after) === i).forEach(item => {
                dBody += `<tr><td colspan="7" class="text-center fw-semibold py-2">${escapeHtml(item.label)} <span class="text-body-secondary fw-normal">${escapeHtml(item.start)} - ${escapeHtml(item.end)}</span></td></tr>`;
            });
        }

        document.getElementById("desktop-body").innerHTML = dBody;

        // ============================================================
        // Render Mobile
        // ============================================================

        let mBody = "";
        const currentDayNumber = new Date().getDay();
        const currentDay = currentDayNumber >= 1 && currentDayNumber <= 6
            ? days[currentDayNumber - 1]
            : null;

        days.forEach((d, dayIndex) => {
            const dayClean = dayKey(d);
            const isCurrentDay = d === currentDay;
            const collapseId = `mobile-day-${dayIndex}`;
            mBody += `<div class="card mb-3 shadow-sm">
                <button class="card-header fw-semibold text-start w-100 border-0 border-bottom rounded-0 bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="${isCurrentDay}" aria-controls="${collapseId}">${escapeHtml(d)} <i class="bi bi-chevron-${isCurrentDay ? "up" : "down"} float-end" aria-hidden="true"></i></button>
                <div id="${collapseId}" class="collapse${isCurrentDay ? " show" : ""}"><div class="list-group list-group-flush">`;
            for (let i = 1; i <= hours.length; i++) {
                const slot = (timetable[dayClean] && timetable[dayClean][i]) ? timetable[dayClean][i] : null;
                const hlabel = hours[i - 1].replace("<br>", " ");
                if (slot && slot.subject) {
                    const details = slotDetails(slot);
                    const secondary = details.secondary;
                    const rooms = details.rooms;
                    
                    mBody += `<div class="list-group-item">
                        <div class="small text-body-secondary">${escapeHtml(hlabel)}</div>
                        <div class="fw-semibold text-primary-emphasis">${escapeHtml(slot.subject)}</div>
                        ${secondary ? `<div class="text-secondary-emphasis">${secondary}</div>` : ''}
                        ${rooms ? `<span class="badge border border-info text-info mt-1">${rooms}</span>` : ''}
                    </div>`;
                } else {
                    mBody += `<div class="list-group-item text-body-tertiary">
                        <div class="small">${hlabel}</div>
                        <div>—</div>
                    </div>`;
                }

                breaks.filter(item => Number(item.after) === i).forEach(item => {
                    mBody += `<div class="list-group-item timetable-break bg-warning-subtle"><span class="fw-semibold">${escapeHtml(item.label)}</span> <span class="small text-body-secondary">${escapeHtml(item.start)} - ${escapeHtml(item.end)}</span></div>`;
                });
            }

            mBody += `</div></div></div>`;
        });

        document.getElementById("mobile-view").innerHTML = mBody;
        document.querySelectorAll("#mobile-view [data-bs-toggle=\"collapse\"]").forEach(button => {
            const target = document.querySelector(button.dataset.bsTarget);
            const icon = button.querySelector("i");
            if (!target || !icon) return;

            const updateChevron = () => {
                const isExpanded = target.classList.contains("show");
                icon.classList.toggle("bi-chevron-up", isExpanded);
                icon.classList.toggle("bi-chevron-down", !isExpanded);
            };

            target.addEventListener("shown.bs.collapse", updateChevron);
            target.addEventListener("hidden.bs.collapse", updateChevron);
            updateChevron();
        });

    } catch (e) {
        console.error(e);
        document.getElementById("page-title").innerText = "Errore nel caricamento";
        showLoadError();
    }
});
